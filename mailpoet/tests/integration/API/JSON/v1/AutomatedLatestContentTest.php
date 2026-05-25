<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\SuccessResponse;
use MailPoet\API\JSON\v1\AutomatedLatestContent as AutomatedLatestContentEndpoint;
use MailPoet\Newsletter\AutomatedLatestContent;
use MailPoet\WP\Functions as WPFunctions;

class AutomatedLatestContentTest extends \MailPoetTest {
  /** @var AutomatedLatestContentEndpoint */
  private $endpoint;

  /** @var WPFunctions */
  private $wp;

  public function _before() {
    parent::_before();

    $this->endpoint = $this->diContainer->get(AutomatedLatestContentEndpoint::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
  }

  public function testItGetsPostTypes() {
    $response = $this->endpoint->getPostTypes();
    verify($response->data)->notEmpty();
    foreach ($response->data as $postType) {
      verify($postType)->arrayCount(2);
      verify($postType['name'])->notEmpty();
      verify($postType['label'])->notEmpty();
    }
  }

  public function testItDoesNotGetPostTypesExludedFromSearch() {
    $response = $this->endpoint->getPostTypes();
    // WP's default post type 'revision' is excluded from search
    // https://codex.wordpress.org/Post_Types
    $revisionPostType = get_post_type_object('revision');
    $this->assertInstanceOf(\WP_Post_Type::class, $revisionPostType);
    verify($revisionPostType->exclude_from_search)->true(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    verify(isset($response->data['revision']))->false();
  }

  public function testItGetTerms() {

    $response = $this->endpoint->getTerms(['taxonomies' => ['category']]);

    $this->assertInstanceOf(SuccessResponse::class, $response);
    $this->assertCount(1, $response->data);
    $this->assertSame('Uncategorized', $response->data['0']->name);
  }

  public function testItPrependsParentNameToHierarchicalTerms() {
    $parent = $this->createCategory('Music');
    $child = $this->createCategory('Rock', (int)$parent['term_id']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    try {
      $response = $this->endpoint->getTerms(['taxonomies' => ['category']]);
      $names = array_column((array)$response->data, 'name');
      $this->assertContains('Music', $names);
      $this->assertContains('Music > Rock', $names);
      $this->assertNotContains('Rock', $names);
    } finally {
      wp_delete_term((int)$child['term_id'], 'category'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      wp_delete_term((int)$parent['term_id'], 'category'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }
  }

  public function testItAppliesSearchTermsArgsFilterToParentLookup() {
    $parent = $this->createCategory('FilterMusic');
    $child = $this->createCategory('FilterRock', (int)$parent['term_id']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $parentId = (int)$parent['term_id']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    $filter = function ($args) use ($parentId) {
      // get_terms() ignores `exclude` when `include` is set, so suppress the
      // parent lookup by replacing `include` with an ID that cannot exist.
      if (!empty($args['include']) && in_array($parentId, (array)$args['include'], true)) {
        $args['include'] = [PHP_INT_MAX];
      }
      return $args;
    };

    $this->wp->addFilter('mailpoet_search_terms_args', $filter);
    try {
      $response = $this->endpoint->getTerms(['taxonomies' => ['category']]);
      $names = array_column((array)$response->data, 'name');
      $this->assertContains('FilterRock', $names);
      $this->assertNotContains('FilterMusic > FilterRock', $names);
    } finally {
      $this->wp->removeFilter('mailpoet_search_terms_args', $filter);
      wp_delete_term((int)$child['term_id'], 'category'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      wp_delete_term((int)$parent['term_id'], 'category'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }
  }

  public function testItDoesNotMutateCachedTermObjects() {
    $parent = $this->createCategory('CacheMusic');
    $child = $this->createCategory('CacheRock', (int)$parent['term_id']); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $childId = (int)$child['term_id']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    try {
      $this->endpoint->getTerms(['taxonomies' => ['category']]);

      $cachedTerm = get_term($childId, 'category');
      $this->assertInstanceOf(\WP_Term::class, $cachedTerm);
      $this->assertSame('CacheRock', $cachedTerm->name);
    } finally {
      wp_delete_term($childId, 'category');
      wp_delete_term((int)$parent['term_id'], 'category'); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }
  }

  private function createCategory(string $name, ?int $parentId = null): array {
    $args = [];
    if ($parentId) {
      $args['parent'] = $parentId;
    }
    $term = wp_insert_term($name, 'category', $args);
    if (is_wp_error($term)) {
      $this->fail('Failed to create category "' . $name . '": ' . $term->get_error_message());
    }
    return $term;
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

  public function testItAppliesPostFilterWhenGettingBulkTransformedPosts() {
    $postId = wp_insert_post([
      'post_title' => 'Original ALC title',
      'post_status' => 'publish',
      'post_author' => 1,
      'post_content' => 'Original ALC content',
    ]);
    $this->assertIsNumeric($postId);

    $receivedOriginalPost = null;
    $receivedArgs = null;
    $filter = function($post, $originalPost, $args) use (&$receivedOriginalPost, &$receivedArgs) {
      $receivedOriginalPost = $originalPost;
      $receivedArgs = $args;
      $post->post_title = 'Filtered ALC title'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $post->post_content = 'Filtered ALC content'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return $post;
    };

    $this->wp->addFilter(AutomatedLatestContent::FILTER_POST, $filter, 10, 3);
    try {
      $response = $this->endpoint->getBulkTransformedPosts([
        'blocks' => [$this->getAutomatedLatestContentBlock((int)$postId)],
      ]);
    } finally {
      $this->wp->removeFilter(AutomatedLatestContent::FILTER_POST, $filter);
      wp_delete_post($postId, true);
    }

    $encodedResponse = json_encode($response->data);
    $this->assertIsString($encodedResponse);
    $this->assertStringContainsString('Filtered ALC title', $encodedResponse);
    $this->assertStringContainsString('Filtered ALC content', $encodedResponse);
    $this->assertStringNotContainsString('Original ALC title', $encodedResponse);
    $this->assertStringNotContainsString('Original ALC content', $encodedResponse);
    $this->assertInstanceOf(\WP_Post::class, $receivedOriginalPost);
    $this->assertIsArray($receivedArgs);
    $this->assertSame((int)$postId, $receivedOriginalPost->ID);
    $this->assertSame('automatedLatestContentLayout', $receivedArgs['type']);
  }

  private function getAutomatedLatestContentBlock(int $postId): array {
    return [
      'type' => 'automatedLatestContentLayout',
      'withLayout' => true,
      'amount' => '1',
      'posts' => [$postId],
      'contentType' => 'post',
      'terms' => [],
      'inclusionType' => 'include',
      'displayType' => 'full',
      'titleFormat' => 'h2',
      'titleAlignment' => 'left',
      'titleIsLink' => false,
      'imageFullWidth' => false,
      'titlePosition' => 'abovePost',
      'featuredImagePosition' => 'none',
      'fullPostFeaturedImagePosition' => 'none',
      'showAuthor' => 'no',
      'authorPrecededBy' => 'Author:',
      'showCategories' => 'no',
      'categoriesPrecededBy' => 'Categories:',
      'readMoreType' => 'none',
      'sortBy' => 'newest',
      'showDivider' => false,
      'backgroundColor' => '#ffffff',
      'backgroundColorAlternate' => '#eeeeee',
    ];
  }
}
