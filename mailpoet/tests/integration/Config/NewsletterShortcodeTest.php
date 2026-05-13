<?php declare(strict_types = 1);

namespace MailPoet\Config;

use MailPoet\Router\Router;
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

  public function testItSupportsAppearanceAttributes(): void {
    $newsletter = (new NewsletterFactory())
      ->withSentStatus()
      ->withSendingQueue()
      ->create();

    $html = do_shortcode(sprintf(
      '[mailpoet_newsletter id="%d" height="600" width="720" iframe_alignment="right" show_fallback_link="0" fallback_link_alignment="left" show_email_background="0"]',
      $newsletter->getId()
    ));

    $this->assertStringContainsString('<iframe', $html);
    $this->assertStringContainsString('height="600"', $html);
    $this->assertStringContainsString('width="720"', $html);
    $this->assertStringContainsString('max-width:720px', $html);
    $this->assertStringContainsString('style="text-align:right;"', $html);
    $this->assertTrue($this->getIframeUrlData($html)['embed_hide_background']);
    $this->assertStringNotContainsString('View newsletter in browser', $html);
  }

  public function testItReturnsEmptyForInvalidNewsletterId(): void {
    $this->assertSame('', do_shortcode('[mailpoet_newsletter]'));
    $this->assertSame('', do_shortcode('[mailpoet_newsletter id="0"]'));
    $this->assertSame('', do_shortcode('[mailpoet_newsletter id="-5"]'));
    $this->assertSame('', do_shortcode('[mailpoet_newsletter id="abc"]'));
  }

  private function getIframeUrl(string $html): string {
    $matches = [];
    $this->assertSame(1, preg_match('/<iframe[^>]+src="([^"]+)"/', $html, $matches));
    return $matches[1];
  }

  private function getIframeUrlData(string $html): array {
    $parsedLink = parse_url(html_entity_decode($this->getIframeUrl($html)), PHP_URL_QUERY);
    parse_str((string)$parsedLink, $data);
    $this->assertArrayHasKey('data', $data);
    return Router::decodeRequestData($data['data']);
  }
}
