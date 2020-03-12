<?php

namespace MailPoet\Test\Form;

use MailPoet\Form\TextInputStylesRenderer;

require_once __DIR__ . '/HtmlParser.php';

class TextInputStylesRendererTest extends \MailPoetUnitTest {
  /** @var TextInputStylesRenderer */
  private $renderer;

  public function _before() {
    parent::_before();
    $this->renderer = new TextInputStylesRenderer();
  }

  public function testItShouldReturnEmptyStringForNoStylesOrUnsupportedStyles() {
    expect($this->renderer->render([]))->equals('');
    expect($this->renderer->render(['nonsense' => '10px']))->equals('');
  }

  public function testItShouldRenderSingleStyles() {
    expect($this->renderer->render(['border_radius' => 10]))->equals('border-style:solid;border-radius:10px;');
    expect($this->renderer->render(['border_color' => '#fff']))->equals('border-style:solid;border-color:#fff;');
    expect($this->renderer->render(['border_size' => 10]))->equals('border-style:solid;border-width:10px;');
    expect($this->renderer->render(['background_color' => '#dddddd']))->equals('background-color:#dddddd;');
  }

  public function testItShouldCompleteStyles() {
    $styles = [
      'border_radius' => 10,
      'border_color' => '#fff',
      'border_size' => 10,
      'background_color' => '#dddddd',
    ];
    $result = $this->renderer->render($styles);
    expect($result)->contains('border-radius:10px;');
    expect($result)->contains('border-color:#fff;');
    expect($result)->contains('border-width:10px;');
    expect($result)->contains('background-color:#dddddd;');
  }
}
