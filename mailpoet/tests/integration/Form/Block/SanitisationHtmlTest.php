<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\BlockRendererHelper;
use MailPoet\Form\Block\Html;
use MailPoet\WP\Functions as WPFunctions;

class SanitisationHtmlTest extends \MailPoetTest {
  private Html $html;

  private $block = [
    'type' => 'html',
    'name' => 'Html',
    'id' => 'html',
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
    $this->html = new Html(
      $this->diContainer->get(BlockRendererHelper::class),
      $this->diContainer->get(WPFunctions::class)
    );
  }

  public function testItSanitisesHtml(): void {
    $block = $this->block;
    $block['params']['text'] = '<p class="my-p">Hello</p><img src=x onerror=alert(1)>';
    $html = $this->html->render($block, []);
    verify($html)->equals("<div class=\"mailpoet_paragraph\" ><p class=\"my-p\">Hello</p><img src=\"x\"></div>");
  }

  public function testItSanitisesClassName(): void {
    $block = $this->block;
    $block['params']['class_name'] = 'my_clas"s1 class2';
    $block['params']['text'] = 'line1';
    $html = $this->html->render($block, []);
    verify($html)->equals("<div class=\"mailpoet_paragraph my_clas&quot;s1 class2\" >line1</div>");
  }
}
