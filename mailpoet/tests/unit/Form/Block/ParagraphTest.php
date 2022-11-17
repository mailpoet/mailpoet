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
    expect($html)->startsWith('<p');
  }

  public function testItShouldRenderContent() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
      ],
    ]);
    expect($html)->equals('<p class="mailpoet_form_paragraph">Paragraph</p>');
  }

  public function testItShouldRenderClass() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'class_name' => 'class1 class2',
      ],
    ]);
    expect($html)->equals('<p class="mailpoet_form_paragraph class1 class2">Paragraph</p>');
  }

  public function testItShouldRenderAlign() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'align' => 'right',
      ],
    ]);
    expect($html)->equals('<p class="mailpoet_form_paragraph" style="text-align: right">Paragraph</p>');
  }

  public function testItShouldRenderTextColor() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'text_color' => 'red',
      ],
    ]);
    expect($html)->equals('<p class="mailpoet_form_paragraph" style="color: red">Paragraph</p>');
  }

  public function testItShouldRenderBackgroundColor() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'background_color' => 'red',
      ],
    ]);
    expect($html)->stringContainsString('style="background-color: red');
  }

  public function testItShouldRenderFontSize() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'font_size' => '33',
      ],
    ]);
    expect($html)->equals('<p class="mailpoet_form_paragraph mailpoet-has-font-size" style="font-size: 33px">Paragraph</p>');
  }

  public function testItShouldRenderLineHeight() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'line_height' => '2.3',
      ],
    ]);
    expect($html)->equals('<p class="mailpoet_form_paragraph" style="line-height: 2.3">Paragraph</p>');
  }

  public function testItShouldRenderDropCap() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'drop_cap' => '1',
      ],
    ]);
    expect($html)->equals('<p class="mailpoet_form_paragraph has-drop-cap">Paragraph</p>');
  }

  public function testItShouldRenderBackgroundClass() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'background_color' => 'red',
      ],
    ]);
    expect($html)->stringContainsString('<p class="mailpoet_form_paragraph mailpoet-has-background-color');
  }
}
