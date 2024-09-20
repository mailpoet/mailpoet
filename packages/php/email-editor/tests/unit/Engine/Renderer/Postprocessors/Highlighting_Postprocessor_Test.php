<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\Postprocessors;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors\Highlighting_Postprocessor;

class Highlighting_Postprocessor_Test extends \MailPoetUnitTest {
  /** @var Highlighting_Postprocessor */
  private $postprocessor;

  public function _before() {
    parent::_before();
    $this->postprocessor = new Highlighting_Postprocessor();
  }

  public function testItReplacesHtmlElements(): void {
    $html = '
      <mark>Some text</mark>
      <p>Some <mark style="color:red;">paragraph</mark></p>
      <a href="http://example.com">Some <mark style="font-weight:bold;">link</mark></a>
    ';
    $result = $this->postprocessor->postprocess($html);
    $this->assertEquals( '
      <span>Some text</span>
      <p>Some <span style="color:red;">paragraph</span></p>
      <a href="http://example.com">Some <span style="font-weight:bold;">link</span></a>
    ', $result);
  }
}
