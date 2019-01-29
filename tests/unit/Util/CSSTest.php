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
}
