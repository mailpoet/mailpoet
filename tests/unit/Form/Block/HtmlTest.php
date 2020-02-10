<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Html;

class HtmlTest extends \MailPoetUnitTest {
  /** @var Html */
  private $html;

  private $block = [
    'type' => 'divider',
    'name' => 'Divider',
    'id' => 'divider',
    'unique' => '1',
    'static' => '0',
    'params' => [
      'nl2br' => '1',
      'text' => "line1\nline2",
    ],
    'position' => '1',
  ];

  public function _before() {
    parent::_before();
    $this->html = new Html();
  }

  public function testItShouldRenderCustomHtml() {
    $html = $this->html->render($this->block);
    expect($html)->equals("<p class=\"mailpoet_paragraph\">line1<br />\nline2</p>");
  }

  public function testItShouldRenderCustomHtmlWithoutAutomaticBrs() {
    $block = $this->block;
    $block['params']['nl2br'] = '';
    $html = $this->html->render($block);
    expect($html)->equals("<p class=\"mailpoet_paragraph\">line1\nline2</p>");
  }

  public function testItShouldNotEscapeHtml() {
    $block = $this->block;
    $block['params']['text'] = '<p class="my-p">Hello</p>';
    $html = $this->html->render($block);
    expect($html)->equals("<p class=\"mailpoet_paragraph\"><p class=\"my-p\">Hello</p></p>");
  }
}
