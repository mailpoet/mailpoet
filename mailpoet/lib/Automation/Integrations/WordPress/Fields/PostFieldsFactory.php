<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WordPress\Fields;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\WordPress;
use MailPoet\Automation\Integrations\WordPress\Payloads\PostPayload;

class PostFieldsFactory {

  /** @var WordPress */
  private $wp;

  public function __construct(
    WordPress $wp
  ) {
    $this->wp = $wp;
  }

  /**
   * @return Field[]
   */
  public function getFields(): array {
    return [
      new Field(
        'wordpress:post:id',
        Field::TYPE_INTEGER,
        __('ID', 'mailpoet'),
        function (PostPayload $payload) {
          return $payload->getPostId();
        }
      ),
      new Field(
        'wordpress:post:type',
        Field::TYPE_ENUM,
        __('Post type', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->post_type : '';
        },
        [
          'options' => $this->getPostTypes(),
        ]
      ),
      new Field(
        'wordpress:post:status',
        Field::TYPE_ENUM,
        __('Post status', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->post_status : '';
        },
        [
          'options' => $this->getPostStatuses(),
        ]
      ),
      new Field(
        'wordpress:post:content',
        Field::TYPE_STRING,
        __('Post Content', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->post_content : '';
        }
      ),
      new Field(
        'wordpress:post:title',
        Field::TYPE_STRING,
        __('Post title', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->post_title : '';
        }
      ),
      new Field(
        'wordpress:post:date',
        Field::TYPE_DATETIME,
        __('Post date', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->post_date_gmt : '';
        }
      ),
      new Field(
        'wordpress:post:modified',
        Field::TYPE_DATETIME,
        __('Post last modified', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->post_modified_gmt : '';
        }
      ),
      new Field(
        'wordpress:post:author',
        Field::TYPE_INTEGER,
        __('Post author ID', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->post_author : 0;
        }
      ),
      new Field(
        'wordpress:post:excerpt',
        Field::TYPE_STRING,
        __('Post excerpt', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->post_excerpt : '';
        }
      ),
      new Field(
        'wordpress:post:comment-status',
        Field::TYPE_BOOLEAN,
        __('Post open for comments', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->comment_status === 'open' : false;
        }
      ),
      new Field(
        'wordpress:post:ping-status',
        Field::TYPE_BOOLEAN,
        __('Post open for pings', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->ping_status === 'open' : false;
        }
      ),
      new Field(
        'wordpress:post:password',
        Field::TYPE_STRING,
        __('Post password', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->post_password : '';
        }
      ),
      new Field(
        'wordpress:post:slug',
        Field::TYPE_STRING,
        __('Post slug', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->post_name : '';
        }
      ),
      new Field(
        'wordpress:post:parent',
        Field::TYPE_INTEGER,
        __('Post parent ID', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->post_parent : 0;
        }
      ),
      new Field(
        'wordpress:post:has-parent',
        Field::TYPE_BOOLEAN,
        __('Post has parent', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->post_parent > 0 : false;
        }
      ),
      new Field(
        'wordpress:post:guid',
        Field::TYPE_STRING,
        __('Post guid', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          return $post ? $post->guid : '';
        }
      ),
      new Field(
        'wordpress:post:menu-order',
        Field::TYPE_INTEGER,
        __('Post menu order', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->menu_order : 0;
        }
      ),
      new Field(
        'wordpress:post:comment-count',
        Field::TYPE_INTEGER,
        __('Number of post comments', 'mailpoet'),
        function (PostPayload $payload) {
          $post = $payload->getPost();
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          return $post ? $post->comment_count : 0;
        }
      ),
    ];
  }

  private function getPostStatuses(): array {
    $statuses = $this->wp->getPostStatuses();
    return array_values(array_map(
      function($status, $index): array {
        return [
          'id' => $index,
          'name' => $status,
        ];
      },
      $statuses, array_keys($statuses)
    ));
  }

  private function getPostTypes(): array {
    /** @var \WP_Post_Type[] $postTypes */
    $postTypes = $this->wp->getPostTypes([], 'objects');
    return array_values(array_map(
      function(\WP_Post_Type $type): array {
        return [
          'id' => $type->name,
          'name' => $type->label,
        ];
      },
      array_filter(
        $postTypes,
        function(\WP_Post_Type $type): bool {
          return $type->public;
        })
    ));
  }
}
