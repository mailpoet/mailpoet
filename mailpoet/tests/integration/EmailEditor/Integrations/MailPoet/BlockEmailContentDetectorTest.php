<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet;

use MailPoet\EmailEditor\Integrations\MailPoet\BlockEmailContentDetector;

class BlockEmailContentDetectorTest extends \MailPoetTest {
  /** @dataProvider emptyContentProvider */
  public function testItDetectsEmptyContent(string $content): void {
    $detector = $this->diContainer->get(BlockEmailContentDetector::class);

    $this->assertFalse($detector->hasMeaningfulContent($content));
  }

  public function emptyContentProvider(): array {
    return [
      'empty string' => [''],
      'whitespace' => [" \n\t "],
      'comment-only block markup' => ['<!-- wp:paragraph --><!-- /wp:paragraph -->'],
      'empty paragraph' => ['<!-- wp:paragraph --><p></p><!-- /wp:paragraph -->'],
      'layout wrapper with spacer' => ['<!-- wp:group --><div class="wp-block-group"><!-- wp:spacer {"height":"32px"} /--></div><!-- /wp:group -->'],
      'separator' => ['<!-- wp:separator /-->'],
    ];
  }

  /** @dataProvider meaningfulContentProvider */
  public function testItDetectsMeaningfulContent(string $content): void {
    $detector = $this->diContainer->get(BlockEmailContentDetector::class);

    $this->assertTrue($detector->hasMeaningfulContent($content));
  }

  public function meaningfulContentProvider(): array {
    return [
      'paragraph text' => ['<!-- wp:paragraph --><p>Hello world</p><!-- /wp:paragraph -->'],
      'nested paragraph text' => ['<!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>Hello world</p><!-- /wp:paragraph --></div><!-- /wp:group -->'],
      'image block with id' => ['<!-- wp:image {"id":123} /-->'],
      'image markup' => ['<!-- wp:image --><figure><img src="https://example.com/image.jpg" alt="" /></figure><!-- /wp:image -->'],
      'coupon block' => ['<!-- wp:woocommerce/coupon-code /-->'],
      'product collection' => ['<!-- wp:woocommerce/product-collection --><div class="wp-block-woocommerce-product-collection"></div><!-- /wp:woocommerce/product-collection -->'],
      'product query post title' => ['<!-- wp:woocommerce/product-template --><!-- wp:post-title {"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /--><!-- /wp:woocommerce/product-template -->'],
      'woocommerce token' => ['<!--[woocommerce/customer-first-name]-->'],
      'mailpoet link token' => ['<a data-link-href="[mailpoet/subscription-unsubscribe-url]" contenteditable="false"></a>'],
    ];
  }
}
