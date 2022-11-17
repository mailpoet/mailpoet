<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorTextBlockCest {
  const TINYMCE_SELECTOR = '.tox-tinymce';
  const TEXT_BLOCK_SELECTOR = '.mailpoet_text_block';
  const CONTAINER_SELECTOR = '.mailpoet_container_horizontal';

  public function addText(\AcceptanceTester $i) {
    $i->wantTo('add Text block to newsletter');
    $textInEditor = ('[data-automation-id="text_block_in_editor"]');
    $newsletter = (new Newsletter())
      ->withSubject('Text Block Newsletter')
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->dragAndDrop('#automation_editor_block_text', '#mce_1');
    $i->waitForText('Edit this to insert text.');
  }

  public function toolbarIsClosing(\AcceptanceTester $i) {
    $i->wantTo('Automatically close TinyMCE toolbar when clicked outside textarea');
    $textInEditor = ('[data-automation-id="text_block_in_editor"]');
    $newsletter = (new Newsletter())
      ->withSubject('TinyMCE toolbar Newsletter')
      ->loadBodyFrom('newsletterThreeCols.json')
      ->create();
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->click(self::TEXT_BLOCK_SELECTOR);
    $i->waitForElementVisible(self::TINYMCE_SELECTOR);
    $i->click(self::CONTAINER_SELECTOR);
    $i->waitForElementNotVisible(self::TINYMCE_SELECTOR);
  }
}
