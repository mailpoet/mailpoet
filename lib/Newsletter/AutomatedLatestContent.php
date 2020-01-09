<?php

namespace MailPoet\Newsletter;

use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\Editor\Transformer;
use MailPoet\WP\Functions as WPFunctions;

class AutomatedLatestContent {
  const DEFAULT_POSTS_PER_PAGE = 10;

  private $newsletterId;
  private $newerThanTimestamp;

  /** @var LoggerFactory */
  private $loggerFactory;

  public function __construct($newsletterId = false, $newerThanTimestamp = false) {
    $this->newsletterId = $newsletterId;
    $this->newerThanTimestamp = $newerThanTimestamp;
    $this->loggerFactory = LoggerFactory::getInstance();
  }

  public function filterOutSentPosts($where) {
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
    $query->isArchive = true;
    $query->isHome = false;
  }

  public function getPosts($args, $postsToExclude = []) {
    // Get posts as logged out user, so private posts hidden by other plugins (e.g. UAM) are also excluded
    $currentUserId = WPFunctions::get()->getCurrentUserId();
    WPFunctions::get()->wpSetCurrentUser(0);

    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
      'loading automated latest content',
      ['args' => $args, 'posts_to_exclude' => $postsToExclude, 'newsletter_id' => $this->newsletterId, 'newer_than_timestamp' => $this->newerThanTimestamp]
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

    if ($this->newerThanTimestamp) {
      $parameters['date_query'] = [
        [
          'column' => 'post_date',
          'after' => $this->newerThanTimestamp,
        ],
      ];
    }

    // set low priority to execute 'ensureConstistentQueryType' before any other filter
    $filterPriority = defined('PHP_INT_MIN') ? constant('PHP_INT_MIN') : ~PHP_INT_MAX;
    WPFunctions::get()->addAction('pre_get_posts', [$this, 'ensureConsistentQueryType'], $filterPriority);
    $this->_attachSentPostsFilter();

    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
      'getting automated latest content',
      ['parameters' => $parameters]
    );
    $posts = WPFunctions::get()->getPosts($parameters);
    $this->logPosts($posts);

    WPFunctions::get()->removeAction('pre_get_posts', [$this, 'ensureConsistentQueryType'], $filterPriority);
    $this->_detachSentPostsFilter();
    WPFunctions::get()->wpSetCurrentUser($currentUserId);
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

  private function _attachSentPostsFilter() {
    if ($this->newsletterId > 0) {
      WPFunctions::get()->addAction('posts_where', [$this, 'filterOutSentPosts']);
    }
  }

  private function _detachSentPostsFilter() {
    if ($this->newsletterId > 0) {
      WPFunctions::get()->removeAction('posts_where', [$this, 'filterOutSentPosts']);
    }
  }

  private function logPosts(array $posts) {
    $postsToLog = [];
    foreach ($posts as $post) {
      $postsToLog[] = [
        'id' => $post->ID,
        'post_date' => $post->postDate,
      ];
    }
    $this->loggerFactory->getLogger(LoggerFactory::TOPIC_POST_NOTIFICATIONS)->addInfo(
      'automated latest content loaded posts',
      ['posts' => $postsToLog]
    );
  }
}
