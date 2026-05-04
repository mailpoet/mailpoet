<?php declare(strict_types = 1);

namespace MailPoet\API\JSON\v1;

use MailPoet\API\JSON\Endpoint as APIEndpoint;
use MailPoet\API\JSON\SuccessResponse;
use MailPoet\Config\AccessControl;
use MailPoet\WooCommerce\Helper;
use WC_Product_Variable;
use WC_Product_Variation;

class WoocommerceProductVariations extends APIEndpoint {
  /** @var Helper */
  private $wooHelper;

  public $permissions = [
    'global' => AccessControl::PERMISSION_MANAGE_SEGMENTS,
  ];

  public function __construct(
    Helper $wooHelper
  ) {
    $this->wooHelper = $wooHelper;
  }

  public function getVariations(array $data = []): SuccessResponse {
    $emptyResponse = ['product' => null, 'variations' => []];
    if (!$this->wooHelper->isWooCommerceActive()) {
      return $this->successResponse($emptyResponse);
    }

    $product = null;
    if (isset($data['product_id'])) {
      $candidate = $this->wooHelper->wcGetProduct((int)$data['product_id']);
      if ($candidate instanceof WC_Product_Variable) {
        $product = $candidate;
      }
    } elseif (isset($data['variation_id'])) {
      $variation = $this->wooHelper->wcGetProduct((int)$data['variation_id']);
      if ($variation instanceof WC_Product_Variation) {
        $parent = $this->wooHelper->wcGetProduct($variation->get_parent_id());
        if ($parent instanceof WC_Product_Variable) {
          $product = $parent;
        }
      }
    }

    if (!$product instanceof WC_Product_Variable) {
      return $this->successResponse($emptyResponse);
    }

    $variations = [];
    foreach ($product->get_children() as $variationId) {
      $variation = $this->wooHelper->wcGetProduct($variationId);
      if (!$variation instanceof WC_Product_Variation) {
        continue;
      }
      $attributesSummary = $this->wooHelper->wcGetFormattedVariation($variation, true);
      $variations[] = [
        'id' => (string)$variation->get_id(),
        'name' => $attributesSummary !== '' ? $attributesSummary : $variation->get_name(),
      ];
    }

    return $this->successResponse([
      'product' => [
        'id' => (string)$product->get_id(),
        'name' => $product->get_name(),
      ],
      'variations' => $variations,
    ]);
  }
}
