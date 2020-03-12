<?php

namespace MailPoet\Test\Form;

use Codeception\Util\Fixtures;
use MailPoet\Form\BlockWrapperRenderer;

class BlockWrapperRendererTest extends \MailPoetUnitTest {
  public function testItShouldWrapBlockContent() {
    $renderer = new BlockWrapperRenderer();
    $block = Fixtures::get('simple_form_body')[0];
    $result = $renderer->render($block, 'content');
    expect($result)->equals('<div class="mailpoet_paragraph">content</div>');
  }

  public function testItShouldWrapRenderCustomClasses() {
    $renderer = new BlockWrapperRenderer();
    $block = Fixtures::get('simple_form_body')[0];
    $block['params']['class_name'] = 'class1 class2';
    $result = $renderer->render($block, 'content');
    expect($result)->equals('<div class="mailpoet_paragraph class1 class2">content</div>');
  }
}
