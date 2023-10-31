<?php declare(strict_types = 1);

namespace integration\Automation\Integrations\WordPress\Fields;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\WordPress\Payloads\CommentPayload;
use MailPoet\Automation\Integrations\WordPress\Subjects\CommentSubject;

class CommentFieldsTest extends \MailPoetTest {

  /** @var WordPress */
  private $wp;

  public function _before() {
    $this->wp = $this->diContainer->get(WordPress::class);
  }

  public function testCommentIdField() {
    $fields = $this->getFieldsMap();

    $field = $fields['wordpress:comment:id'];
    $this->assertSame('Comment ID', $field->getName());
    $this->assertSame('integer', $field->getType());
    $this->assertSame([], $field->getArgs());

    $commentId = wp_insert_comment([]);
    $this->assertNotFalse($commentId);
    $this->assertSame($commentId, $field->getValue(new CommentPayload($commentId, $this->wp)));
  }

  /**
   * @dataProvider dataForSimpleFields
   */
  public function testSimpleFields($fieldName, $name, $type, $commentField, $expectation) {
    $fields = $this->getFieldsMap();

    $field = $fields[$fieldName];
    $this->assertSame($name, $field->getName());
    $this->assertSame($type, $field->getType());
    $this->assertSame([], $field->getArgs());

    $this->assertNull($field->getValue(new CommentPayload(0, $this->wp)));
    $commentId = wp_insert_comment([
      $commentField => $expectation,
    ]);
    $this->assertNotFalse($commentId);
    $this->assertSame($expectation, $field->getValue(new CommentPayload($commentId, $this->wp)));
  }

  public function dataForSimpleFields(): array {
    return [
      'wordpress:comment:author-name' => [
        'field' => 'wordpress:comment:author-name',
        'name' => 'Comment author name',
        'type' => 'string',
        'commentField' => 'comment_author',
        'expectation' => 'John Doe',
      ],
      'wordpress:comment:author-email' => [
        'field' => 'wordpress:comment:author-email',
        'name' => 'Comment author email',
        'type' => 'string',
        'commentField' => 'comment_author_email',
        'expectation' => 'john.doe@example.com',
      ],
      'wordpress:comment:author-url' => [
        'field' => 'wordpress:comment:author-url',
        'name' => 'Comment author URL',
        'type' => 'string',
        'commentField' => 'comment_author_url',
        'expectation' => 'https://johndoe.com',
      ],
      'wordpress:comment:author-ip' => [
        'field' => 'wordpress:comment:author-ip',
        'name' => 'Comment author IP',
        'type' => 'string',
        'commentField' => 'comment_author_IP',
        'expectation' => '127.0.0.1',
      ],
      'wordpress:comment:content' => [
        'field' => 'wordpress:comment:content',
        'name' => 'Comment content',
        'type' => 'string',
        'commentField' => 'comment_content',
        'expectation' => 'the comment content',
      ],
      'wordpress:comment:karma' => [
        'field' => 'wordpress:comment:karma',
        'name' => 'Comment karma',
        'type' => 'integer',
        'commentField' => 'comment_karma',
        'expectation' => 1,
      ],
      'wordpress:comment:comment-agent' => [
        'field' => 'wordpress:comment:comment-agent',
        'name' => 'Comment user agent',
        'type' => 'string',
        'commentField' => 'comment_agent',
        'expectation' => 'The user agent',
      ],
      'wordpress:comment:comment-type' => [
        'field' => 'wordpress:comment:comment-type',
        'name' => 'Comment type',
        'type' => 'string',
        'commentField' => 'comment_type',
        'expectation' => 'type',
      ],
      'wordpress:comment:comment-parent' => [
        'field' => 'wordpress:comment:comment-parent',
        'name' => 'Comment parent ID',
        'type' => 'integer',
        'commentField' => 'comment_parent',
        'expectation' => 1,
      ],
    ];
  }

  public function testDateField() {
    $fields = $this->getFieldsMap();

    $field = $fields['wordpress:comment:date'];
    $this->assertSame('Comment date', $field->getName());
    $this->assertSame('datetime', $field->getType());
    $this->assertSame([], $field->getArgs());
    $this->assertNull($field->getValue(new CommentPayload(0, $this->wp)));

    $commentId = wp_insert_comment([]);
    $this->assertNotFalse($commentId);

    $comment = \WP_Comment::get_instance($commentId);
    $this->assertNotFalse($comment);
    $this->assertSame($comment->comment_date_gmt, $field->getValue(new CommentPayload($commentId, $this->wp))); //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testStatusField() {
    $fields = $this->getFieldsMap();

    $field = $fields['wordpress:comment:status'];
    $this->assertSame('Comment status', $field->getName());
    $this->assertSame('enum', $field->getType());
    $this->assertSame([
      'options' => [
        [
          'id' => 'hold',
          'name' => 'Unapproved',
        ],
        [
          'id' => 'approve',
          'name' => 'Approved',
        ],
        [
          'id' => 'spam',
          'name' => 'Spam',
        ],
        [
          'id' => 'trash',
          'name' => 'Trash',
        ],
      ],
    ], $field->getArgs());

    $this->assertNull($field->getValue(new CommentPayload(0, $this->wp)));
    $commentId = wp_insert_comment([
      'comment_approved' => 0,
    ]);
    $this->assertNotFalse($commentId);
    $this->assertSame('hold', $field->getValue(new CommentPayload($commentId, $this->wp)));

    wp_update_comment([
      'comment_ID' => $commentId,
      'comment_approved' => '1',
    ]);
    $this->assertSame('approve', $field->getValue(new CommentPayload($commentId, $this->wp)));

    wp_update_comment([
      'comment_ID' => $commentId,
      'comment_approved' => 'spam',
    ]);
    $this->assertSame('spam', $field->getValue(new CommentPayload($commentId, $this->wp)));

    wp_update_comment([
      'comment_ID' => $commentId,
      'comment_approved' => 'trash',
    ]);
    $this->assertSame('trash', $field->getValue(new CommentPayload($commentId, $this->wp)));
  }

  public function testHasChildrenField() {
    $fields = $this->getFieldsMap();

    $field = $fields['wordpress:comment:has-children'];
    $this->assertSame('Comment has replies', $field->getName());
    $this->assertSame('boolean', $field->getType());
    $this->assertSame([], $field->getArgs());

    $this->assertFalse($field->getValue(new CommentPayload(0, $this->wp)));
    $commentId = wp_insert_comment([
      'comment_post_ID' => 1,
    ]);
    $this->assertNotFalse($commentId);
    $comment = get_comment($commentId);
    $this->assertInstanceOf(\WP_Comment::class, $comment);
    $this->assertFalse($field->getValue(new CommentPayload($commentId, $this->wp)));

    $childId = wp_insert_comment([
      'comment_parent' => $commentId,
      'comment_post_ID' => 1,
    ]);
    $this->assertNotFalse($childId);
    $this->assertTrue($field->getValue(new CommentPayload($commentId, $this->wp)));
  }

  /** @return array<string, Field> */
  private function getFieldsMap(): array {
    $factory = $this->diContainer->get(CommentSubject::class);
    $fields = [];
    foreach ($factory->getFields() as $field) {
      $fields[$field->getKey()] = $field;
    }
    return $fields;
  }
}
