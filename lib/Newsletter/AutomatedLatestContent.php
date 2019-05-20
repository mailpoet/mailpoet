<?php
namespace MailPoet\Newsletter;

use MailPoet\Logging\Logger;
use MailPoet\Newsletter\Editor\Transformer;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class AutomatedLatestContent {
  const DEFAULT_POSTS_PER_PAGE = 10;

  private $newsletter_id;
  private $newer_than_timestamp;

  function __construct($newsletter_id = false, $newer_than_timestamp = false) {
    $this->newsletter_id = $newsletter_id;
    $this->newer_than_timestamp = $newer_than_timestamp;
  }

  function filterOutSentPosts($where) {
    $sentPostsQuery = 'SELECT ' . MP_NEWSLETTER_POSTS_TABLE . '.post_id FROM '
      . MP_NEWSLETTER_POSTS_TABLE . ' WHERE '
      . MP_NEWSLETTER_POSTS_TABLE . ".newsletter_id='" . $this->newsletter_id . "'";

    $wherePostUnsent = 'ID NOT IN (' . $sentPostsQuery . ')';

    if (!empty($where)) $wherePostUnsent = ' AND ' . $wherePostUnsent;

    return $where . $wherePostUnsent;
  }

  public function ensureConsistentQueryType(\WP_Query $query) {
    // Queries with taxonomies are autodetected as 'is_archive=true' and 'is_home=false'
    // while queries without them end up being 'is_archive=false' and 'is_home=true'.
    // This is to fix that by always enforcing constistent behavior.
    $query->is_archive = true;
    $query->is_home = false;
  }

  function getPosts($args, $posts_to_exclude = []) {
    // Get posts as logged out user, so private posts hidden by other plugins (e.g. UAM) are also excluded
    $current_user_id = WPFunctions::get()->getCurrentUserId();
    WPFunctions::get()->wpSetCurrentUser(0);

    Logger::getLogger('post-notifications')->addInfo(
      'loading automated latest content',
      ['args' => $args, 'posts_to_exclude' => $posts_to_exclude, 'newsletter_id' => $this->newsletter_id, 'newer_than_timestamp' => $this->newer_than_timestamp]
    );
    $posts_per_page = (!empty($args['amount']) && (int)$args['amount'] > 0)
      ? (int)$args['amount']
      : self::DEFAULT_POSTS_PER_PAGE;
    $parameters = [
      'posts_per_page' => $posts_per_page,
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
    if (!empty($posts_to_exclude)) {
      $parameters['post__not_in'] = $posts_to_exclude;
    }
    $parameters['tax_query'] = $this->constructTaxonomiesQuery($args);

    // This enables using posts query filters for get_posts, where by default
    // it is disabled.
    // However, it also enables other plugins and themes to hook in and alter
    // the query.
    $parameters['suppress_filters'] = false;

    if ($this->newer_than_timestamp) {
      $parameters['date_query'] = [
        [
          'column' => 'post_date',
          'after' => $this->newer_than_timestamp,
        ],
      ];
    }

    // set low priority to execute 'ensureConstistentQueryType' before any other filter
    $filter_priority = defined('PHP_INT_MIN') ? constant('PHP_INT_MIN') : ~PHP_INT_MAX;
    WPFunctions::get()->addAction('pre_get_posts', [$this, 'ensureConsistentQueryType'], $filter_priority);
    $this->_attachSentPostsFilter();

    Logger::getLogger('post-notifications')->addInfo(
      'getting automated latest content',
      ['parameters' => $parameters]
    );
    $posts = WPFunctions::get()->getPosts($parameters);
    $this->logPosts($posts);

    WPFunctions::get()->removeAction('pre_get_posts', [$this, 'ensureConsistentQueryType'], $filter_priority);
    $this->_detachSentPostsFilter();
    WPFunctions::get()->wpSetCurrentUser($current_user_id);
    return $posts;
  }

  function transformPosts($args, $posts) {
    $transformer = new Transformer($args);
    return $transformer->transform($posts);
  }

  function constructTaxonomiesQuery($args) {
    $taxonomies_query = [];
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
          $taxonomies_query[] = $tax;
        }
      }
      if (!empty($taxonomies_query)) {
        // With exclusion we want to use 'AND', because we want posts that
        // don't have excluded tags/categories. But with inclusion we want to
        // use 'OR', because we want posts that have any of the included
        // tags/categories
        $taxonomies_query['relation'] = ($args['inclusionType'] === 'exclude') ? 'AND' : 'OR';
      }
    }

    // make $taxonomies_query nested to avoid conflicts with plugins that use taxonomies
    return empty($taxonomies_query) ? [] : [$taxonomies_query];
  }

  private function _attachSentPostsFilter() {
    if ($this->newsletter_id > 0) {
      WPFunctions::get()->addAction('posts_where', [$this, 'filterOutSentPosts']);
    }
  }

  private function _detachSentPostsFilter() {
    if ($this->newsletter_id > 0) {
      WPFunctions::get()->removeAction('posts_where', [$this, 'filterOutSentPosts']);
    }
  }

  private function logPosts(array $posts) {
    $posts_to_log = [];
    foreach ($posts as $post) {
      $posts_to_log[] = [
        'id' => $post->ID,
        'post_date' => $post->post_date,
      ];
    }
    Logger::getLogger('post-notifications')->addInfo(
      'automated latest content loaded posts',
      ['posts' => $posts_to_log]
    );
  }
}
