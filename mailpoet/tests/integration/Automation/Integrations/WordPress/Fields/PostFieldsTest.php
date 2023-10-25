<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\WordPress\Fields;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\WordPress\Payloads\PostPayload;
use MailPoet\Automation\Integrations\WordPress\Subjects\PostSubject;

class PostFieldsTest extends \MailPoetTest {

  /** @var WordPress */
  private $wp;

  public function _before() {
    $this->wp = $this->diContainer->get(WordPress::class);
    add_filter('wp_insert_post_empty_content', '__return_false');
  }

  public function _after() {
    remove_filter('wp_insert_post_empty_content', '__return_false');
  }

  /**
   * @dataProvider dataForSimpleFields
   */
  public function testSimpleFields($fieldName, $name, $type, $postField, $expectation) {
    $fields = $this->getFieldsMap();

    $field = $fields[$fieldName];
    $this->assertSame($name, $field->getName());
    $this->assertSame($type, $field->getType());
    $this->assertSame([], $field->getArgs());

    $this->assertNull($field->getValue(new PostPayload(0, $this->wp)));
    $postId = wp_insert_post([
      $postField => $expectation,
    ]);
    $this->assertIsNumeric($postId);
    $this->assertSame($expectation, $field->getValue(new PostPayload($postId, $this->wp)));
  }

  public function dataForSimpleFields(): array {
    return [
      'wordpress:post:content' => [
        'field' => 'wordpress:post:content',
        'name' => 'Post Content',
        'type' => 'string',
        'postField' => 'post_content',
        'expectation' => 'This is the post content',
      ],
      'wordpress:post:title' => [
        'field' => 'wordpress:post:title',
        'name' => 'Post title',
        'type' => 'string',
        'postField' => 'post_title',
        'expectation' => 'This is the post title',
      ],
      'wordpress:post:author' => [
        'field' => 'wordpress:post:author',
        'name' => 'Post author ID',
        'type' => 'integer',
        'postField' => 'post_author',
        'expectation' => 1,
      ],
      'wordpress:post:excerpt' => [
        'field' => 'wordpress:post:excerpt',
        'name' => 'Post excerpt',
        'type' => 'string',
        'postField' => 'post_excerpt',
        'expectation' => 'This is the excerpt',
      ],
      'wordpress:post:password' => [
        'field' => 'wordpress:post:password',
        'name' => 'Post password',
        'type' => 'string',
        'postField' => 'post_password',
        'expectation' => 'password',
      ],
      'wordpress:post:slug' => [
        'field' => 'wordpress:post:slug',
        'name' => 'Post slug',
        'type' => 'string',
        'postField' => 'post_name',
        'expectation' => 'slug',
      ],
      'wordpress:post:menu-order' => [
        'field' => 'wordpress:post:menu-order',
        'name' => 'Post menu order',
        'type' => 'integer',
        'postField' => 'menu_order',
        'expectation' => 2,
      ],
    ];
  }

  public function testGuid() {
    $fields = $this->getFieldsMap();

    $field = $fields['wordpress:post:guid'];
    $this->assertSame('Post guid', $field->getName());
    $this->assertSame('string', $field->getType());
    $this->assertSame([], $field->getArgs());

    $expectation = 'http://guid';
    $this->assertNull($field->getValue(new PostPayload(0, $this->wp)));
    $postId = wp_insert_post([
      'guid' => 'guid',
    ]);
    $this->assertIsNumeric($postId);
    $this->assertSame($expectation, $field->getValue(new PostPayload($postId, $this->wp)));
  }

  public function testPostTypeField() {
    $fields = $this->getFieldsMap();

    $field = $fields['wordpress:post:type'];
    $this->assertSame('Post type', $field->getName());
    $this->assertSame('enum', $field->getType());
    $this->assertSame([
      'options' => [
        [
          'id' => 'post',
          'name' => 'Posts',
        ],
        [
          'id' => 'page',
          'name' => 'Pages',
        ],
        [
          'id' => 'attachment',
          'name' => 'Media',
        ],
        [
          'id' => 'mailpoet_page',
          'name' => 'MailPoet Page',
        ],
      ],
    ], $field->getArgs());

    $expectation = 'page';
    $this->assertNull($field->getValue(new PostPayload(0, $this->wp)));
    $postId = wp_insert_post([
      'post_type' => $expectation,
    ]);
    $this->assertIsNumeric($postId);
    $this->assertSame($expectation, $field->getValue(new PostPayload($postId, $this->wp)));
  }

  public function _testPostParentField() {

  }

  public function _testPostHasParentField() {

  }

  public function _testPostStatusField() {

  }

  public function _testPostDateField() {

  }

  public function _testPostModifiedField() {

  }

  public function _testPostCommentStatusField() {

  }

  public function _testPostPingStatusField() {

  }

  public function _testPostCommentCountField() {

  }

  /** @return array<string, Field> */
  private function getFieldsMap(): array {
    $factory = $this->diContainer->get(PostSubject::class);
    $fields = [];
    foreach ($factory->getFields() as $field) {
      $fields[$field->getKey()] = $field;
    }
    return $fields;
  }
}
