<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use Codeception\Util\Fixtures;

class GutenergRenderTest extends \MailPoetTest {
  public function testItRendersHtml() {
    $gutRenderer = $this->diContainer->get(GutenbergRenderer::class);
    $output = $gutRenderer->render(Fixtures::get('gutenberg_email_body'));
    file_put_contents(__DIR__ . '/output.html', $output);
  }
}
