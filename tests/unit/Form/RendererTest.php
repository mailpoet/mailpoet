<?php

namespace MailPoet\Test\Form;

use Codeception\Util\Fixtures;
use MailPoet\Form\BlocksRenderer;
use MailPoet\Form\Renderer;
use MailPoet\Form\Util\Styles;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscription\Captcha;
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

  /** @var HtmlParser */
  private $htmlParser;

  public function _before() {
    parent::_before();
    $this->stylesMock = $this->createMock(Styles::class);
    $this->settingsMock = $this->createMock(SettingsController::class);
    $this->blocksRendererMock = $this->createMock(BlocksRenderer::class);
    $this->renderer = new Renderer($this->stylesMock, $this->settingsMock, $this->blocksRendererMock);
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
      ->willReturn(Captcha::TYPE_DISABLED);
    $html = $this->renderer->renderBlocks(Fixtures::get('simple_form_body'));
    $blocks = $this->htmlParser->findByXpath($html, "//div[@class='block']");
    expect($blocks->length)->equals(2);
  }

  public function testItShouldRenderHoneypot() {
    $this->blocksRendererMock->method('renderBlock')->willReturn('<div>Dummy</div>');
    $this->settingsMock
      ->method('get')
      ->with('captcha.type')
      ->willReturn(Captcha::TYPE_DISABLED);
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
        ['captcha.type', null, Captcha::TYPE_RECAPTCHA],
        ['captcha.recaptcha_site_token', null, $token],
      ]));
    $html = $this->renderer->renderBlocks(Fixtures::get('simple_form_body'));
    $recaptcha = $this->htmlParser->findByXpath($html, "//div[@class='mailpoet_recaptcha']");
    expect($recaptcha->length)->equals(1);
    $recaptchaIframes = $this->htmlParser->findByXpath($html, "//iframe");
    expect($recaptchaIframes->length)->equals(1);
    $iframe = $recaptchaIframes->item(0);
    assert($iframe instanceof \DOMNode);
    $source = $iframe->attributes->getNamedItem('src');
    assert($source instanceof \DOMAttr);
    expect($source->value)->equals("https://www.google.com/recaptcha/api/fallback?k=$token");
  }

  public function testItShouldNotRenderHoneypotAndRecaptcha() {
    $this->blocksRendererMock->method('renderBlock')->willReturn('<div>Dummy</div>');
    $this->settingsMock
      ->method('get')
      ->with('captcha.type')
      ->willReturn(Captcha::TYPE_DISABLED);
    $html = $this->renderer->renderBlocks(Fixtures::get('simple_form_body'), false);
    $hpLabel = $this->htmlParser->findByXpath($html, "//label[@class='mailpoet_hp_email_label']");
    expect($hpLabel->length)->equals(0);
    $recaptcha = $this->htmlParser->findByXpath($html, "//div[@class='mailpoet_recaptcha']");
    expect($recaptcha->length)->equals(0);
  }

  public function testItShouldRenderBackgroundColour() {
    $this->blocksRendererMock
      ->expects($this->exactly(2))
      ->method('renderBlock')
      ->willReturn('<span class="block">Dummy</span>');
    $this->settingsMock
      ->method('get')
      ->with('captcha.type')
      ->willReturn(Captcha::TYPE_DISABLED);
    $formBody = Fixtures::get('simple_form_body');
    $html = $this->renderer->renderHTML([
      'body' => $formBody,
      'settings' => ['backgroundColor' => 'red'],
    ]);
    $found = $this->htmlParser->findByXpath($html, "//div");
    expect($found->length)->equals(1);
    $div = $found->item(0);
    assert($div instanceof \DOMNode);
    $source = $div->attributes->getNamedItem('style');
    assert($source instanceof \DOMAttr);
    expect($source->value)->equals("background-color: red");
  }

  public function testItShouldRenderColour() {
    $this->blocksRendererMock
      ->expects($this->exactly(2))
      ->method('renderBlock')
      ->willReturn('<span class="block">Dummy</span>');
    $this->settingsMock
      ->method('get')
      ->with('captcha.type')
      ->willReturn(Captcha::TYPE_DISABLED);
    $formBody = Fixtures::get('simple_form_body');
    $html = $this->renderer->renderHTML([
      'body' => $formBody,
      'settings' => ['fontColor' => 'red'],
    ]);
    $found = $this->htmlParser->findByXpath($html, "//div");
    expect($found->length)->equals(1);
    $div = $found->item(0);
    assert($div instanceof \DOMNode);
    $source = $div->attributes->getNamedItem('style');
    assert($source instanceof \DOMAttr);
    expect($source->value)->equals("color: red");
  }

  public function testItShouldRenderFontSize() {
    $this->blocksRendererMock
      ->expects($this->exactly(2))
      ->method('renderBlock')
      ->willReturn('<span class="block">Dummy</span>');
    $this->settingsMock
      ->method('get')
      ->with('captcha.type')
      ->willReturn(Captcha::TYPE_DISABLED);
    $formBody = Fixtures::get('simple_form_body');
    $html = $this->renderer->renderHTML([
      'body' => $formBody,
      'settings' => ['fontSize' => '20'],
    ]);
    $found = $this->htmlParser->findByXpath($html, "//div");
    expect($found->length)->equals(1);
    $div = $found->item(0);
    assert($div instanceof \DOMNode);
    $source = $div->attributes->getNamedItem('style');
    assert($source instanceof \DOMAttr);
    expect($source->value)->equals("font-size: 20px");
  }
}
