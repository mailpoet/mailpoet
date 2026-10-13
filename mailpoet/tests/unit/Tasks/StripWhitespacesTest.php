<?php declare(strict_types = 1);

namespace MailPoet\Test\Tasks;

use MailPoet\Tasks\StripWhitespaces;

require_once __DIR__ . '/../../../tasks/StripWhitespaces.php';

class StripWhitespacesTest extends \MailPoetUnitTest {
  public function testItThrowsOnOldPhp() {
    $this->expectException(\RuntimeException::class);
    new StripWhitespaces(70400);
  }

  public function testItRemovesLineAndBlockComments() {
    $input = "<?php\n// line\n# hash\n\$a = 1; // trailing\n/* block\n   more */\n\$b = 2; /* inline */ \$c = 3;\n";
    verify($this->strip($input))->equals("<?php\n\$a = 1;\n\$b = 2; \$c = 3;\n");
  }

  public function testItRemovesDocComments() {
    $input = "<?php\n/**\n * Doc.\n */\nclass A {\n  /** @var int */\n  public \$x;\n}\n";
    verify($this->strip($input))->equals("<?php\nclass A {\npublic \$x;\n}\n");
  }

  public function testItCollapsesWhitespace() {
    $input = "<?php\n\n\n\$a   =    1;\n\n  \t \$b = 2;\n";
    verify($this->strip($input))->equals("<?php\n\$a = 1;\n\$b = 2;\n");
  }

  public function testItNeverFusesTokensAroundRemovedComments() {
    verify($this->strip('<?php new/*x*/Foo;'))->equals('<?php new Foo;');
    verify($this->strip('<?php function/**/foo() {}'))->equals('<?php function foo() {}');
    verify($this->strip('<?php return/**/$x;'))->equals('<?php return $x;');
  }

  public function testItKeepsSpacesThatChangeMeaning() {
    verify($this->strip('<?php return $x;'))->equals('<?php return $x;');
    verify($this->strip('<?php $y = 1 .5;'))->equals('<?php $y = 1 .5;');
    verify($this->strip('<?php $y = $a - -$b;'))->equals('<?php $y = $a - -$b;');
  }

  public function testItKeepsNewlineAfterAttribute() {
    $input = "<?php\nclass A {\n  #[\\ReturnTypeWillChange]\n  public function x() {}\n}\n";
    verify($this->strip($input))->equals("<?php\nclass A {\n#[\\ReturnTypeWillChange]\npublic function x() {}\n}\n");
  }

  public function testItKeepsCloseTagEndingLineComment() {
    $input = "<?php // foo ?>\n<b>html</b>\n<?php echo 1;";
    verify($this->strip($input))->equals("<?php ?>\n<b>html</b>\n<?php echo 1;");
  }

  public function testItHandlesOpenAndCloseTagEdges() {
    verify($this->strip('<?php /*x*/'))->equals('<?php ');
    verify($this->strip("<?php echo 1; ?>\n"))->equals("<?php echo 1; ?>\n");
    verify($this->strip("<?php\n/* x */\n"))->equals("<?php\n");
  }

  public function testItKeepsHeredocAndNowdocUnchanged() {
    $body = "  // not a comment\n  /* nor this */   spaced\n\n\n  # hash\n";
    $input = "<?php\n\$a = <<<EOT\n{$body}EOT;\n\$b = <<<'EOT'\n{$body}EOT;\n";
    verify($this->strip($input))->equals($input);
    $input = "<?php\n\$a = <<<EOT\n  \$x   // keep\n  EOT\n;\n";
    verify($this->strip($input))->equals($input);
  }

  public function testItKeepsStringsUnchanged() {
    $input = "<?php\n\$a = 'http://x.com  /* y */ # z';\n\$b = \"a  //  b  /*  c  */  \$d  # e\";\n";
    verify($this->strip($input))->equals($input);
  }

  public function testItKeepsAbspathGuard() {
    $input = "<?php if (!defined('ABSPATH')) exit; /* c */\nclass A {}\n";
    verify($this->strip($input))->equals("<?php if (!defined('ABSPATH')) exit;\nclass A {}\n");
  }

  public function testItStripsHugeFileOfDocBlocks() {
    $block = "/**\n * Some documentation line.\n * Another line.\n */\n";
    $code = "<?php\n" . str_repeat($block, 6000) . "class A {}\n";
    verify(strlen($code))->greaterThan(300000);
    $result = $this->strip($code);
    verify(strpos($result, '/**'))->false();
    verify($result)->equals("<?php\nclass A {}\n");
  }

  public function testStrippedCodeStillParses() {
    $input = "<?php\n// c\nnamespace A;\n/** d */\n#[\\Attr]\nfinal class B {\n  # x\n  public function f(): int { return/**/1; } // y\n}\n";
    $result = $this->strip($input);
    $tokens = token_get_all($result, TOKEN_PARSE);
    verify(count($tokens))->greaterThan(0);
  }

  public function testStripDirectoryTouchesOnlyPhpFiles() {
    $dir = sys_get_temp_dir() . '/strip_ws_' . uniqid();
    mkdir($dir . '/sub', 0777, true);
    $code = "<?php\n// c\n\$a = 1;\n";
    file_put_contents($dir . '/a.php', $code);
    file_put_contents($dir . '/sub/b.php', $code);
    file_put_contents($dir . '/c.txt', $code);
    file_put_contents($dir . '/d.php.bak', $code);
    $count = (new StripWhitespaces())->stripDirectory($dir);
    $a = file_get_contents($dir . '/a.php');
    $b = file_get_contents($dir . '/sub/b.php');
    $c = file_get_contents($dir . '/c.txt');
    $d = file_get_contents($dir . '/d.php.bak');
    unlink($dir . '/a.php');
    unlink($dir . '/sub/b.php');
    unlink($dir . '/c.txt');
    unlink($dir . '/d.php.bak');
    rmdir($dir . '/sub');
    rmdir($dir);
    verify($count)->equals(2);
    verify($a)->equals("<?php\n\$a = 1;\n");
    verify($b)->equals("<?php\n\$a = 1;\n");
    verify($c)->equals($code);
    verify($d)->equals($code);
  }

  private function strip(string $code): string {
    if (PHP_VERSION_ID < 80000) {
      $this->markTestSkipped('Needs PHP 8.');
    }
    return (new StripWhitespaces())->strip($code);
  }
}
