<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter;

use MailPoet\Newsletter\AutomatedLatestContent;
use MailPoet\Newsletter\BlockPostQuery;

class AutomatedLatestContentTest extends \MailPoetTest {
  /** @var AutomatedLatestContent */
  public $alc;

  /** @var int[] */
  private $postIds = [];

  public function _before() {
    parent::_before();
    $this->alc = $this->diContainer->get(AutomatedLatestContent::class);
  }

  public function _after() {
    foreach ($this->postIds as $postId) {
      wp_delete_post($postId, true);
    }
    parent::_after();
  }

  public function testItCategorizesTermsToTaxonomies() {
    $args = [
      'terms' => [
        [
          'id' => 1,
          'taxonomy' => 'post_tag',
        ],
        [
          'id' => 2,
          'taxonomy' => 'product_tag',
        ],
        [
          'id' => 3,
          'taxonomy' => 'post_tag',
        ],
      ],
      'inclusionType' => 'include',
    ];

    $query = new BlockPostQuery(['args' => $args]);
    verify($query->getQueryParams()['tax_query'])->equals([
      [
        [
          'taxonomy' => 'post_tag',
          'field' => 'id',
          'terms' => [1, 3],
        ],
        [
          'taxonomy' => 'product_tag',
          'field' => 'id',
          'terms' => [2],
        ],
        'relation' => 'OR',
      ],
    ]);
  }

  public function testItCanExcludeTaxonomies() {
    $args = [
      'terms' => [
        [
          'id' => 7,
          'taxonomy' => 'post_tag',
        ],
        [
          'id' => 8,
          'taxonomy' => 'post_tag',
        ],
      ],
      'inclusionType' => 'exclude',
    ];

    $query = (new BlockPostQuery(['args' => $args]))->getQueryParams()['tax_query'];

    verify($query[0][0]['operator'])->equals('NOT IN');
    verify($query[0]['relation'])->equals('AND');
  }

  public function testItFindsNoPostsForAnUnlistedType() {
    $postId = wp_insert_post([
      'post_title' => 'Reusable block',
      'post_status' => 'publish',
      'post_type' => 'wp_block',
    ]);
    $this->postIds = [$postId, wp_insert_post([
      'post_title' => 'Published post',
      'post_status' => 'publish',
    ])];

    $posts = $this->alc->getPosts(new BlockPostQuery(['args' => ['contentType' => 'wp_block']]));
    $manualPosts = $this->alc->getPosts(new BlockPostQuery(['args' => ['contentType' => 'wp_block', 'posts' => [$postId]]]));

    verify($posts)->arrayCount(0);
    verify($manualPosts)->arrayCount(0);
  }

  public function testItKeepsTheTypesTheEditorLists() {
    foreach (['post', 'page'] as $postType) {
      $params = (new BlockPostQuery(['args' => ['contentType' => $postType]]))->getQueryParams();
      verify($params['post_type'])->equals($postType);
      verify($params)->arrayHasNotKey('post__in');
    }
  }

  /**
   * @group woo
   */
  public function testItKeepsProducts() {
    $params = (new BlockPostQuery(['args' => ['contentType' => 'product']]))->getQueryParams();
    verify($params['post_type'])->equals('product');
    verify($params)->arrayHasNotKey('post__in');
  }
}
