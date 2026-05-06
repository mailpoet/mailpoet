<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\v1\AutomatedLatestContent as AutomatedLatestContentEndpoint;
use MailPoet\Newsletter\AutomatedLatestContent;
use MailPoet\WP\Functions as WPFunctions;

class AutomatedLatestContentReproductionTest extends \MailPoetTest {
  /** @var AutomatedLatestContentEndpoint */
  private $endpoint;

  /** @var WPFunctions */
  private $wp;

  public function _before() {
    parent::_before();
    $this->endpoint = $this->diContainer->get(AutomatedLatestContentEndpoint::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
  }

  public function testItFallsBackToOriginalPostWhenFilterReturnsNull() {
    $postId = wp_insert_post([
      'post_title' => 'Original title',
      'post_status' => 'publish',
      'post_author' => 1,
      'post_content' => 'Original content',
    ]);
    $this->assertIsNumeric($postId);

    $filter = function() {
      return null;
    };

    $this->wp->addFilter(AutomatedLatestContent::FILTER_POST, $filter);
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
    $this->assertStringContainsString('Original title', $encodedResponse);
    $this->assertStringContainsString('Original content', $encodedResponse);
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
