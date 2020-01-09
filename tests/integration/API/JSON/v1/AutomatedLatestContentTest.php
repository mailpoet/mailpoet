<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\v1\AutomatedLatestContent;
use MailPoet\WP\Functions as WPFunctions;

class AutomatedLatestContentTest extends \MailPoetTest {
  public function testItGetsPostTypes() {
    $endpoint = new AutomatedLatestContent(new \MailPoet\Newsletter\AutomatedLatestContent(), new WPFunctions);
    $response = $endpoint->getPostTypes();
    expect($response->data)->notEmpty();
    foreach ($response->data as $postType) {
      expect($postType)->count(2);
      expect($postType['name'])->notEmpty();
      expect($postType['label'])->notEmpty();
    }
  }

  public function testItDoesNotGetPostTypesExludedFromSearch() {
    $endpoint = new AutomatedLatestContent(new \MailPoet\Newsletter\AutomatedLatestContent(), new WPFunctions);
    $response = $endpoint ->getPostTypes();
    // WP's default post type 'revision' is excluded from search
    // https://codex.wordpress.org/Post_Types
    $revisionPostType = get_post_type_object('revision');
    expect($revisionPostType->excludeFromSearch)->true();
    expect(isset($response->data['revision']))->false();
  }
}
