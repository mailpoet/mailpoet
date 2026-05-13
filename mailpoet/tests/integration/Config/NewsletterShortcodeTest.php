<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;

class NewsletterShortcodeTest extends \MailPoetTest {
  public function _before() {
    parent::_before();
    $this->diContainer->get(Shortcodes::class)->init();
  }

  public function testItRendersNewsletterEmbedShortcode(): void {
    $newsletter = (new NewsletterFactory())
      ->withSentStatus()
      ->withSendingQueue()
      ->create();

    $html = do_shortcode(sprintf('[mailpoet_newsletter id="%d"]', $newsletter->getId()));

    $this->assertStringContainsString('<iframe', $html);
    $this->assertStringContainsString('height="800"', $html);
    $this->assertStringContainsString('View newsletter in browser', $html);
  }

  public function testItSupportsHeightAndFallbackAttributes(): void {
    $newsletter = (new NewsletterFactory())
      ->withSentStatus()
      ->withSendingQueue()
      ->create();

    $html = do_shortcode(sprintf(
      '[mailpoet_newsletter id="%d" height="600" show_fallback_link="0"]',
      $newsletter->getId()
    ));

    $this->assertStringContainsString('<iframe', $html);
    $this->assertStringContainsString('height="600"', $html);
    $this->assertStringNotContainsString('View newsletter in browser', $html);
  }

  public function testItReturnsEmptyForInvalidNewsletterId(): void {
    $this->assertSame('', do_shortcode('[mailpoet_newsletter]'));
    $this->assertSame('', do_shortcode('[mailpoet_newsletter id="0"]'));
    $this->assertSame('', do_shortcode('[mailpoet_newsletter id="-5"]'));
    $this->assertSame('', do_shortcode('[mailpoet_newsletter id="abc"]'));
  }
}
