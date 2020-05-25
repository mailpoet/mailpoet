<?php

namespace MailPoet\Test\Newsletter;

use Codeception\Stub;
use MailPoet\Newsletter\Renderer\Blocks\Renderer;
use MailPoet\Newsletter\Renderer\Preprocessor;
use MailPoet\WooCommerce\TransactionalEmails;

class PreprocessorTest extends \MailPoetUnitTest {
  public function testProcessWooCommerceHeadingBlock() {
    $renderer = Stub::make(Renderer::class);
    $transactionalEmails = Stub::make(TransactionalEmails::class, [
      'getWCEmailSettings' => [
        'base_color' => '{base_color}',
        'base_text_color' => '{base_text_color}',
      ],
    ]);
    $preprocessor = new Preprocessor($renderer, $transactionalEmails);
    expect($preprocessor->processBlock([], ['type' => 'woocommerceHeading']))->equals([[
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
    $renderer = Stub::make(Renderer::class);
    $preprocessor = new Preprocessor($renderer, Stub::make(TransactionalEmails::class));
    expect($preprocessor->processBlock([], ['type' => 'woocommerceContent']))->equals([[
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
              'type' => 'text',
              'text' => '[mailpoet_woocommerce_content_placeholder]',
            ],
          ],
        ],
      ],
    ]]);
  }
}
