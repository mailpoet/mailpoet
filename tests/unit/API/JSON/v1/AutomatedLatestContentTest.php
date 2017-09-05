<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\v1\AutomatedLatestContent;

class AutomatedLatestContentTest extends \MailPoetTest {
  function testItGetsPostTypes() {
    $router = new AutomatedLatestContent();
    $response = $router->getPostTypes();
    expect($response->data)->notEmpty();
    foreach($response->data as $post_type) {
      expect($post_type)->count(2);
      expect($post_type['name'])->notEmpty();
      expect($post_type['label'])->notEmpty();
    }
  }
}
