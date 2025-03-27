<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\SuccessResponse;
use MailPoet\API\JSON\v1\DynamicProducts;

/**
 * @group woo
 */
class DynamicProductsTest extends \MailPoetTest {
  /** @var DynamicProducts */
  private $endpoint;

  public function _before() {
    parent::_before();

    $this->endpoint = $this->diContainer->get(DynamicProducts::class);
  }

  public function testItGetsTaxonomies() {
    $response = $this->endpoint->getTaxonomies();
    verify($response->data)->notEmpty();
    foreach ($response->data as $taxonomy) {
      verify($taxonomy->label)->notEmpty();
    }
  }

  public function testItGetTerms() {
    // Create a product category
    $termId = wp_insert_term('Test Product Category', 'product_cat');
    $this->assertIsArray($termId);

    $response = $this->endpoint->getTerms(['taxonomies' => ['product_cat']]);

    $this->assertInstanceOf(SuccessResponse::class, $response);
    $this->assertNotEmpty($response->data);

    // Clean up
    wp_delete_term($termId['term_id'], 'product_cat');
  }

  public function testItGetsTransformedProducts() {
    $currentUserId = wp_get_current_user()->ID;
    wp_set_current_user(1);

    // Create a test product using the tester
    $product = $this->tester->createWooCommerceProduct([
      'name' => 'TestProduct',
      'price' => '10.00',
    ]);

    $response = $this->endpoint->getTransformedProducts([
      'amount' => '1',
      'contentType' => 'product',
      'displayType' => 'excerpt',
      'titleFormat' => 'h1',
      'titleAlignment' => 'center',
      'titleIsLink' => false,
      'imageFullWidth' => false,
      'featuredImagePosition' => 'centered',
      'showAuthor' => 'no',
      'showCategories' => 'no',
      'readMoreType' => 'button',
      'readMoreButton' => [
        'type' => 'button',
        'text' => 'Read more',
        'url' => '[postLink]',
        'styles' => [
          'block' => [
            'backgroundColor' => '#e2973f',
            'borderColor' => '#e2973f',
            'borderWidth' => '0px',
            'borderRadius' => '5px',
            'borderStyle' => 'solid',
            'width' => '110px',
            'lineHeight' => '40px',
            'fontColor' => '#ffffff',
            'fontFamily' => 'Arial',
            'fontSize' => '14px',
            'fontWeight' => 'bold',
            'textAlign' => 'left',
          ],
        ],
      ],
      'sortBy' => 'newest',
      'showDivider' => false,
    ]);

    // No need to manually delete the product - tester will handle it
    wp_set_current_user($currentUserId);

    $this->assertInstanceOf(SuccessResponse::class, $response);
    $this->assertNotEmpty($response->data);
  }

  public function testItGetsBulkTransformedProducts() {
    $currentUserId = wp_get_current_user()->ID;
    wp_set_current_user(1);

    // Create test products using the tester
    $product1 = $this->tester->createWooCommerceProduct([
      'name' => 'TestProduct1',
      'price' => '10.00',
    ]);
    $product2 = $this->tester->createWooCommerceProduct([
      'name' => 'TestProduct2',
      'price' => '10.00',
    ]);

    $block = [
      'amount' => '2',
      'contentType' => 'product',
      'displayType' => 'excerpt',
      'titleFormat' => 'h1',
      'titleAlignment' => 'center',
      'titleIsLink' => false,
      'imageFullWidth' => false,
      'featuredImagePosition' => 'centered',
      'showAuthor' => 'no',
      'showCategories' => 'no',
      'readMoreType' => 'button',
      'readMoreButton' => [
        'type' => 'button',
        'text' => 'Read more',
        'url' => '[postLink]',
        'styles' => [
          'block' => [
            'backgroundColor' => '#e2973f',
            'borderColor' => '#e2973f',
            'borderWidth' => '0px',
            'borderRadius' => '5px',
            'borderStyle' => 'solid',
            'width' => '110px',
            'lineHeight' => '40px',
            'fontColor' => '#ffffff',
            'fontFamily' => 'Arial',
            'fontSize' => '14px',
            'fontWeight' => 'bold',
            'textAlign' => 'left',
          ],
        ],
      ],
      'sortBy' => 'newest',
      'showDivider' => false,
    ];

    $response = $this->endpoint->getBulkTransformedProducts([
      'blocks' => [$block, $block],
    ]);

    // No need to manually delete products - tester will handle it
    wp_set_current_user($currentUserId);

    $this->assertInstanceOf(SuccessResponse::class, $response);
    $this->assertCount(2, $response->data);

    $product1->delete(true);
    $product2->delete(true);
  }
}
