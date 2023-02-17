<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter;

use Codeception\Stub;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Renderer\Blocks\AbandonedCartContent;
use MailPoet\Newsletter\Renderer\Blocks\AutomatedLatestContentBlock;
use MailPoet\Newsletter\Renderer\Preprocessor;
use MailPoet\WooCommerce\CouponPreProcessor;
use MailPoet\WooCommerce\TransactionalEmails;

class PreprocessorTest extends \MailPoetUnitTest {
  public function testProcessWooCommerceHeadingBlock() {
    $acc = Stub::make(AbandonedCartContent::class);
    $alc = Stub::make(AutomatedLatestContentBlock::class);
    $couponPreProcessor = Stub::make(CouponPreProcessor::class);
    $transactionalEmails = Stub::make(TransactionalEmails::class, [
      'getWCEmailSettings' => [
        'base_color' => '{base_color}',
        'base_text_color' => '{base_text_color}',
      ],
    ]);
    $wooPreprocessor = new TransactionalEmails\ContentPreprocessor($transactionalEmails);
    $preprocessor = new Preprocessor($acc, $alc, $wooPreprocessor, $couponPreProcessor);
    expect($preprocessor->processBlock(new NewsletterEntity(), ['type' => 'woocommerceHeading']))->equals([[
      'type' => 'container',
      'orientation' => 'horizontal',
      'styles' => [
        'block' => ['backgroundColor' => '{base_color}'],
      ],
      'blocks' => [
        [
          'type' => 'container',
          'orientation' => 'vertical',
          'styles' => ['block' => ['backgroundColor' => 'transparent']],
          'blocks' => [
            [
              'type' => 'text',
              'text' => Preprocessor::WC_HEADING_BEFORE . '<h1 style="color:{base_text_color};">[mailpoet_woocommerce_heading_placeholder]</h1>' . Preprocessor::WC_HEADING_AFTER,
            ],
          ],
        ],
      ],
    ]]);
  }

  public function testProcessWooCommerceContentBlock() {
    $acc = Stub::make(AbandonedCartContent::class);
    $alc = Stub::make(AutomatedLatestContentBlock::class);
    $couponPreProcessor = Stub::make(CouponPreProcessor::class);
    $wooPreprocessor = new TransactionalEmails\ContentPreprocessor(Stub::make(TransactionalEmails::class));
    $preprocessor = new Preprocessor($acc, $alc, $wooPreprocessor, $couponPreProcessor);
    expect($preprocessor->processBlock(new NewsletterEntity(), ['type' => 'woocommerceContent']))->equals([[
      'type' => 'container',
      'orientation' => 'horizontal',
      'styles' => [
        'block' => ['backgroundColor' => 'transparent'],
      ],
      'blocks' => [
        [
          'type' => 'container',
          'orientation' => 'vertical',
          'styles' => ['block' => ['backgroundColor' => 'transparent']],
          'blocks' => [
            [
              'type' => 'placeholder',
              'placeholder' => '[mailpoet_woocommerce_content_placeholder]',
            ],
          ],
        ],
      ],
    ]]);
  }
}
