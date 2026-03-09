<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Patterns;

use Automattic\WooCommerce\EmailEditor\Engine\Patterns\Abstract_Pattern;
use MailPoet\Util\CdnAssetUrl;

abstract class Pattern extends Abstract_Pattern {
  protected CdnAssetUrl $cdnAssetUrl;
  protected $namespace = 'mailpoet';

  public function __construct(
    CdnAssetUrl $cdnAssetUrl
  ) {
    $this->cdnAssetUrl = $cdnAssetUrl;
  }

  /**
   * Get the content to use when creating an email from this pattern.
   * Override in subclasses to provide different insertion content than preview.
   * By default, returns the same as get_content().
   */
  public function get_email_content(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return $this->get_content();
  }

  /**
   * @param string[] $imageNames CDN filenames for placeholder product images.
   */
  protected function getProductPlaceholderBlocks(array $imageNames): string {
    $blocks = '';
    foreach ($imageNames as $imageName) {
      $blocks .= $this->getProductPlaceholderCard($imageName);
    }
    return $blocks;
  }

  /**
   * Render placeholder products in a two-column grid.
   *
   * Uses 10px padding per column side (20px total gap) to match the WooCommerce
   * email renderer's two-column grid (see class-product-collection.php).
   * Pixel values are used instead of spacing presets for email client compatibility.
   *
   * @param string[] $imageNames CDN filenames for placeholder product images. Should contain an even number of items.
   */
  protected function getProductPlaceholderColumns(array $imageNames): string {
    $rows = array_chunk($imageNames, 2);
    $blocks = '';

    foreach ($rows as $row) {
      $cols = '';
      foreach ($row as $index => $imageName) {
        $isFirst = $index === 0;
        $padding = $isFirst ? 'right' : 'left';
        $style = 'padding-' . $padding . ':10px';
        $cols .= '
        <!-- wp:column {"style":{"spacing":{"padding":{"' . $padding . '":"10px"}}}} -->
        <div class="wp-block-column" style="' . $style . '">' . $this->getProductPlaceholderCard($imageName) . '</div>
        <!-- /wp:column -->';
      }

      $blocks .= '
      <!-- wp:columns -->
      <div class="wp-block-columns">' . $cols . '</div>
      <!-- /wp:columns -->';
    }

    return $blocks;
  }

  /**
   * Get a product collection block for recommended products.
   *
   * @param string $collection Collection slug (e.g., 'best-sellers', 'new-arrivals').
   * @param string $orderBy Order by field (e.g., 'date', 'popularity').
   * @param int $perPage Number of products to show.
   * @param int $columns Number of columns in the grid.
   */
  protected function getRecommendedProductCollectionBlock(string $collection, string $orderBy = 'date', int $perPage = 4, int $columns = 2): string {
    return '
      <!-- wp:woocommerce/product-collection {"query":{"perPage":' . $perPage . ',"pages":1,"offset":0,"postType":"product","order":"desc","orderBy":"' . $orderBy . '","search":"","exclude":[],"inherit":false,"taxQuery":[],"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false},"tagName":"div","displayLayout":{"type":"flex","columns":' . $columns . ',"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/' . $collection . '","hideControls":["inherit","attributes","keyword","order","default-order","featured","on-sale","stock-status","hand-picked","taxonomy","filterable","created","price-range"]} -->
      <div class="wp-block-woocommerce-product-collection"><!-- wp:woocommerce/product-template -->
      <!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true,"style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
      <!-- wp:woocommerce/product-sale-badge {"align":"right"} /-->
      <!-- /wp:woocommerce/product-image -->

      <!-- wp:post-title {"textAlign":"center","isLink":true,"style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}},"typography":{"fontSize":"24px"}},"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

      <!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","style":{"typography":{"fontSize":"14px"},"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} /-->

      <!-- wp:woocommerce/product-button {"textAlign":"center","isDescendentOfQueryLoop":true,"style":{"typography":{"fontSize":"16px"}}} /-->
      <!-- /wp:woocommerce/product-template -->
      </div>
      <!-- /wp:woocommerce/product-collection -->
    ';
  }

  private function getProductPlaceholderCard(string $imageName): string {
    $imageUrl = esc_url($this->cdnAssetUrl->generateCdnUrl('email-editor/' . $imageName));
    $imageAlt = esc_attr__('Product placeholder', 'mailpoet');
    $productName = __('Product name', 'mailpoet');

    return '
      <!-- wp:image {"sizeSlug":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
      <figure class="wp-block-image size-full" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)"><img src="' . $imageUrl . '" alt="' . $imageAlt . '"/></figure>
      <!-- /wp:image -->

      <!-- wp:heading {"textAlign":"center","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}},"typography":{"fontSize":"24px"}}} -->
      <h2 class="wp-block-heading has-text-align-center" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10);font-size:24px">' . $productName . '</h2>
      <!-- /wp:heading -->

      <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"14px"},"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
      <p class="has-text-align-center" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10);font-size:14px">$99.90</p>
      <!-- /wp:paragraph -->
    ';
  }
}
