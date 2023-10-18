<?php declare(strict_types = 1);

namespace MailPoet\Test\Form;

use MailPoet\Form\BlockStylesRenderer;
use MailPoet\WP\Functions as WPFunctions;

require_once __DIR__ . '/HtmlParser.php';

class BlockStylesRendererTest extends \MailPoetUnitTest {
  /** @var BlockStylesRenderer */
  private $renderer;

  public function _before() {
    parent::_before();
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('escAttr')->will($this->returnArgument(0));
    $this->renderer = new BlockStylesRenderer($wpMock);
  }

  public function testItShouldReturnEmptyStringForNoStylesOrUnsupportedStyles() {
    verify($this->renderer->renderForTextInput([]))->equals('');
    verify($this->renderer->renderForTextInput(['nonsense' => '10px']))->equals('');
  }

  public function testItShouldRenderSingleTextInputStyles() {
    verify($this->renderer->renderForTextInput(['border_radius' => 10]))->equals('border-style:solid;border-radius:10px !important;');
    verify($this->renderer->renderForTextInput(['border_color' => '#fff']))->equals('border-style:solid;border-color:#fff;');
    verify($this->renderer->renderForTextInput(['border_size' => 10]))->equals('border-style:solid;border-width:10px;');
    verify($this->renderer->renderForTextInput(['background_color' => '#dddddd']))->equals('background-color:#dddddd;');
  }

  public function testItShouldCompleteTextInputStyles() {
    $styles = [
      'border_radius' => 10,
      'border_color' => '#fff',
      'border_size' => 10,
      'background_color' => '#dddddd',
      'bold' => '1',
    ];
    $settings = [
      'input_padding' => '40',
      'fontSize' => 13,
    ];
    $result = $this->renderer->renderForTextInput($styles, $settings);
    verify($result)->stringContainsString('border-radius:10px !important;');
    verify($result)->stringContainsString('border-color:#fff;');
    verify($result)->stringContainsString('border-width:10px;');
    verify($result)->stringContainsString('background-color:#dddddd;');
    verify($result)->stringContainsString('padding:40px;');
    verify($result)->stringContainsString('font-size:13px;');
    expect($result)->stringNotContainsString('font-weight:bold;');
  }

  public function testItShouldRenderSingleButtonStyles() {
    verify($this->renderer->renderForButton(['border_radius' => 10]))->equals('border-style:solid;border-radius:10px !important;border-color:transparent;');
    verify($this->renderer->renderForButton(['border_color' => '#fff']))->equals('border-style:solid;border-color:#fff;');
    verify($this->renderer->renderForButton(['border_size' => 10]))->equals('border-style:solid;border-width:10px;border-color:transparent;');
    verify($this->renderer->renderForButton(['background_color' => '#dddddd']))->equals('background-color:#dddddd;border-color:transparent;');
    verify($this->renderer->renderForButton(['font_color' => '#aaa']))->equals('color:#aaa;border-color:transparent;');
    verify($this->renderer->renderForButton(['font_size' => 10]))->equals('font-size:10px;line-height:1.5;height:auto;border-color:transparent;');
  }

  public function testItShouldCompleteButtonStyles() {
    $styles = [
      'border_radius' => 10,
      'border_color' => '#fff',
      'border_size' => 10,
      'background_color' => '#dddddd',
      'font_color' => '#eeeeee',
      'font_size' => 8,
      'bold' => '1',
    ];
    $settings = [
      'input_padding' => '40',
      'fontSize' => 13,
    ];
    $result = $this->renderer->renderForButton($styles, $settings);
    verify($result)->stringContainsString('border-radius:10px !important;');
    verify($result)->stringContainsString('border-color:#fff;');
    verify($result)->stringContainsString('border-width:10px;');
    verify($result)->stringContainsString('background-color:#dddddd;');
    verify($result)->stringContainsString('color:#eeeeee;');
    verify($result)->stringContainsString('font-size:8px;');
    verify($result)->stringContainsString('font-weight:bold;');
    verify($result)->stringContainsString('padding:40px;');
  }

  public function testItShouldRenderButtonGradient() {
    $styles = [
      'gradient' => 'linear-gradient(#eee, #ddd)',
    ];
    $settings = [
      'input_padding' => '40',
      'fontSize' => 13,
    ];
    $result = $this->renderer->renderForButton($styles, $settings);
    verify($result)->stringContainsString('background: linear-gradient(#eee, #ddd);');
  }

  public function testItShouldRenderSegmentInputStyles() {
    verify($this->renderer->renderForSelect([], ['input_padding' => 10]))->equals('padding:10px;');
    verify($this->renderer->renderForSelect([], ['alignment' => 'right']))->equals('margin: 0 0 0 auto;');
    verify($this->renderer->renderForSelect([], ['alignment' => 'center']))->equals('margin: 0 auto;');
  }

  public function testItShouldRenderPlaceholderStyles() {
    verify($this->renderer->renderPlaceholderStyles([], 'input'))->equals('');
    verify($this->renderer->renderPlaceholderStyles(['params' => ['label_within' => '1']], 'input'))->equals('');
    expect($this->renderer->renderPlaceholderStyles([
      'params' => ['label_within' => '1'],
      'styles' => ['font_color' => 'red'],
    ], 'input'))->notEquals('');
  }

  public function testItShouldRenderFontFamily() {
    $styles = [];
    $settings = [
      'font_family' => 'font1',
    ];
    $result = $this->renderer->renderForButton($styles, $settings);
    verify($result)->stringContainsString("font-family:'font1'");
    $result = $this->renderer->renderForButton(['font_family' => 'font2'], $settings);
    verify($result)->stringContainsString("font-family:'font2'");
  }

  public function testItShouldSupportFontSizesWithUnits() {
    $settings = [
      'input_padding' => '40',
      'fontSize' => '1.5rem',
    ];
    $result = $this->renderer->renderForButton([], $settings);
    verify($result)->stringContainsString('font-size:1.5rem;');
    $styles = [
      'font_size' => '2.4em',
    ];
    $result = $this->renderer->renderForButton($styles, $settings);
    verify($result)->stringContainsString('font-size:2.4em;');
    $styles = [
      'font_size' => '23',
    ];
    $result = $this->renderer->renderForButton($styles, $settings);
    verify($result)->stringContainsString('font-size:23px;');
  }
}
