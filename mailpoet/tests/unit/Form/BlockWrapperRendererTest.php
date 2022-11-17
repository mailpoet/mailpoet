<?php declare(strict_types = 1);

namespace MailPoet\Test\Form;

use Codeception\Util\Fixtures;
use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\WP\Functions as WPFunctions;

class BlockWrapperRendererTest extends \MailPoetUnitTest {
  public function testItShouldWrapBlockContent() {
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('escAttr')->will($this->returnArgument(0));
    $renderer = new BlockWrapperRenderer($wpMock);
    $block = Fixtures::get('simple_form_body')[0];
    $result = $renderer->render($block, 'content');
    expect($result)->equals('<div class="mailpoet_paragraph">content</div>');
  }

  public function testItShouldWrapRenderCustomClasses() {
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('escAttr')->will($this->returnArgument(0));
    $renderer = new BlockWrapperRenderer($wpMock);
    $block = Fixtures::get('simple_form_body')[0];
    $block['params']['class_name'] = 'class1 class2';
    $result = $renderer->render($block, 'content');
    expect($result)->equals('<div class="mailpoet_paragraph class1 class2">content</div>');
  }
}
