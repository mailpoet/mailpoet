<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Heading;
use MailPoet\WP\Functions as WPFunctions;

class HeadingTest extends \MailPoetUnitTest {
  /** @var Heading */
  private $heading;

  public function _before() {
    parent::_before();
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('escAttr')->will($this->returnArgument(0));
    $this->heading = new Heading($wpMock);
  }

  public function testItShouldRenderHeading() {
    $html = $this->heading->render([]);
    expect($html)->startsWith('<h2');
  }

  public function testItShouldRenderContent() {
    $html = $this->heading->render([
      'params' => [
        'content' => 'Header',
      ],
    ]);
    expect($html)->equals('<h2 class="mailpoet-heading">Header</h2>');
  }

  public function testItShouldRenderLevel() {
    $html = $this->heading->render([
      'params' => [
        'content' => 'Header',
        'level' => 1,
      ],
    ]);
    expect($html)->equals('<h1 class="mailpoet-heading">Header</h1>');
  }

  public function testItShouldRenderClass() {
    $html = $this->heading->render([
      'params' => [
        'content' => 'Header',
        'level' => 1,
        'class_name' => 'class1 class2',
      ],
    ]);
    expect($html)->equals('<h1 class="mailpoet-heading class1 class2">Header</h1>');
  }

  public function testItShouldRenderAnchor() {
    $html = $this->heading->render([
      'params' => [
        'content' => 'Header',
        'level' => 1,
        'anchor' => 'anchor',
      ],
    ]);
    expect($html)->equals('<h1 class="mailpoet-heading" id="anchor">Header</h1>');
  }

  public function testItShouldRenderAlign() {
    $html = $this->heading->render([
      'params' => [
        'content' => 'Header',
        'level' => 1,
        'align' => 'right',
      ],
    ]);
    expect($html)->equals('<h1 class="mailpoet-heading" style="text-align: right">Header</h1>');
  }

  public function testItShouldRenderTextColour() {
    $html = $this->heading->render([
      'params' => [
        'content' => 'Header',
        'level' => 1,
        'text_color' => 'red',
      ],
    ]);
    expect($html)->equals('<h1 class="mailpoet-heading" style="color: red">Header</h1>');
  }

  public function testItShouldRenderBackgroundColor() {
    $html = $this->heading->render([
      'params' => [
        'content' => 'Header',
        'background_color' => 'red',
      ],
    ]);
    expect($html)->stringContainsString('style="background-color: red');
    expect($html)->stringContainsString('class="mailpoet-heading mailpoet-has-background-color"');
  }

  public function testItShouldRenderFontSize() {
    $html = $this->heading->render([
      'params' => [
        'content' => 'Header',
        'font_size' => '33',
      ],
    ]);
    expect($html)->equals('<h2 class="mailpoet-heading mailpoet-has-font-size" style="font-size: 33px">Header</h2>');
  }

  public function testItShouldRenderLineHeight() {
    $html = $this->heading->render([
      'params' => [
        'content' => 'Header',
        'line_height' => '2.3',
      ],
    ]);
    expect($html)->equals('<h2 class="mailpoet-heading" style="line-height: 2.3">Header</h2>');
  }
}
