<?php declare(strict_types = 1);

namespace MailPoet\PostEditorBlocks;

use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;

class NewsletterBlockTest extends \MailPoetTest {
  /** @var NewsletterBlock */
  private $block;

  public function _before() {
    parent::_before();
    $this->block = $this->diContainer->get(NewsletterBlock::class);
  }

  public function testItRendersThroughSharedNewsletterRenderer(): void {
    $newsletter = (new NewsletterFactory())
      ->withSentStatus()
      ->withSendingQueue()
      ->create();

    $html = $this->block->renderNewsletter([
      'newsletterId' => $newsletter->getId(),
      'height' => 650,
      'width' => 680,
      'showFallbackLink' => true,
      'fallbackLinkAlignment' => 'right',
      'iframeAlignment' => 'left',
      'align' => 'full',
    ]);

    $this->assertStringContainsString('<iframe', $html);
    $this->assertStringContainsString('height="650"', $html);
    $this->assertStringContainsString('width="680"', $html);
    $this->assertStringContainsString('max-width:680px', $html);
    $this->assertStringContainsString('class="mailpoet-newsletter-embed alignfull"', $html);
    $this->assertStringContainsString('style="text-align:left;"', $html);
    $this->assertStringContainsString('class="mailpoet-newsletter-embed-fallback" style="text-align:right;"', $html);
    $this->assertStringContainsString('View newsletter in browser', $html);
  }

  public function testItReturnsEmptyForMissingNewsletter(): void {
    $this->assertSame('', $this->block->renderNewsletter([]));
  }
}
