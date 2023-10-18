<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter;

use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;

class EscapeHelperTest extends \MailPoetUnitTest {
  public function testItEscapesHtmlText() {
    verify(EHelper::escapeHtmlText('Text<tag>\'"Hello</tag>'))
      ->equals("Text&lt;tag&gt;'\"Hello&lt;/tag&gt;");
  }

  public function testItEscapesHtmlAttr() {
    verify(EHelper::escapeHtmlAttr('Text<tag>\'"Hello</tag>'))
      ->equals("Text&lt;tag&gt;&#039;&quot;Hello&lt;/tag&gt;");
  }

  public function testItEscapesLinkAttr() {
    verify(EHelper::escapeHtmlLinkAttr('Text<tag>\'"Hello</tag>'))
      ->equals("Text&lt;tag&gt;&#039;&quot;Hello&lt;/tag&gt;");
    verify(EHelper::escapeHtmlLinkAttr('javaScRipt:Text<tag>\'"Hello</tag>'))
      ->equals("");
    verify(EHelper::escapeHtmlLinkAttr(' javaScRipt:Text<tag>\'"Hello</tag>'))
      ->equals("");
    verify(EHelper::escapeHtmlLinkAttr('DAta:Text<tag>\'"Hello</tag>'))
      ->equals("");
    verify(EHelper::escapeHtmlLinkAttr('    DAta:Text<tag>\'"Hello</tag>'))
      ->equals("");
    verify(EHelper::escapeHtmlLinkAttr('DAta:appliCation<tag>\'"Hello</tag>'))
      ->equals("");
  }
}
