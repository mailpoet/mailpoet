<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter;

use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\Editor\Transformer;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;

class DynamicProducts {

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var int|false */
  private $newsletterId;

  /** @var NewsletterPostsRepository */
  private $newsletterPostsRepository;

  /** @var WPFunctions */
  private $wp;

  /** @var WCHelper */
  private $wcHelper;

  public function __construct(
    LoggerFactory $loggerFactory,
    NewsletterPostsRepository $newsletterPostsRepository,
    WPFunctions $wp,
    WCHelper $wcHelper
  ) {
    $this->loggerFactory = $loggerFactory;
    $this->newsletterPostsRepository = $newsletterPostsRepository;
    $this->wp = $wp;
    $this->wcHelper = $wcHelper;
  }

  public function filterOutSentPosts(string $where): string {
    $newsletterPostsTableName = $this->newsletterPostsRepository->getTableName();
    $sentPostsQuery = 'SELECT ' . $newsletterPostsTableName . '.post_id FROM '
      . $newsletterPostsTableName . ' WHERE '
      . $newsletterPostsTableName . ".newsletter_id='" . $this->newsletterId . "'";

    $wherePostUnsent = 'ID NOT IN (' . $sentPostsQuery . ')';

    if (!empty($where)) $wherePostUnsent = ' AND ' . $wherePostUnsent;

    return $where . $wherePostUnsent;
  }

  public function ensureConsistentQueryType(\WP_Query $query) {
    // Queries with taxonomies are autodetected as 'is_archive=true' and 'is_home=false'
    // while queries without them end up being 'is_archive=false' and 'is_home=true'.
    // This is to fix that by always enforcing constistent behavior.
    $query->is_archive = true; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $query->is_home = false; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function getPosts(BlockPostQuery $query) {
    $this->newsletterId = $query->newsletterId;
    // Get posts as logged out user, so private posts hidden by other plugins (e.g. UAM) are also excluded
    $currentUserId = $this->wp->getCurrentUserId();
    // phpcs:ignore Generic.PHP.ForbiddenFunctions.Discouraged
    wp_set_current_user(0);

    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->info(
      'loading dynamic products',
      [
        'args' => $query->args,
        'posts_to_exclude' => $query->postsToExclude,
        'newsletter_id' => $query->newsletterId,
        'newer_than_timestamp' => $query->newerThanTimestamp,
      ]
    );

    // set low priority to execute 'ensureConstistentQueryType' before any other filter
    $filterPriority = defined('PHP_INT_MIN') ? constant('PHP_INT_MIN') : ~PHP_INT_MAX;
    $this->wp->addAction('pre_get_posts', [$this, 'ensureConsistentQueryType'], $filterPriority);
    $this->_attachSentPostsFilter($query->newsletterId);
    $parameters = $query->getQueryParams();
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->info(
      'getting dynamic products',
      ['parameters' => $parameters]
    );

    // Convert WP_Query parameters to WC_Product_Query parameters
    $wcArgs = [
      'limit' => $parameters['posts_per_page'] ?? -1,
      'orderby' => $parameters['orderby'] ?? 'date',
      'order' => $parameters['order'] ?? 'DESC',
      'exclude' => $query->postsToExclude, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
    ];

    // WooCommerce Product Query does not support 'any' status,
    // so we need to handle it manually
    $postStatus = $parameters['post_status'] ?? 'publish';
    // Default to published products only
    $wcArgs['status'] = 'publish';

    // Store original user capabilities for later permission filtering
    $canEditPrivate = $this->wp->userCan($currentUserId, 'edit_private_posts');
    $canEditOthers = $this->wp->userCan($currentUserId, 'edit_others_posts');
    $isAdmin = $this->wp->userCan($currentUserId, 'manage_options');

    if (!empty($parameters['tax_query'])) {
      $wcArgs['tax_query'] = $parameters['tax_query'];
    }

    if (!empty($parameters['date_query'])) {
      $wcArgs['date_query'] = $parameters['date_query'];
    }

    // Fetch published products (safe for everyone)
    $products = $this->wcHelper->wcGetProducts($wcArgs);

    // For privileged users, fetch additional product statuses separately and merge
    if ($postStatus === 'any' && ($canEditPrivate || $isAdmin)) {
      // Editors get private products
      if ($canEditPrivate) {
        $privateArgs = $wcArgs;
        $privateArgs['status'] = 'private';
        $privateProducts = $this->wcHelper->wcGetProducts($privateArgs);
        $products = array_merge($products, $privateProducts);
      }

      // Admins get draft and pending products too
      if ($isAdmin) {
        $draftArgs = $wcArgs;
        $draftArgs['status'] = ['draft', 'pending'];
        $draftProducts = $this->wcHelper->wcGetProducts($draftArgs);
        $products = array_merge($products, $draftProducts);
      }
    }

    $this->logPosts($products);

    $this->wp->removeAction('pre_get_posts', [$this, 'ensureConsistentQueryType'], $filterPriority);
    $this->_detachSentPostsFilter($query->newsletterId);
    // phpcs:ignore Generic.PHP.ForbiddenFunctions.Discouraged
    wp_set_current_user($currentUserId);
    return $products;
  }

  public function transformPosts($args, $posts) {
    $transformer = new Transformer($args);
    return $transformer->transform($posts);
  }

  private function _attachSentPostsFilter($newsletterId) {
    if ($newsletterId > 0) {
      $this->wp->addAction('posts_where', [$this, 'filterOutSentPosts']);
    }
  }

  private function _detachSentPostsFilter($newsletterId) {
    if ($newsletterId > 0) {
      $this->wp->removeAction('posts_where', [$this, 'filterOutSentPosts']);
    }
  }

  private function logPosts(array $posts) {
    $postsToLog = [];
    foreach ($posts as $post) {
      $postsToLog[] = [
        'id' => $post->get_id(),
        'post_date' => $post->get_date_created()->format('Y-m-d H:i:s'), // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      ];
    }
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->info(
      'dynamic products loaded posts',
      ['posts' => $postsToLog]
    );
  }
}
