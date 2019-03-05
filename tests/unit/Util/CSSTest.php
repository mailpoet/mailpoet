<?php
namespace MailPoet\Test\Util;

use MailPoet\Util\CSS;

class CSSTest extends \MailPoetUnitTest {

  /** @var CSS */
  private $css;

  public function _before() {
    $this->css = new \MailPoet\Util\CSS();
  }

  // tests
  public function testItCanBeInstantiated() {
    expect_that($this->css instanceof \MailPoet\Util\CSS);
  }

  /**
   * Rules of same specificity should be ordered by their order in styles string
   * so that the rule which appears earlier is treated as rule with lower specificity.
   */
  public function testItParsesCssAndOrdersThemCorrectly() {
    $css = "
      div a { color: brown; }
      .green { color: green; }
      .purple.bold { color: purple; }
      span a { color: blue; }
      a { color: red; }
    ";
    $parsed = $this->css->parseCSS($css);
    $this->assertEquals('purple', $parsed[0]['properties']['color']);
    $this->assertEquals('green', $parsed[1]['properties']['color']);
    $this->assertEquals('blue', $parsed[2]['properties']['color']);
    $this->assertEquals('brown', $parsed[3]['properties']['color']);
    $this->assertEquals('red', $parsed[4]['properties']['color']);
  }

  public function testItCanInlineARule() {
    $styles = 'p { color: red; }';
    $content = '<p>Foo</p>';
    $html = $this->buildHtml($styles, $content);
    $result_html = (string)$this->css->inlineCSS(null, $html);
    $this->assertContains('<p style="color:red">', $result_html);
  }

  public function testItInlinesMoreSpecificRule() {
    $styles = 'p { color: red; } .blue { color: blue; }';
    $content = '<p class="blue">Foo</p>';
    $html = $this->buildHtml($styles, $content);
    $result_html = (string)$this->css->inlineCSS(null, $html);
    $this->assertContains('<p class="blue" style="color:blue">', $result_html);
  }

  public function testItPreserveInlinedRule() {
    $styles = 'p { color: red; }';
    $content = '<p style="color:green">Foo</p>';
    $html = $this->buildHtml($styles, $content);
    $result_html = (string)$this->css->inlineCSS(null, $html);
    $this->assertContains('<p style="color:green">', $result_html);
  }

  public function testItAlwaysInlinesGlobalImportantRule() {
    $styles = 'p { color: red !important; }';
    $content = '<p style="color:green !important">Foo</p>';
    $html = $this->buildHtml($styles, $content);
    $result_html = (string)$this->css->inlineCSS(null, $html);
    $this->assertContains('<p style="color:red">', $result_html);
  }

  public function testItMergesInlineStylesCorrectly() {
    $styles = $this->css->mergeInlineStyles('color: red', 'margin: 10px');
    $this->assertEquals('color:red;margin:10px', $styles);
    $styles = $this->css->mergeInlineStyles('color: red', 'margin: 10px; color: blue');
    $this->assertEquals('color:blue;margin:10px', $styles);
    $styles = $this->css->mergeInlineStyles('', 'margin: 10px; color: blue');
    $this->assertEquals('margin:10px;color:blue', $styles);
    $styles = $this->css->mergeInlineStyles('margin:10px;color:blue', '');
    $this->assertEquals('margin:10px;color:blue', $styles);
  }

  private function buildHtml($styles, $content) {
    return "<html><style>{$styles}</style><body>{$content}</body></html>";
  }
}
