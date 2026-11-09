<?php declare(strict_types = 1);

namespace MailPoet\PostEditorBlocks;

use MailPoet\Config\Renderer;
use MailPoet\WP\Functions as WPFunctions;

class PostEditorBlockTest extends \MailPoetUnitTest {
  public function testBlockScriptLoadsTheJsxRuntimeFromWordPress(): void {
    $renderer = $this->createMock(Renderer::class);
    $renderer->method('getJsAsset')->willReturnArgument(0);
    $wp = $this->createMock(WPFunctions::class);
    $enqueuedScriptDeps = [];
    $enqueueScriptCallback =
      /**
       * @param array<int, string> $deps
       */
      function (string $handle, string $src = '', array $deps = []) use (&$enqueuedScriptDeps): void {
        $enqueuedScriptDeps[$handle] = $deps;
      };
    $wp->method('wpEnqueueScript')
      ->willReturnCallback($enqueueScriptCallback);
    $block = new PostEditorBlock(
      $renderer,
      $wp,
      $this->createMock(SubscriptionFormBlock::class),
      $this->createMock(NewsletterBlock::class),
      $this->createMock(ManageSubscriptionBlock::class)
    );

    $block->enqueueAssets();

    $this->assertContains('react-jsx-runtime', $enqueuedScriptDeps['mailpoet-block-form-block-js']);
  }
}
