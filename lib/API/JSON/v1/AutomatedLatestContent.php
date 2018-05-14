<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;
use MailPoet\WP\Hooks;
use MailPoet\WP\Posts as WPPosts;

if(!defined('ABSPATH')) exit;

class AutomatedLatestContent extends APIEndpoint {
  public $ALC;
  public $permissions = array(
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS
  );

  function __construct() {
    $this->ALC = new \MailPoet\Newsletter\AutomatedLatestContent();
  }

  function getPostTypes() {
    $post_types = array_map(function($post_type) {
      return array(
        'name' => $post_type->name,
        'label' => $post_type->label
      );
    }, WPPosts::getTypes(array(), 'objects'));
    return $this->successResponse(
      array_filter($post_types)
    );
  }

  function getTaxonomies($data = array()) {
    $post_type = (isset($data['postType'])) ? $data['postType'] : 'post';
    $all_taxonomies = get_object_taxonomies($post_type, 'objects');
    $taxonomies_with_label = array_filter($all_taxonomies, function($taxonomy) {
      return $taxonomy->label;
    });
    return $this->successResponse($taxonomies_with_label);
  }

  function getTerms($data = array()) {
    $taxonomies = (isset($data['taxonomies'])) ? $data['taxonomies'] : array();
    $search = (isset($data['search'])) ? $data['search'] : '';
    $limit = (isset($data['limit'])) ? (int)$data['limit'] : 50;
    $page = (isset($data['page'])) ? (int)$data['page'] : 1;
    $args = array(
      'taxonomy' => $taxonomies,
      'hide_empty' => false,
      'search' => $search,
      'number' => $limit,
      'offset' => $limit * ($page - 1),
      'orderby' => 'name',
      'order' => 'ASC'
    );

    $args = Hooks::applyFilters('mailpoet_search_terms_args', $args);

    return $this->successResponse(WPPosts::getTerms($args));
  }

  function getPosts($data = array()) {
    return $this->successResponse(
      $this->ALC->getPosts($data)
    );
  }

  function getTransformedPosts($data = array()) {
    $posts = $this->ALC->getPosts($data);
    return $this->successResponse(
      $this->ALC->transformPosts($data, $posts)
    );
  }

  function getBulkTransformedPosts($data = array()) {
    $alc = new \MailPoet\Newsletter\AutomatedLatestContent();

    $used_posts = array();
    $rendered_posts = array();

    foreach($data['blocks'] as $block) {
      $posts = $alc->getPosts($block, $used_posts);
      $rendered_posts[] = $alc->transformPosts($block, $posts);

      foreach($posts as $post) {
        $used_posts[] = $post->ID;
      }
    }

    return $this->successResponse($rendered_posts);
  }
}