<?php
namespace MailPoet\Router;

if(!defined('ABSPATH')) exit;

class AutomatedLatestContent {
  public $ALC;

  function __construct() {
    $this->ALC = new \MailPoet\Newsletter\AutomatedLatestContent();
  }

  function getPostTypes() {
    return get_post_types(array(), 'objects');
  }

  function getTaxonomies($args) {
    $post_type = (isset($args['postType'])) ? $args['postType'] : 'post';
    return get_object_taxonomies($post_type, 'objects');
  }

  function getTerms($args) {
    $taxonomies = (isset($args['taxonomies'])) ? $args['taxonomies'] : array();
    $search = (isset($args['search'])) ? $args['search'] : '';
    $limit = (isset($args['limit'])) ? (int)$args['limit'] : 10;
    $page = (isset($args['page'])) ? (int)$args['page'] : 1;
    return get_terms(
      $taxonomies,
      array(
        'hide_empty' => false,
        'search' => $search,
        'number' => $limit,
        'offset' => $limit * ($page - 1)
      )
    );
  }

  function getPosts($args) {
    return $this->ALC->getPosts($args);
  }

  function getTransformedPosts($args) {
    $posts = $this->ALC->getPosts($args);
    return $this->ALC->transformPosts($args, $posts);
  }
}
