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
}
