<?php declare(strict_types = 1);

namespace MailPoet\Form\Block;

/**
 * There is also a unit test for this class in mailpoet/tests/unit/Form/Block/BlockRendererHelperTest.php
 * The integration test method that need WordPress to be loaded.
 */
class BlockRendererHelperTest extends \MailPoetTest {
  public function testItEscapesKnownShortCodes() {
    $text = '[mailpoet_subscribers_count] [gallery attr="attr"]inside[/gallery][unknown]';
    $rendererHelper = $this->diContainer->get(BlockRendererHelper::class);
    $escaped = $rendererHelper->escapeShortCodes($text);
    verify($escaped)->equals('&#91;mailpoet_subscribers_count&#93; &#91;gallery attr="attr"&#93;inside&#91;/gallery&#93;[unknown]');
  }
}
