<?php declare(strict_types = 1);

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
    $this->assertInstanceOf(\WP_Post_Type::class, $revisionPostType);
    expect($revisionPostType->exclude_from_search)->true(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    expect(isset($response->data['revision']))->false();
  }

  public function testItGetTerms() {

    $response = $this->endpoint->getTerms(['taxonomies' => ['category']]);

    $this->assertInstanceOf(SuccessResponse::class, $response);
    $this->assertCount(1, $response->data);
    $this->assertSame('Uncategorized', $response->data['0']->name);
  }

  /**
   * @dataProvider dataForTestItGetsTransformedPostsWithDifferentStatus
   */
  public function testItGetsTransformedPostsWithDifferentStatus(string $status, string $type) {
    $currentUserId = wp_get_current_user()->ID;
    wp_set_current_user(1);

    $title = "testItGetsTransformedPosts test $status";
    $id = wp_insert_post([
      'post_title' => $title,
      'post_status' => $status,
      'post_author' => 1,
      'post_content' => 'This is a post to test something.',
      'post_date' => $status === 'future' ? gmdate('Y-m-d H:i:s', time() + 3600) : gmdate('Y-m-d H:i:s'),
    ]);
    $this->assertIsNumeric($id);

    $response = $this->endpoint->getTransformedPosts([
      'posts' => [$id],
      'postStatus' => $status,
      'type' => $type,
      'displayType' => 'excerpt',
      'titleFormat' => 'ul',
      'showDivider' => false,
      'imageFullWidth' => false,
      'readMoreType' => 'none',
      'titleIsLink' => false,
      'titleAlignment' => 'center',
      'featuredImagePosition' => 'belowTitle',
    ]);

    wp_delete_post($id, true);
    wp_set_current_user($currentUserId);
    $this->assertCount(1, $response->data, "Post \"$id\" with status  \"$status\" was not fetched properly.");
    $this->assertStringContainsString($title, $response->data[0]['text'], "Response for Post \"$id\" with status  \"$status\" did not contain the title.");
  }

  public function dataForTestItGetsTransformedPostsWithDifferentStatus() {
    $stati = ['future', 'draft', 'publish', 'pending', 'private'];
    $types = ['posts', 'products'];

    $data = [];
    foreach ($types as $type) {
      foreach ($stati as $status) {
        $data['status_' . $status . '_type_' . $type] = [
          'status' => $status,
          'type' => $type,
        ];
      }
    }
    return $data;
  }
}
