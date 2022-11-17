<?php declare(strict_types = 1);

namespace MailPoet\Form;

use Codeception\Util\Fixtures;
use MailPoet\DI\ContainerWrapper;

class RendererTest extends \MailPoetTest {
  public function testItRendersFormBody() {
    $formBody = Fixtures::get('form_body_template');
    $renderer = ContainerWrapper::getInstance()->get(Renderer::class);
    $this->assertInstanceOf(Renderer::class, $renderer);
    $formHtml = $renderer->renderBlocks($formBody);
    expect($formHtml)->stringContainsString('<input type="email" name="data[email]"/>'); // honey pot
    expect($formHtml)->stringContainsString('input type="submit" class="mailpoet_submit" value="Subscribe!"'); // Subscribe button
  }
}
