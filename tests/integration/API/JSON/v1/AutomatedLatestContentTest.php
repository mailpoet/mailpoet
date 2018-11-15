<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\v1\AutomatedLatestContent;

class AutomatedLatestContentTest extends \MailPoetTest {
  function testItGetsPostTypes() {
    $endpoint = new AutomatedLatestContent(new \MailPoet\Newsletter\AutomatedLatestContent());
    $response = $endpoint->getPostTypes();
    expect($response->data)->notEmpty();
    foreach($response->data as $post_type) {
      expect($post_type)->count(2);
      expect($post_type['name'])->notEmpty();
      expect($post_type['label'])->notEmpty();
    }
  }

  function testItDoesNotGetPostTypesExludedFromSearch() {
    $endpoint = new AutomatedLatestContent(new \MailPoet\Newsletter\AutomatedLatestContent());
    $response = $endpoint ->getPostTypes();
    // WP's default post type 'revision' is excluded from search
    // https://codex.wordpress.org/Post_Types
    $revision_post_type = get_post_type_object('revision');
    expect($revision_post_type->exclude_from_search)->true();
    expect(isset($response->data['revision']))->false();
  }
}
