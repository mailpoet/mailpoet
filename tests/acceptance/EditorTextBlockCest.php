<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorTextBlockCest {
  const TINYMCE_SELECTOR = '.tox-tinymce';
  const TEXT_BLOCK_SELECTOR = '.mailpoet_text_block';
  const CONTAINER_SELECTOR = '.mailpoet_container_horizontal';

  function addText(\AcceptanceTester $I) {
    $I->wantTo('add Text block to newsletter');
    $textInEditor = ('[data-automation-id="text_block_in_editor"]');
    $newsletter = (new Newsletter())
      ->withSubject('Text Block Newsletter')
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->dragAndDrop('#automation_editor_block_text', '#mce_1');
    $I->waitForText('Edit this to insert text.');
  }

  function toolbarIsClosing(\AcceptanceTester $I) {
    $I->wantTo('Automatically close TinyMCE toolbar when clicked outside textarea');
    $textInEditor = ('[data-automation-id="text_block_in_editor"]');
    $newsletter = (new Newsletter())
      ->withSubject('TinyMCE toolbar Newsletter')
      ->loadBodyFrom('newsletterThreeCols.json')
      ->create();
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->click(self::TEXT_BLOCK_SELECTOR);
    $I->waitForElementVisible(self::TINYMCE_SELECTOR);
    $I->click(self::CONTAINER_SELECTOR);
    $I->waitForElementNotVisible(self::TINYMCE_SELECTOR);
  }
}
