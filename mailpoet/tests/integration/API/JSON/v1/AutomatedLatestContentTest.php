<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\SuccessResponse;
use MailPoet\API\JSON\v1\AutomatedLatestContent;

class AutomatedLatestContentTest extends \MailPoetTest {
  /** @var AutomatedLatestContent */
  private $endpoint;

  public function _before() {
    parent::_before();

    $this->endpoint = $this->diContainer->get(AutomatedLatestContent::class);
  }

  public function testItGetsPostTypes() {
    $response = $this->endpoint->getPostTypes();
    expect($response->data)->notEmpty();
    foreach ($response->data as $postType) {
      expect($postType)->count(2);
      expect($postType['name'])->notEmpty();
      expect($postType['label'])->notEmpty();
    }
  }

  public function testItDoesNotGetPostTypesExludedFromSearch() {
    $response = $this->endpoint->getPostTypes();
    // WP's default post type 'revision' is excluded from search
    // https://codex.wordpress.org/Post_Types
    $revisionPostType = get_post_type_object('revision');
    assert($revisionPostType instanceof \WP_Post_Type);
    expect($revisionPostType->exclude_from_search)->true(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    expect(isset($response->data['revision']))->false();
  }

  public function testItGetTerms() {

    $response = $this->endpoint->getTerms(['taxonomies' => ['category']]);

    $this->assertInstanceOf(SuccessResponse::class, $response);
    $this->assertCount(1, $response->data);
    $this->assertSame('Uncategorized', $response->data['0']->name);
  }
}
