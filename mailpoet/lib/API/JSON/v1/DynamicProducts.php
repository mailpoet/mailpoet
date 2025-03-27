<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\SuccessResponse;
use MailPoet\Config\AccessControl;
use MailPoet\Newsletter\BlockPostQuery;
use MailPoet\Newsletter\DynamicProducts as DP;
use MailPoet\Util\APIPermissionHelper;
use MailPoet\WP\Functions as WPFunctions;

class DynamicProducts extends APIEndpoint {
  /** @var DP  */
  public $dynamicProducts;

  /** @var WPFunctions */
  private $wp;

  /** @var APIPermissionHelper */
  private $permissionHelper;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_EMAILS,
  ];

  public function __construct(
    DP $dynamicProducts,
    APIPermissionHelper $permissionHelper,
    WPFunctions $wp
  ) {
    $this->dynamicProducts = $dynamicProducts;
    $this->wp = $wp;
    $this->permissionHelper = $permissionHelper;
  }

  public function getTaxonomies($data = []) {
    $postType = 'product';
    $allTaxonomies = WPFunctions::get()->getObjectTaxonomies($postType, 'objects');
    $taxonomiesWithLabel = array_filter($allTaxonomies, function($taxonomy) {
      return $taxonomy->label;
    });
    return $this->successResponse($taxonomiesWithLabel);
  }

  public function getTerms($data = []) {
    $taxonomies = (isset($data['taxonomies'])) ? $data['taxonomies'] : [];
    $search = (isset($data['search'])) ? $data['search'] : '';
    $limit = (isset($data['limit'])) ? (int)$data['limit'] : 100;
    $page = (isset($data['page'])) ? (int)$data['page'] : 1;
    $args = [
      'taxonomy' => $taxonomies,
      'hide_empty' => false,
      'search' => $search,
      'number' => $limit,
      'offset' => $limit * ($page - 1),
      'orderby' => 'name',
      'order' => 'ASC',
    ];

    $args = (array)$this->wp->applyFilters('mailpoet_search_terms_args', $args);
    $terms = WPFunctions::get()->getTerms($args);

    return $this->successResponse(array_values($terms));
  }

  /**
   * Fetches products for Products static block
   */
  public function getProducts(array $data = []): SuccessResponse {
    return $this->successResponse(
      $this->getPermittedProducts($this->dynamicProducts->getPosts(new BlockPostQuery(['args' => $data, 'dynamic' => false])))
    );
  }

  /**
   * Fetches products for Dynamic Products block
   */
  public function getTransformedProducts(array $data = []): SuccessResponse {
    $products = $this->getPermittedProducts($this->dynamicProducts->getPosts(new BlockPostQuery([
      'args' => $data,
      'dynamic' => true,
    ])));
    return $this->successResponse(
      $this->dynamicProducts->transformPosts($data, $products)
    );
  }

  /**
   * Fetches products for multiple Dynamic Products blocks
   */
  public function getBulkTransformedProducts(array $data = []): SuccessResponse {
    $usedProducts = [];
    $renderedProducts = [];

    foreach ($data['blocks'] as $block) {
      $query = new BlockPostQuery(['args' => $block, 'postsToExclude' => $usedProducts]);
      $products = $this->getPermittedProducts($this->dynamicProducts->getPosts($query));
      $renderedProducts[] = $this->dynamicProducts->transformPosts($block, $products);

      foreach ($products as $product) {
        $usedProducts[] = $product->get_id();
      }
    }

    return $this->successResponse($renderedProducts);
  }

  /**
   * @param \WC_Product[] $products
   * @return \WC_Product[]
   */
  private function getPermittedProducts($products) {
    return array_filter($products, function ($product) {
      return $this->permissionHelper->checkReadPermission($product);
    });
  }
}
