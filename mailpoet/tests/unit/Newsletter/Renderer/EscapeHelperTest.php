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

  public function testItRemovesLinkWithScriptSchemeSplitByTabsOrLineBreaks() {
    verify(EHelper::escapeHtmlLinkAttr("java\tscript:void(0)"))->equals('');
    verify(EHelper::escapeHtmlLinkAttr("javascript\n:void(0)"))->equals('');
    verify(EHelper::escapeHtmlLinkAttr("JaVa\r\nScRiPt:void(0)"))->equals('');
    verify(EHelper::escapeHtmlLinkAttr("\x01javascript:void(0)"))->equals('');
    verify(EHelper::escapeHtmlLinkAttr("da\tta:text/html,x"))->equals('');
  }

  public function testItRemovesInvalidLinks() {
    verify(EHelper::escapeHtmlLinkAttr('java&#13;script:void(0)'))->equals('');
    verify(EHelper::escapeHtmlLinkAttr('java&#x0d;script:void(0)'))->equals('');
    verify(EHelper::escapeHtmlLinkAttr('&#1;javascript:void(0)'))->equals('');
    verify(EHelper::escapeHtmlLinkAttr('javascript&colon;void(0)'))->equals('');
    verify(EHelper::escapeHtmlLinkAttr('&#106;avascript:void(0)'))->equals('');
    verify(EHelper::escapeHtmlLinkAttr('&#x6A;avascript:void(0)'))->equals('');
    verify(EHelper::escapeHtmlLinkAttr('javascript&#58void(0)'))->equals('');
    verify(EHelper::escapeHtmlLinkAttr('java&Tab;script:void(0)'))->equals('');
    verify(EHelper::escapeHtmlLinkAttr('&#32;javascript:void(0)'))->equals('');
  }

  public function testItRemovesLinkWithVbscriptScheme() {
    verify(EHelper::escapeHtmlLinkAttr('VBScript:msgbox(1)'))->equals('');
  }

  public function testItKeepsRegularLinks() {
    verify(EHelper::escapeHtmlLinkAttr('https://example.com/?a=1&b=2'))->equals('https://example.com/?a=1&amp;b=2');
    verify(EHelper::escapeHtmlLinkAttr('mailto:info@example.com'))->equals('mailto:info@example.com');
    verify(EHelper::escapeHtmlLinkAttr('tel:+420123456789'))->equals('tel:+420123456789');
    verify(EHelper::escapeHtmlLinkAttr('[link:subscription_unsubscribe_url]'))->equals('[link:subscription_unsubscribe_url]');
    verify(EHelper::escapeHtmlLinkAttr('#top'))->equals('#top');
    verify(EHelper::escapeHtmlLinkAttr('data:image/png;base64,AAAA'))->equals('data:image/png;base64,AAAA');
    verify(EHelper::escapeHtmlLinkAttr('https://example.com/?topic=data: text'))->equals('https://example.com/?topic=data: text');
    verify(EHelper::escapeHtmlLinkAttr('https://example.com/?next=vbscript:'))->equals('https://example.com/?next=vbscript:');
    verify(EHelper::escapeHtmlLinkAttr('https://example.com/?next=javascript:'))->equals('https://example.com/?next=javascript:');
  }
}
