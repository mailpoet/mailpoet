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
}
