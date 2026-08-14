<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet\Templates\Library;

use MailPoet\EmailEditor\Integrations\MailPoet\Templates\Library\Newsletter;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

class NewsletterTest extends \MailPoetUnitTest {
  /** @var MockObject & WPFunctions */
  private $wp;

  public function _before() {
    parent::_before();
    $this->wp = $this->createMock(WPFunctions::class);
  }

  public function testItUsesSiteLogoWhenCustomLogoExists() {
    $this->wp->method('hasCustomLogo')->willReturn(true);

    $content = (new Newsletter($this->wp))->getContent();

    $this->assertStringContainsString('<!-- wp:site-logo {"width":130,"isLink":false,"align":"center"} /-->', $content);
    $this->assertStringNotContainsString('wp:site-title', $content);
    $this->assertStringNotContainsString('your-logo-placeholder.png', $content);
  }

  public function testItUsesSiteTitleWhenCustomLogoDoesNotExist() {
    $this->wp->method('hasCustomLogo')->willReturn(false);

    $content = (new Newsletter($this->wp))->getContent();

    $this->assertStringContainsString('<!-- wp:site-title {"level":2,"textAlign":"center"', $content);
    $this->assertStringNotContainsString('wp:site-logo', $content);
    $this->assertStringNotContainsString('your-logo-placeholder.png', $content);
  }

  public function testFooterLinksDoNotForceTextDecoration() {
    $this->wp->method('hasCustomLogo')->willReturn(false);

    $content = (new Newsletter($this->wp))->getContent();

    $this->assertStringContainsString('[mailpoet/subscription-unsubscribe-url]', $content);
    $this->assertStringContainsString('[mailpoet/subscription-manage-url]', $content);
    $this->assertStringNotContainsString('text-decoration', $content);
  }
}
