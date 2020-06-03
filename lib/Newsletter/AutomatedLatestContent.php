<?php

namespace MailPoet\Newsletter;

use DateTimeInterface;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\Editor\Transformer;
use MailPoet\WP\Functions as WPFunctions;

class AutomatedLatestContent {
  const DEFAULT_POSTS_PER_PAGE = 10;

  /** @var LoggerFactory */
  private $loggerFactory;

  /** @var int|false */
  private $newsletterId;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    LoggerFactory $loggerFactory,
    WPFunctions $wp
  ) {
    $this->loggerFactory = $loggerFactory;
    $this->wp = $wp;
  }

  public function filterOutSentPosts(string $where): string {
    $sentPostsQuery = 'SELECT ' . MP_NEWSLETTER_POSTS_TABLE . '.post_id FROM '
      . MP_NEWSLETTER_POSTS_TABLE . ' WHERE '
      . MP_NEWSLETTER_POSTS_TABLE . ".newsletter_id='" . $this->newsletterId . "'";

    $wherePostUnsent = 'ID NOT IN (' . $sentPostsQuery . ')';

    if (!empty($where)) $wherePostUnsent = ' AND ' . $wherePostUnsent;

    return $where . $wherePostUnsent;
  }

  public function ensureConsistentQueryType(\WP_Query $query) {
    // Queries with taxonomies are autodetected as 'is_archive=true' and 'is_home=false'
    // while queries without them end up being 'is_archive=false' and 'is_home=true'.
    // This is to fix that by always enforcing constistent behavior.
    $query->is_archive = true; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $query->is_home = false; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
  }

  public function getPosts($args, $postsToExclude = [], $newsletterId = false, $newerThanTimestamp = false) {
    $this->newsletterId = $newsletterId;
    // Get posts as logged out user, so private posts hidden by other plugins (e.g. UAM) are also excluded
    $currentUserId = $this->wp->getCurrentUserId();
    $this->wp->wpSetCurrentUser(0);

    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
      'loading automated latest content',
      ['args' => $args, 'posts_to_exclude' => $postsToExclude, 'newsletter_id' => $newsletterId, 'newer_than_timestamp' => $newerThanTimestamp]
    );
    $postsPerPage = (!empty($args['amount']) && (int)$args['amount'] > 0)
      ? (int)$args['amount']
      : self::DEFAULT_POSTS_PER_PAGE;
    $parameters = [
      'posts_per_page' => $postsPerPage,
      'post_type' => (isset($args['contentType'])) ? $args['contentType'] : 'post',
      'post_status' => (isset($args['postStatus'])) ? $args['postStatus'] : 'publish',
      'orderby' => 'date',
      'order' => ($args['sortBy'] === 'newest') ? 'DESC' : 'ASC',
    ];
    if (!empty($args['offset']) && (int)$args['offset'] > 0) {
      $parameters['offset'] = (int)$args['offset'];
    }
    if (isset($args['search'])) {
      $parameters['s'] = $args['search'];
    }
    if (isset($args['posts']) && is_array($args['posts'])) {
      $parameters['post__in'] = $args['posts'];
      $parameters['posts_per_page'] = -1; // Get all posts with matching IDs
    }
    if (!empty($postsToExclude)) {
      $parameters['post__not_in'] = $postsToExclude;
    }
    $parameters['tax_query'] = $this->constructTaxonomiesQuery($args);

    // This enables using posts query filters for get_posts, where by default
    // it is disabled.
    // However, it also enables other plugins and themes to hook in and alter
    // the query.
    $parameters['suppress_filters'] = false;

    if ($newerThanTimestamp instanceof DateTimeInterface) {
      $parameters['date_query'] = [
        [
          'column' => 'post_date',
          'after' => $newerThanTimestamp->format('Y-m-d H:i:s'),
        ],
      ];
    }

    // set low priority to execute 'ensureConstistentQueryType' before any other filter
    $filterPriority = defined('PHP_INT_MIN') ? constant('PHP_INT_MIN') : ~PHP_INT_MAX;
    $this->wp->addAction('pre_get_posts', [$this, 'ensureConsistentQueryType'], $filterPriority);
    $this->_attachSentPostsFilter($newsletterId);

    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
      'getting automated latest content',
      ['parameters' => $parameters]
    );
    $posts = $this->wp->getPosts($parameters);
    $this->logPosts($posts);

    $this->wp->removeAction('pre_get_posts', [$this, 'ensureConsistentQueryType'], $filterPriority);
    $this->_detachSentPostsFilter($newsletterId);
    $this->wp->wpSetCurrentUser($currentUserId);
    return $posts;
  }

  public function transformPosts($args, $posts) {
    $transformer = new Transformer($args);
    return $transformer->transform($posts);
  }

  public function constructTaxonomiesQuery($args) {
    $taxonomiesQuery = [];
    if (isset($args['terms']) && is_array($args['terms'])) {
      $taxonomies = [];
      // Categorize terms based on their taxonomies
      foreach ($args['terms'] as $term) {
        $taxonomy = $term['taxonomy'];
        if (!isset($taxonomies[$taxonomy])) {
          $taxonomies[$taxonomy] = [];
        }
        $taxonomies[$taxonomy][] = $term['id'];
      }

      foreach ($taxonomies as $taxonomy => $terms) {
        if (!empty($terms)) {
          $tax = [
            'taxonomy' => $taxonomy,
            'field' => 'id',
            'terms' => $terms,
          ];
          if ($args['inclusionType'] === 'exclude') $tax['operator'] = 'NOT IN';
          $taxonomiesQuery[] = $tax;
        }
      }
      if (!empty($taxonomiesQuery)) {
        // With exclusion we want to use 'AND', because we want posts that
        // don't have excluded tags/categories. But with inclusion we want to
        // use 'OR', because we want posts that have any of the included
        // tags/categories
        $taxonomiesQuery['relation'] = ($args['inclusionType'] === 'exclude') ? 'AND' : 'OR';
      }
    }

    // make $taxonomies_query nested to avoid conflicts with plugins that use taxonomies
    return empty($taxonomiesQuery) ? [] : [$taxonomiesQuery];
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
        'id' => $post->ID,
        'post_date' => $post->post_date, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      ];
    }
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
      'automated latest content loaded posts',
      ['posts' => $postsToLog]
    );
  }
}
