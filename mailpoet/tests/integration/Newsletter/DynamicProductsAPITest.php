<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter;

use MailPoet\API\JSON\v1\DynamicProducts as DynamicProductsAPI;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Newsletter\DynamicProducts;
use MailPoet\Util\APIPermissionHelper;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;

/**
 * @group woo
 */
class DynamicProductsAPITest extends \MailPoetTest {

  /*** @var array WP_User[] */
  private $createdUsers = [];

  /*** @var DynamicProductsAPI */
  private $dpAPI;

  /*** @var WPFunctions */
  private $wp;

  /*** @var WCHelper */
  private $wcHelper;

  private static $dpBlock = [
    'type' => 'dynamicProducts',
    'withLayout' => true,
    'amount' => '2',
    'contentType' => 'product',
    'terms' => [],
    'inclusionType' => 'include',
    'displayType' => 'excerpt',
    'titleFormat' => 'h2',
    'titleAlignment' => 'left',
    'titleIsLink' => false,
    'imageFullWidth' => true,
    'titlePosition' => 'abovePost',
    'featuredImagePosition' => 'left',
    'fullPostFeaturedImagePosition' => 'none',
    'showAuthor' => 'no',
    'authorPrecededBy' => 'Author:',
    'showCategories' => 'no',
    'categoriesPrecededBy' => 'Categories:',
    'readMoreType' => 'button',
    'readMoreText' => 'Read more',
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
      'context' => 'dynamicProducts.readMoreButton',
    ],
    'sortBy' => 'newest',
    'showDivider' => false,
    'divider' => [
      'type' => 'divider',
      'styles' => [
        'block' => [
          'backgroundColor' => 'transparent',
          'padding' => '13px',
          'borderStyle' => 'solid',
          'borderWidth' => '3px',
          'borderColor' => '#aaaaaa',
        ],
      ],
      'context' => 'dynamicProducts.divider',
    ],
    'backgroundColor' => '#ffffff',
    'backgroundColorAlternate' => '#eeeeee',
  ];

  public function _before() {
    parent::_before();
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->wcHelper = $this->diContainer->get(WCHelper::class);
    $loggerFactory = $this->diContainer->get(LoggerFactory::class);

    $dp = $this->make($this->diContainer->get(DynamicProducts::class), [
      "loggerFactory" => $loggerFactory,
      "wp" => $this->wp,
      "wcHelper" => $this->wcHelper,
      "transformPosts" => function ($block, $posts) {
        return $posts;
      },
    ]);
    $apiPermissionHelper = $this->diContainer->get(APIPermissionHelper::class);
    $this->dpAPI = new DynamicProductsAPI($dp, $apiPermissionHelper, $this->wp);

    if (is_multisite()) {
      // switch to the first blog in a network install, this should be removed when we add full support for MU
      switch_to_blog(1);
    }
  }

  public function _after() {
    parent::_after();

    // we've switched to blog_id=1
    if (is_multisite()) {
      restore_current_blog();
    }

    foreach ($this->createdUsers as $user) {
      wp_delete_user($user->ID);
    }
  }

  private function loginWithRole(string $role): \WP_User {
    $username = uniqid("testUser");
    $email = "$username@test.com";
    $existingUser = $this->wp->getUserBy("email", $email);

    if ($existingUser) {
      wp_delete_user($existingUser->ID);
    }

    wp_insert_user([
      'user_login' => $username,
      'user_email' => $email,
      'user_pass' => '',
    ]);
    $user = $this->wp->getUserBy("email", $email);

    $user->add_role($role);

    wp_set_current_user($user->ID);
    $this->createdUsers[] = $user;

    return $user;
  }

  public function testGetBulkTransformedProducts() {
    $publishedProductTitle = 'Published Product';

    // Create a private product using the tester
    $privateProduct = $this->tester->createWooCommerceProduct([
      'name' => 'Private Product',
      'status' => 'private',
      'price' => '10.00',
    ]);

    // Create a published product using the tester
    $publishedProduct = $this->tester->createWooCommerceProduct([
      'name' => $publishedProductTitle,
      'status' => 'publish',
      'price' => '10.00',
    ]);

    $singleBlockQuery = array_merge(self::$dpBlock, ['postStatus' => "any"]);
    $result = $this->dpAPI->getBulkTransformedProducts([
      "blocks" => [$singleBlockQuery],
    ]);
    verify($result->data)->arrayCount(1);

    // Published products should be visible to anyone
    verify(count($result->data[0]))->equals(1);
    verify($result->data[0][0]->get_name())->equals($publishedProductTitle);

    $this->loginWithRole("editor");
    $result = $this->dpAPI->getBulkTransformedProducts([
      "blocks" => [$singleBlockQuery],
    ]);
    verify(count($result->data[0]))->equals(1);
    verify($result->data[0][0]->get_name())->equals($publishedProductTitle);

    $this->loginWithRole("administrator");
    $result = $this->dpAPI->getBulkTransformedProducts([
      "blocks" => [$singleBlockQuery],
    ]);
    verify(count($result->data[0]))->equals(1);
    verify($result->data[0][0]->get_name())->equals($publishedProductTitle);
  }

  /**
   * @param \WC_Product[] $products
   * @return string[]
   */
  private function getProductNames($products): array {
    return array_map(function (\WC_Product $product) {
      return $product->get_name();
    }, $products);
  }

  public function testGetProducts() {
    // Create a private product using the tester
    $privateProduct = $this->tester->createWooCommerceProduct([
      'name' => 'Private Product',
      'status' => 'private',
      'price' => '10.00',
    ]);

    // Create a published product using the tester
    $publishedProduct = $this->tester->createWooCommerceProduct([
      'name' => 'Published Product',
      'status' => 'publish',
      'price' => '10.00',
    ]);

    // Create a draft product using the tester
    $draftProduct = $this->tester->createWooCommerceProduct([
      'name' => 'Draft Product',
      'status' => 'draft',
      'price' => '10.00',
    ]);

    $result = $this->dpAPI->getProducts(['postStatus' => "any", "contentType" => "product"]);
    verify($result->data)->arrayCount(1);
    verify($this->getProductNames($result->data))->arrayContains('Published Product');

    $this->loginWithRole("editor");
    $result = $this->dpAPI->getProducts(['postStatus' => "any", "contentType" => "product"]);
    verify($result->data)->arrayCount(2);
    verify($this->getProductNames($result->data))->arrayContains('Published Product');
    verify($this->getProductNames($result->data))->arrayContains('Private Product');

    $user = $this->loginWithRole("administrator");
    if (is_multisite()) {
      grant_super_admin($user->ID);
    }

    $result = $this->dpAPI->getProducts(['postStatus' => "any", "contentType" => "product"]);
    verify($result->data)->arrayCount(3);
    verify($this->getProductNames($result->data))->arrayContains('Published Product');
    verify($this->getProductNames($result->data))->arrayContains('Private Product');
    verify($this->getProductNames($result->data))->arrayContains('Draft Product');
  }
}
