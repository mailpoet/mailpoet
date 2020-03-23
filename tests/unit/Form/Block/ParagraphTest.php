<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Paragraph;

class ParagraphTest extends \MailPoetUnitTest {
  /** @var Paragraph */
  private $paragraph;

  public function _before() {
    parent::_before();
    $this->paragraph = new Paragraph();
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
    expect($html)->equals('<p>Paragraph</p>');
  }

  public function testItShouldRenderClass() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'class_name' => 'class1 class2',
      ],
    ]);
    expect($html)->equals('<p class="class1 class2">Paragraph</p>');
  }

  public function testItShouldRenderAlign() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'align' => 'right',
      ],
    ]);
    expect($html)->equals('<p style="text-align: right">Paragraph</p>');
  }

  public function testItShouldRenderTextColor() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'text_color' => 'red',
      ],
    ]);
    expect($html)->equals('<p style="color: red">Paragraph</p>');
  }

  public function testItShouldRenderBackgroundColor() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'background_color' => 'red',
      ],
    ]);
    expect($html)->equals('<p style="background-color: red">Paragraph</p>');
  }

  public function testItShouldRenderFontSize() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'font_size' => '33',
      ],
    ]);
    expect($html)->equals('<p style="font-size: 33px">Paragraph</p>');
  }

  public function testItShouldRenderDropCap() {
    $html = $this->paragraph->render([
      'params' => [
        'content' => 'Paragraph',
        'drop_cap' => '1',
      ],
    ]);
    expect($html)->equals('<p class="has-drop-cap">Paragraph</p>');
  }
}
