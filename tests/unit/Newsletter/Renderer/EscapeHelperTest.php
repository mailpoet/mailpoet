<?php

namespace MailPoet\Test\Newsletter;

use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;

class EscapeHelperTest extends \MailPoetUnitTest {

  function testItEscapesHtmlText() {
    expect(EHelper::escapeHtmlText('Text<tag>\'"Hello</tag>'))
      ->equals("Text&lt;tag&gt;'\"Hello&lt;/tag&gt;");
  }

  function testItEscapesHtmlAttr() {
    expect(EHelper::escapeHtmlAttr('Text<tag>\'"Hello</tag>'))
      ->equals("Text&lt;tag&gt;&#039;&quot;Hello&lt;/tag&gt;");
  }

  function testItEscapesLinkAttr() {
    expect(EHelper::escapeHtmlLinkAttr('Text<tag>\'"Hello</tag>'))
      ->equals("Text&lt;tag&gt;&#039;&quot;Hello&lt;/tag&gt;");
    expect(EHelper::escapeHtmlLinkAttr('javaScRipt:Text<tag>\'"Hello</tag>'))
      ->equals("");
    expect(EHelper::escapeHtmlLinkAttr(' javaScRipt:Text<tag>\'"Hello</tag>'))
      ->equals("");
    expect(EHelper::escapeHtmlLinkAttr('DAta:Text<tag>\'"Hello</tag>'))
      ->equals("");
    expect(EHelper::escapeHtmlLinkAttr('    DAta:Text<tag>\'"Hello</tag>'))
      ->equals("");
    expect(EHelper::escapeHtmlLinkAttr('DAta:appliCation<tag>\'"Hello</tag>'))
      ->equals("");
  }
}
