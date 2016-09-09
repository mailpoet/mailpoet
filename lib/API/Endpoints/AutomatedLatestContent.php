<?php
namespace MailPoet\API\Endpoints;
use \MailPoet\API\Endpoint as APIEndpoint;
use \MailPoet\API\Error as APIError;

if(!defined('ABSPATH')) exit;

class AutomatedLatestContent extends APIEndpoint {
  public $ALC;

  function __construct() {
    $this->ALC = new \MailPoet\Newsletter\AutomatedLatestContent();
  }

  function getPostTypes() {
    return $this->successResponse(
      get_post_types(array(), 'objects')
    );
  }

  function getTaxonomies($data = array()) {
    $post_type = (isset($data['postType'])) ? $data['postType'] : 'post';
    return $this->successResponse(
      get_object_taxonomies($post_type, 'objects')
    );
  }

  function getTerms($data = array()) {
    $taxonomies = (isset($data['taxonomies'])) ? $data['taxonomies'] : array();
    $search = (isset($data['search'])) ? $data['search'] : '';
    $limit = (isset($data['limit'])) ? (int)$data['limit'] : 10;
    $page = (isset($data['page'])) ? (int)$data['page'] : 1;

    return $this->successResponse(
      get_terms(
        $taxonomies,
        array(
          'hide_empty' => false,
          'search' => $search,
          'number' => $limit,
          'offset' => $limit * ($page - 1)
        )
      )
    );
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
