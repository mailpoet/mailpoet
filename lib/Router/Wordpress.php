<?php
namespace MailPoet\Router;

if(!defined('ABSPATH')) exit;

class Wordpress {
  function __construct() {
  }

  function getPostTypes() {
    wp_send_json(get_post_types(array(), 'objects'));
  }

  function getTaxonomies($args) {
    $post_type = (isset($args['postType'])) ? $args['postType'] : 'post';
    wp_send_json(get_object_taxonomies($post_type, 'objects'));
  }

  function getTerms($args) {
    $taxonomies = (isset($args['taxonomies'])) ? $args['taxonomies'] : array();
    $search = (isset($args['search'])) ? $args['search'] : '';
    $limit = (isset($args['limit'])) ? (int)$args['limit'] : 10;
    $page = (isset($args['page'])) ? (int)$args['page'] : 1;

    wp_send_json(get_terms($taxonomies, array(
      'hide_empty' => false,
      'search' => $search,
      'number' => $limit,
      'offset' => $limit * ($page - 1),
    )));
  }

  function getPosts($args) {
    $parameters = array(
      'posts_per_page' => (isset($args['amount'])) ? (int)$args['amount'] : 10,
      'post_type' => (isset($args['contentType'])) ? $args['contentType'] : 'post',
      'post_status' => (isset($args['postStatus'])) ? $args['postStatus'] : 'publish',
      'orderby' => 'date',
      'order' => ($args['sortBy'] === 'newest') ? 'DESC' : 'ASC',
    );

    if (isset($args['search'])) {
      $parameters['s'] = $args['search'];
    }

    if (isset($args['terms']) && is_array($args['terms']) && !empty($args['terms'])) {
      // Add filtering by tags and categories
      $tags = array();
      $categories = array();
      foreach($args['terms'] as $term) {
        if ($term['taxonomy'] === 'category') $categories[] = $term['id'];
        else if ($term['taxonomy'] === 'post_tag') $tags[] = $term['id'];
      }

      $taxonomies_query = array();
      foreach (array('post_tag' => $tags, 'category' => $categories) as $taxonomy => $terms) {
        if (!empty($terms)) {
          $tax = array(
            'taxonomy' => $taxonomy,
            'field' => 'id',
            'terms' => $terms,
          );
          if ($args['inclusionType'] === 'exclude') $tax['operator'] = 'NOT IN';
          $taxonomies_query[] = $tax;
        }
      }

      if (!empty($taxonomies_query)) {
        // With exclusion we want to use 'AND', because we want posts that don't have excluded tags/categories
        // But with inclusion we want to use 'OR', because we want posts that have any of the included tags/categories
        $taxonomies_query['relation'] = ($args['inclusionType'] === 'exclude') ? 'AND' : 'OR';
        $parameters['tax_query'] = $taxonomies_query;
      }
    }

    wp_send_json(get_posts($parameters));
  }
}
