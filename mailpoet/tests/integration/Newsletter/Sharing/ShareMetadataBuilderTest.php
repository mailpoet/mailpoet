<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter\Sharing;

use MailPoet\Newsletter\Sharing\ShareMetadataBuilder;
use MailPoet\Test\DataFactories\Newsletter;

class ShareMetadataBuilderTest extends \MailPoetTest {
  public function testItInjectsEscapedOpenGraphAndTwitterMetadata() {
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withSubject('Spring <Sale>')
      ->withPreheader('Preview & share')
      ->create();
    $html = '<html><head><title>Email</title></head><body>'
      . '<img src="https://example.com/hero.jpg" alt="Hero & image" width="600" height="300" />'
      . '</body></html>';

    $result = $this->diContainer->get(ShareMetadataBuilder::class)
      ->injectMetadata($html, $newsletter, 'https://example.com/mailpoet-email/1-spring-sale/');

    verify($result)->stringContainsString('<meta name="robots" content="noindex, nofollow" />');
    verify($result)->stringContainsString('<meta property="og:title" content="Spring &lt;Sale&gt;" />');
    verify($result)->stringContainsString('<meta property="og:description" content="Preview &amp; share" />');
    verify($result)->stringContainsString('<meta property="og:image" content="https://example.com/hero.jpg" />');
    verify($result)->stringContainsString('<meta property="og:image:alt" content="Hero &amp; image" />');
    verify($result)->stringContainsString('<meta name="twitter:card" content="summary_large_image" />');
    verify($result)->stringContainsString('</head>');
  }

  public function testItStillInjectsRobotsMetaEvenWhenTitleIsEmpty() {
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withSubject('')
      ->create();
    $html = '<html><head></head><body></body></html>';

    $result = $this->diContainer->get(ShareMetadataBuilder::class)
      ->injectMetadata($html, $newsletter, 'https://example.com/mailpoet-email/abc123/');

    verify($result)->stringContainsString('<meta name="robots" content="noindex, nofollow" />');
    verify($result)->stringNotContainsString('og:title');
  }

  public function testItPreservesDollarDigitSequencesInSubject() {
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withSubject('Save $50 today, $5 off, $0 trial')
      ->create();
    $html = '<html><head></head><body></body></html>';

    $result = $this->diContainer->get(ShareMetadataBuilder::class)
      ->injectMetadata($html, $newsletter, 'https://example.com/mailpoet-email/1-save/');

    verify($result)->stringContainsString('<meta property="og:title" content="Save $50 today, $5 off, $0 trial" />');
    verify($result)->stringContainsString('<meta name="twitter:title" content="Save $50 today, $5 off, $0 trial" />');
  }

  public function testItOmitsUnsuitableImages() {
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withSubject('Text update')
      ->create();
    $html = '<html><head></head><body>'
      . '<img src="https://example.com/social-icons/facebook.png" width="32" height="32" />'
      . '</body></html>';

    $result = $this->diContainer->get(ShareMetadataBuilder::class)
      ->injectMetadata($html, $newsletter, 'https://example.com/mailpoet-email/1-text-update/');

    verify($result)->stringContainsString('<meta name="twitter:card" content="summary" />');
    verify($result)->stringNotContainsString('og:image');
  }

  public function testItInjectsShareToolbarOutsideEmailContent(): void {
    $newsletter = (new Newsletter())
      ->withSentStatus()
      ->withSubject('Shareable update')
      ->create();

    $result = $this->diContainer->get(ShareMetadataBuilder::class)
      ->injectShareToolbar(
        '<html><body><main>Email content</main></body></html>',
        $newsletter,
        'https://example.com/mailpoet-email/1-shareable-update/'
      );

    verify($result)->stringContainsString('data-mailpoet-share-host');
    verify($result)->stringContainsString('class="mailpoet-share-toolbar"');
    verify($result)->stringContainsString('data-mailpoet-share-copy="https://example.com/mailpoet-email/1-shareable-update/"');
    verify($result)->stringContainsString('mailpoet-share-toolbar__controls');
    $this->assertMatchesRegularExpression('#mailpoet-share-toolbar__url-row.*mailpoet-share-toolbar__url.*mailpoet-share-toolbar__copy-button#s', $result);
    $this->assertMatchesRegularExpression('#mailpoet-share-toolbar__actions.*mailpoet-share-toolbar__social-button--facebook.*mailpoet-share-toolbar__social-button--x.*mailpoet-share-toolbar__social-button--whatsapp.*mailpoet-share-toolbar__social-button--email#s', $result);
    verify($result)->stringContainsString('mailpoet-share-toolbar__copy-button components-button is-secondary');
    verify($result)->stringContainsString('mailpoet-share-toolbar__social-button--facebook components-button');
    verify($result)->stringContainsString('mailpoet-share-toolbar__social-button--facebook.components-button{background:#1877f2}');
    verify($result)->stringContainsString('mailpoet-share-toolbar__icon--facebook');
    verify($result)->stringContainsString('mailpoet-share-toolbar__icon--x');
    verify($result)->stringContainsString('mailpoet-share-toolbar__icon--whatsapp');
    verify($result)->stringContainsString('mailpoet-share-toolbar__icon--email');
    verify($result)->stringContainsString('aria-live="polite"');
    verify($result)->stringContainsString('mailto:?subject=');
    // mailto opens in a new tab so clicking it doesn't unload the share page itself.
    $this->assertMatchesRegularExpression('#<a [^>]*href="mailto:\?[^"]*"[^>]*target="_blank"#', $result);
    // brand names are not passed through __() so a translation hook on facebook/whatsapp can't break them
    verify($result)->stringContainsString('>Facebook</span>');
    verify($result)->stringContainsString('>WhatsApp</span>');
    // shadow-DOM promotion + navigator.share AbortError silencing
    verify($result)->stringContainsString('attachShadow');
    verify($result)->stringContainsString('.catch(function(){})');
    // toolbar is injected before the <main> content
    verify(strpos($result, 'data-mailpoet-share-host'))->lessThan(strpos($result, '<main>Email content</main>'));
    // and the toolbar markup is fully balanced — the email content is NOT a descendant
    // of the host. (Regression test: if the host wrapper isn't closed before <main>,
    // the shadow-DOM promotion swallows the entire email body into the shadow root.)
    $this->assertSame(
      1,
      preg_match('#<main>Email content</main>#', $result),
      'Email content should be present'
    );
    $dom = \MailPoet\Util\pQuery\pQuery::parseStr($result);
    $main = $dom->query('[data-mailpoet-share-host] main');
    verify(count($main))->equals(0);
  }
}
