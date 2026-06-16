<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

use MailPoet\Util\ThirdPartyOutput;

class ThirdPartyOutputTest extends \MailPoetUnitTest {
  public function testItDiscardsOpenOutputBuffers() {
    $baseline = ob_get_level();
    ob_start();
    ob_start();
    echo 'lazy-load rewriter must never see this';

    ThirdPartyOutput::preventHtmlRewriting();
    $levelAfter = ob_get_level();

    // Restore the buffer level the test framework started with so we do not
    // disturb output capture for the rest of the suite.
    while (ob_get_level() < $baseline) {
      ob_start();
    }

    verify($levelAfter)->equals(0);
  }

  public function testItDefinesOptimizerBypassConstants() {
    $baseline = ob_get_level();

    ThirdPartyOutput::preventHtmlRewriting();

    // Restore the framework's buffer level discarded by the call above.
    while (ob_get_level() < $baseline) {
      ob_start();
    }

    verify(defined('DONOTCACHEPAGE'))->true();
    verify(defined('DONOTMINIFY'))->true();
    verify(defined('DONOTLAZYLOAD'))->true();
    verify(defined('DONOTROCKETOPTIMIZE'))->true();
  }
}
