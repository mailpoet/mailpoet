<?php

namespace MailPoet\Test\Form;

use Codeception\Util\Fixtures;
use MailPoet\Form\BlocksRenderer;
use MailPoet\Form\Renderer;
use MailPoet\Form\Util\CustomFonts;
use MailPoet\Form\Util\Styles;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha\CaptchaConstants;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/HtmlParser.php';

class RendererTest extends \MailPoetUnitTest {
  /** @var Renderer */
  private $renderer;

  /** @var MockObject & Styles */
  private $stylesMock;

  /** @var MockObject & SettingsController */
  private $settingsMock;

  /** @var MockObject & BlocksRenderer */
  private $blocksRendererMock;

  /** @var MockObject & CustomFonts */
  private $customFonts;

  /** @var HtmlParser */
  private $htmlParser;

  public function _before() {
    parent::_before();
    $this->stylesMock = $this->createMock(Styles::class);
    $this->settingsMock = $this->createMock(SettingsController::class);
    $this->blocksRendererMock = $this->createMock(BlocksRenderer::class);
    $this->customFonts = $this->createMock(CustomFonts::class);
    $this->renderer = new Renderer($this->stylesMock, $this->settingsMock, $this->customFonts, $this->blocksRendererMock);
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderBlocks() {
    $this->blocksRendererMock
      ->expects($this->exactly(2))
      ->method('renderBlock')
      ->willReturn('<div class="block">Dummy</div>');
    $this->settingsMock
      ->method('get')
      ->with('captcha.type')
      ->willReturn(CaptchaConstants::TYPE_DISABLED);
    $html = $this->renderer->renderBlocks(Fixtures::get('simple_form_body'));
    $blocks = $this->htmlParser->findByXpath($html, "//div[@class='block']");
    expect($blocks->length)->equals(2);
  }

  public function testItShouldRenderHoneypot() {
    $this->blocksRendererMock->method('renderBlock')->willReturn('<div>Dummy</div>');
    $this->settingsMock
      ->method('get')
      ->with('captcha.type')
      ->willReturn(CaptchaConstants::TYPE_DISABLED);
    $html = $this->renderer->renderBlocks(Fixtures::get('simple_form_body'));
    $hpLabel = $this->htmlParser->findByXpath($html, "//label[@class='mailpoet_hp_email_label']");
    expect($hpLabel->length)->equals(1);
    $hpInput = $this->htmlParser->findByXpath($html, "//input[@type='email']");
    expect($hpInput->length)->equals(1);
  }

  public function testItShouldRenderReCaptcha() {
    $token = '123456';
    $this->blocksRendererMock->method('renderBlock')->willReturn('<div>Dummy</div>');
    $this->settingsMock
      ->method('get')
      ->will($this->returnValueMap([
        ['captcha.type', null, CaptchaConstants::TYPE_RECAPTCHA],
        ['captcha.recaptcha_site_token', null, $token],
      ]));
    $html = $this->renderer->renderBlocks(Fixtures::get('simple_form_body'));
    $recaptcha = $this->htmlParser->findByXpath($html, "//div[@class='mailpoet_recaptcha']");
    expect($recaptcha->length)->equals(1);
    $recaptchaIframes = $this->htmlParser->findByXpath($html, "//iframe");
    expect($recaptchaIframes->length)->equals(1);
    $iframe = $recaptchaIframes->item(0);
    $this->assertInstanceOf(\DOMNode::class, $iframe);
    $this->assertInstanceOf(\DOMNamedNodeMap::class, $iframe->attributes);
    $source = $iframe->attributes->getNamedItem('src');
    $this->assertInstanceOf(\DOMAttr::class, $source);
    expect($source->value)->equals("https://www.google.com/recaptcha/api/fallback?k=$token");
  }

  public function testItShouldNotRenderHoneypotAndRecaptcha() {
    $this->blocksRendererMock->method('renderBlock')->willReturn('<div>Dummy</div>');
    $this->settingsMock
      ->method('get')
      ->with('captcha.type')
      ->willReturn(CaptchaConstants::TYPE_DISABLED);
    $html = $this->renderer->renderBlocks(Fixtures::get('simple_form_body'), [], null, false);
    $hpLabel = $this->htmlParser->findByXpath($html, "//label[@class='mailpoet_hp_email_label']");
    expect($hpLabel->length)->equals(0);
    $recaptcha = $this->htmlParser->findByXpath($html, "//div[@class='mailpoet_recaptcha']");
    expect($recaptcha->length)->equals(0);
  }
}
