<?php

namespace MailPoet\Test\Newsletter;

use Codeception\Stub;
use MailPoet\Newsletter\Renderer\Blocks\Renderer;
use MailPoet\Newsletter\Renderer\Preprocessor;
use MailPoet\WP\Functions;

class PreprocessorTest extends \MailPoetUnitTest {

  function testProcessWooCommerceHeadingBlock() {
    $renderer = Stub::make(Renderer::class);
    $wp = Stub::make(new Functions, [
      'getOption' => function($name) {
        if ($name === 'woocommerce_email_base_color')
          return '{base_color}';
        if ($name === 'woocommerce_email_text_color')
          return '{text_color}';
      },
    ]);
    $preprocessor = new Preprocessor($renderer, $wp);
    expect($preprocessor->processBlock(['type' => 'woocommerceHeading']))->equals([[
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
              'text' => '<h1 style="color:{text_color};">[mailpet_woocommerce_heading_placeholder]</h1>',
            ],
          ],
        ],
      ],
    ]]);
  }

  function testProcessWooCommerceContentBlock() {
    $renderer = Stub::make(Renderer::class);
    $preprocessor = new Preprocessor($renderer, new Functions);
    expect($preprocessor->processBlock(['type' => 'woocommerceContent']))->equals([[
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
              'text' => '[mailpet_woocommerce_content_placeholder]',
            ],
          ],
        ],
      ],
    ]]);
  }

}