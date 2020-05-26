<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\v1\AutomatedLatestContent;

class AutomatedLatestContentTest extends \MailPoetTest {
  public function testItGetsPostTypes() {
    $endpoint = $this->diContainer->get(AutomatedLatestContent::class);
    $response = $endpoint->getPostTypes();
    expect($response->data)->notEmpty();
    foreach ($response->data as $postType) {
      expect($postType)->count(2);
      expect($postType['name'])->notEmpty();
      expect($postType['label'])->notEmpty();
    }
  }

  public function testItDoesNotGetPostTypesExludedFromSearch() {
    $endpoint = $this->diContainer->get(AutomatedLatestContent::class);
    $response = $endpoint ->getPostTypes();
    // WP's default post type 'revision' is excluded from search
    // https://codex.wordpress.org/Post_Types
    $revisionPostType = get_post_type_object('revision');
    expect($revisionPostType->exclude_from_search)->true(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    expect(isset($response->data['revision']))->false();
  }
}
