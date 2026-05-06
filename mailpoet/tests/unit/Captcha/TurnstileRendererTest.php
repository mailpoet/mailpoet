<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\Captcha\TurnstileRenderer;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\Form\HtmlParser;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../Form/HtmlParser.php';

class TurnstileRendererTest extends \MailPoetUnitTest {

  /** @var HtmlParser */
  private $htmlParser;

  /** @var MockObject & SettingsController */
  private $settingsMock;

  /** @var TurnstileRenderer */
  private $renderer;

  public function _before() {
    $this->htmlParser = new HtmlParser();
    $this->settingsMock = $this->createMock(SettingsController::class);
    $this->renderer = new TurnstileRenderer($this->settingsMock, new WPFunctions());
  }

  public function testItRendersTurnstile() {
    $siteToken = 'expected_value';
    $this->settingsMock
      ->method('get')
      ->with('captcha')
      ->willReturn([
        'turnstile_site_token' => $siteToken,
      ]);

    $html = $this->renderer->render();
    $matches = $this->htmlParser->findByXpath(
      $html,
      "//div[@class='cf-turnstile' and @data-sitekey='$siteToken' and not(@data-response-field-name)]"
    );

    verify($matches->length)->equals(1);
  }

  public function testItRendersResponseFieldName() {
    $siteToken = 'expected_value';
    $responseFieldName = 'data[turnstileResponseToken]';
    $this->settingsMock
      ->method('get')
      ->with('captcha')
      ->willReturn([
        'turnstile_site_token' => $siteToken,
      ]);

    $html = $this->renderer->render($responseFieldName);
    $matches = $this->htmlParser->findByXpath(
      $html,
      "//div[@class='cf-turnstile' and @data-sitekey='$siteToken' and @data-response-field-name='$responseFieldName']"
    );

    verify($matches->length)->equals(1);
  }
}
