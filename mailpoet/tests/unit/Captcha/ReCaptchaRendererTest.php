<?php declare(strict_types = 1);

namespace unit\Captcha;

use MailPoet\Captcha\ReCaptchaRenderer;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha\CaptchaConstants;
use MailPoet\Test\Form\HtmlParser;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../Form/HtmlParser.php';

class ReCaptchaRendererTest extends \MailPoetUnitTest {

  /** @var HtmlParser */
  private $htmlParser;

  /** @var MockObject & SettingsController */
  private $settingsMock;

  /** @var ReCaptchaRenderer */
  private $renderer;

  public function _before() {
    $this->htmlParser = new HtmlParser();
    $this->settingsMock = $this->createMock(SettingsController::class);
    $this->renderer = new ReCaptchaRenderer($this->settingsMock, new WPFunctions());
  }

  public function testRenderingCheckbox() {
    $expectedSiteToken = 'expected_value';
    $this->settingsMock
      ->method('get')
      ->with('captcha')
      ->willReturn([
        'type' => CaptchaConstants::TYPE_RECAPTCHA,
        'recaptcha_site_token' => $expectedSiteToken,
        'recaptcha_invisible_site_token' => 'unexpected_value',
      ]);

    $html = $this->renderer->render();
    $this->htmlParser->findByXpath($html, "//div[@class='g-recaptcha' and not(@data-size)]");
    $this->htmlParser->findByXpath($html, "//div[@class='g-recaptcha' and @data-sitekey='$expectedSiteToken']");
  }

  public function testRenderingInvisible() {
    $expectedSiteToken = 'expected_value';
    $this->settingsMock
      ->method('get')
      ->with('captcha')
      ->willReturn([
        'type' => CaptchaConstants::TYPE_RECAPTCHA_INVISIBLE,
        'recaptcha_site_token' => 'unexpected_value',
        'recaptcha_invisible_site_token' => $expectedSiteToken,
      ]);

    $html = $this->renderer->render();
    $this->htmlParser->findByXpath($html, "//div[@class='g-recaptcha' and @data-size='invisible']");
    $this->htmlParser->findByXpath($html, "//div[@class='g-recaptcha' and @data-sitekey='$expectedSiteToken']");
  }
}
