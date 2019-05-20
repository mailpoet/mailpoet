<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Posts as WPPosts;

if (!defined('ABSPATH')) exit;

class AutomatedLatestContent extends APIEndpoint {
  /** @var \MailPoet\Newsletter\AutomatedLatestContent  */
  public $ALC;
  private $wp;
  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  function __construct(\MailPoet\Newsletter\AutomatedLatestContent $alc, WPFunctions $wp) {
    $this->ALC = $alc;
    $this->wp = $wp;
  }

  function getPostTypes() {
    $post_types = array_map(function($post_type) {
      return [
        'name' => $post_type->name,
        'label' => $post_type->label,
      ];
    }, WPPosts::getTypes([], 'objects'));
    return $this->successResponse(
      array_filter($post_types)
    );
  }

  function getTaxonomies($data = []) {
    $post_type = (isset($data['postType'])) ? $data['postType'] : 'post';
    $all_taxonomies = WPFunctions::get()->getObjectTaxonomies($post_type, 'objects');
    $taxonomies_with_label = array_filter($all_taxonomies, function($taxonomy) {
      return $taxonomy->label;
    });
    return $this->successResponse($taxonomies_with_label);
  }

  function getTerms($data = []) {
    $taxonomies = (isset($data['taxonomies'])) ? $data['taxonomies'] : [];
    $search = (isset($data['search'])) ? $data['search'] : '';
    $limit = (isset($data['limit'])) ? (int)$data['limit'] : 100;
    $page = (isset($data['page'])) ? (int)$data['page'] : 1;
    $args = [
      'taxonomy' => $taxonomies,
      'hide_empty' => false,
      'search' => $search,
      'number' => $limit,
      'offset' => $limit * ($page - 1),
      'orderby' => 'name',
      'order' => 'ASC',
    ];

    $args = $this->wp->applyFilters('mailpoet_search_terms_args', $args);
    $terms = WPPosts::getTerms($args);

    return $this->successResponse(array_values($terms));
  }

  function getPosts($data = []) {
    return $this->successResponse(
      $this->ALC->getPosts($data)
    );
  }

  function getTransformedPosts($data = []) {
    $posts = $this->ALC->getPosts($data);
    return $this->successResponse(
      $this->ALC->transformPosts($data, $posts)
    );
  }

  function getBulkTransformedPosts($data = []) {
    $used_posts = [];
    $rendered_posts = [];

    foreach ($data['blocks'] as $block) {
      $posts = $this->ALC->getPosts($block, $used_posts);
      $rendered_posts[] = $this->ALC->transformPosts($block, $posts);

      foreach ($posts as $post) {
        $used_posts[] = $post->ID;
      }
    }

    return $this->successResponse($rendered_posts);
  }
}
