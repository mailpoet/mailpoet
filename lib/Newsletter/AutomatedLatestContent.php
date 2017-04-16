<?php
namespace MailPoet\Newsletter;

use MailPoet\Newsletter\Editor\Transformer;

if(!defined('ABSPATH')) exit;

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

    if(!empty($where)) $wherePostUnsent = ' AND ' . $wherePostUnsent;

    return $where . $wherePostUnsent;
  }

  function getPosts($args, $posts_to_exclude = array()) {
    $posts_per_page = (!empty($args['amount']) && (int)$args['amount'] > 0)
      ? (int)$args['amount']
      : self::DEFAULT_POSTS_PER_PAGE;
    $parameters = array(
      'posts_per_page' => $posts_per_page,
      'post_type' => (isset($args['contentType'])) ? $args['contentType'] : 'post',
      'post_status' => (isset($args['postStatus'])) ? $args['postStatus'] : 'publish',
      'orderby' => 'date',
      'order' => ($args['sortBy'] === 'newest') ? 'DESC' : 'ASC',
    );
    if(isset($args['search'])) {
      $parameters['s'] = $args['search'];
    }
    if(isset($args['posts']) && is_array($args['posts'])) {
      $parameters['post__in'] = $args['posts'];
    }
    if(!empty($posts_to_exclude)) {
      $parameters['post__not_in'] = $posts_to_exclude;
    }
    $parameters['tax_query'] = $this->constructTaxonomiesQuery($args);

    // This enables using posts query filters for get_posts, where by default
    // it is disabled.
    // However, it also enables other plugins and themes to hook in and alter
    // the query.
    $parameters['suppress_filters'] = false;

    if($this->newer_than_timestamp) {
      $parameters['date_query'] = array(
        array(
          'column' => 'post_date',
          'after' => $this->newer_than_timestamp
        )
      );
    }

    $this->_attachSentPostsFilter();
    $posts = get_posts($parameters);
    $this->_detachSentPostsFilter();
    return $posts;
  }

  function transformPosts($args, $posts) {
    $transformer = new Transformer($args);
    return $transformer->transform($posts);
  }

  function constructTaxonomiesQuery($args) {
    $taxonomies_query = array();
    if(isset($args['terms']) && is_array($args['terms'])) {
      $taxonomies = array();
      // Categorize terms based on their taxonomies
      foreach($args['terms'] as $term) {
        $taxonomy = $term['taxonomy'];
        if(!isset($taxonomies[$taxonomy])) {
          $taxonomies[$taxonomy] = array();
        }
        $taxonomies[$taxonomy][] = $term['id'];
      }

      foreach($taxonomies as $taxonomy => $terms) {
        if(!empty($terms)) {
          $tax = array(
            'taxonomy' => $taxonomy,
            'field' => 'id',
            'terms' => $terms,
          );
          if($args['inclusionType'] === 'exclude') $tax['operator'] = 'NOT IN';
          $taxonomies_query[] = $tax;
        }
      }
      if(!empty($taxonomies_query)) {
        // With exclusion we want to use 'AND', because we want posts that
        // don't have excluded tags/categories. But with inclusion we want to
        // use 'OR', because we want posts that have any of the included
        // tags/categories
        $taxonomies_query['relation'] = ($args['inclusionType'] === 'exclude') ? 'AND' : 'OR';
      }
    }
    return $taxonomies_query;
  }

  private function _attachSentPostsFilter() {
    if($this->newsletter_id > 0) {
      add_action('posts_where', array($this, 'filterOutSentPosts'));
    }
  }

  private function _detachSentPostsFilter() {
    if($this->newsletter_id > 0) {
      remove_action('posts_where', array($this, 'filterOutSentPosts'));
    }
  }
}
