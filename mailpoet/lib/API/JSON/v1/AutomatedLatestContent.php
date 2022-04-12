<?php

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\Config\AccessControl;
use MailPoet\Newsletter\AutomatedLatestContent as ALC;
use MailPoet\Util\APIPermissionHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Posts as WPPosts;

class AutomatedLatestContent extends APIEndpoint {
  /** @var ALC  */
  public $ALC;

  /*** @var WPFunctions */
  private $wp;

  /*** @var APIPermissionHelper */
  private $permissionHelper;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  public function __construct(
    ALC $alc,
    APIPermissionHelper $permissionHelper,
    WPFunctions $wp
  ) {
    $this->ALC = $alc;
    $this->wp = $wp;
    $this->permissionHelper = $permissionHelper;
  }

  public function getPostTypes() {
    $postTypes = array_map(function($postType) {
      return [
        'name' => $postType->name,
        'label' => $postType->label,
      ];
    }, WPPosts::getTypes([], 'objects'));
    return $this->successResponse(
      array_filter($postTypes)
    );
  }

  public function getTaxonomies($data = []) {
    $postType = (isset($data['postType'])) ? $data['postType'] : 'post';
    $allTaxonomies = WPFunctions::get()->getObjectTaxonomies($postType, 'objects');
    $taxonomiesWithLabel = array_filter($allTaxonomies, function($taxonomy) {
      return $taxonomy->label;
    });
    return $this->successResponse($taxonomiesWithLabel);
  }

  public function getTerms($data = []) {
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

    $args = (array)$this->wp->applyFilters('mailpoet_search_terms_args', $args);
    $terms = WPFunctions::get()->getTerms($args);

    return $this->successResponse(array_values($terms));
  }

  /**
   * @param \WP_Post[] $posts
   * @return \WP_Post[]
   */
  private function getPermittedPosts($posts) {
    return array_filter($posts, function ($post) {
      return $this->permissionHelper->checkReadPermission($post);
    });
  }

  public function getPosts($data = []) {
    return $this->successResponse(
      $this->getPermittedPosts($this->ALC->getPosts($data))
    );
  }

  public function getTransformedPosts($data = []) {
    $posts = $this->getPermittedPosts($this->ALC->getPosts($data));
    return $this->successResponse(
      $this->ALC->transformPosts($data, $posts)
    );
  }

  public function getBulkTransformedPosts($data = []) {
    $usedPosts = [];
    $renderedPosts = [];

    foreach ($data['blocks'] as $block) {
      $posts = $this->getPermittedPosts($this->ALC->getPosts($block, $usedPosts));
      $renderedPosts[] = $this->ALC->transformPosts($block, $posts);

      foreach ($posts as $post) {
        $usedPosts[] = $post->ID;
      }
    }

    return $this->successResponse($renderedPosts);
  }
}
