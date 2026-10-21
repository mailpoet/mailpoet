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

  public function testItKeepsNameValidationMessagesWithApostrophesInOneAttribute() {
    $translate = function (string $translation, string $text): string {
      return $text === 'Please specify a valid name.' ? "Merci d'indiquer un nom valide." : $translation;
    };
    add_filter('gettext', $translate, 10, 2);
    try {
      $rendererHelper = $this->diContainer->get(BlockRendererHelper::class);
      $validation = $rendererHelper->getInputValidation(['id' => 'first_name', 'type' => 'text', 'params' => []]);
    } finally {
      remove_filter('gettext', $translate, 10);
    }
    $this->assertHtmlHasTag('<input ' . $validation . '>', 'input', [
      'data-parsley-names' => '["Merci d\'indiquer un nom valide.","Addresses in names are not permitted, please add your name instead."]',
    ]);
  }
}
