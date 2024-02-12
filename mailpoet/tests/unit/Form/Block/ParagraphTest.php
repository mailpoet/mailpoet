<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Paragraph;
use MailPoet\WP\Functions as WPFunctions;

class ParagraphTest extends \MailPoetUnitTest {
  /** @var Paragraph */
  private $paragraph;

  public function _before() {
    parent::_before();
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('escAttr')->will($this->returnArgument(0));
    $this->paragraph = new Paragraph($wpMock);
  }

  public function testItShouldRenderParagraph() {
    $html = $this->paragraph->render([]);
    verify($html)->stringStartsWith('<p');
  }

  public function testItShouldRenderContent() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
      ],
    ]);
    verify($html)->equals('<p class="mailpoet_form_paragraph">Paragraph</p>');
  }

  public function testItShouldRenderClass() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'class_name' => 'class1 class2',
      ],
    ]);
    verify($html)->equals('<p class="mailpoet_form_paragraph class1 class2">Paragraph</p>');
  }

  public function testItShouldRenderAlign() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'align' => 'right',
      ],
    ]);
    verify($html)->equals('<p class="mailpoet_form_paragraph" style="text-align: right">Paragraph</p>');
  }

  public function testItShouldRenderTextColor() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'text_color' => 'red',
      ],
    ]);
    verify($html)->equals('<p class="mailpoet_form_paragraph" style="color: red">Paragraph</p>');
  }

  public function testItShouldRenderBackgroundColor() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'background_color' => 'red',
      ],
    ]);
    verify($html)->stringContainsString('style="background-color: red');
  }

  public function testItShouldRenderGradient() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'gradient' => 'linear-gradient(#fff, #000)',
      ],
    ]);
    verify($html)->stringContainsString('style="background: linear-gradient(#fff, #000)');
    verify($html)->stringContainsString('class="mailpoet_form_paragraph mailpoet-has-background-color"');
  }

  public function testItShouldRenderFontSize() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'font_size' => '33',
      ],
    ]);
    verify($html)->equals('<p class="mailpoet_form_paragraph mailpoet-has-font-size" style="font-size: 33px">Paragraph</p>');
  }

  public function testItShouldRenderPadding() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'padding' => ['top' => '10px', 'right' => '20px', 'bottom' => '30px', 'left' => '40px'],
      ],
    ]);
    verify($html)->stringContainsString('padding:10px 20px 30px 40px;');
  }

  public function testItShouldRenderFontSizeWithUnit() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'font_size' => '2.3em',
      ],
    ]);
    verify($html)->equals('<p class="mailpoet_form_paragraph mailpoet-has-font-size" style="font-size: 2.3em">Paragraph</p>');
  }

  public function testItShouldRenderLineHeight() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'line_height' => '2.3',
      ],
    ]);
    verify($html)->equals('<p class="mailpoet_form_paragraph" style="line-height: 2.3">Paragraph</p>');
  }

  public function testItShouldRenderDropCap() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'drop_cap' => '1',
      ],
    ]);
    verify($html)->equals('<p class="mailpoet_form_paragraph has-drop-cap">Paragraph</p>');
  }

  public function testItShouldRenderBackgroundClass() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'background_color' => 'red',
      ],
    ]);
    verify($html)->stringContainsString('<p class="mailpoet_form_paragraph mailpoet-has-background-color');
  }
}
