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
}
