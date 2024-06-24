<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorTextBlockCest {
  const TINYMCE_SELECTOR = '.tox-tinymce';
  const TEXT_BLOCK_SELECTOR = '.mailpoet_text_block';
  const CONTAINER_SELECTOR = '.mailpoet_container_horizontal';

  public function addText(\AcceptanceTester $i) {
    $i->wantTo('add Text block to newsletter');

    $newsletter = (new Newsletter())
      ->withSubject('Text Block Newsletter')
      ->loadBodyFrom('newsletterWithText.json')
      ->create();

    $i->login();

    $i->amEditingNewsletter($newsletter->getId());
    $i->dragAndDrop('#automation_editor_block_text', '#mce_1');
    $i->waitForText('Edit this to insert text.');

    $i->wantTo('add Text block to empty column');
    $i->dragAndDrop('#automation_editor_block_text', '.mailpoet_container_empty');
    $i->waitForText('Edit this to insert text.');
    $i->seeNumberOfElements('[data-automation-id="text_block_in_editor"]', 3);
  }

  public function toolbarIsClosing(\AcceptanceTester $i) {
    $i->wantTo('Automatically close TinyMCE toolbar when clicked outside textarea');

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

  public function verifyText(\AcceptanceTester $i) {
    $i->wantTo('Verify the content alignment and color inside the text block');

    $textInEditor = ('[data-automation-id="text_block_in_editor"]');
    $newsletter = (new Newsletter())
      ->withSubject('Text Block Newsletter')
      ->loadBodyFrom('newsletterWithTextBlock.json')
      ->create();

    $i->login();

    $i->amEditingNewsletter($newsletter->getId());
    $i->assertAttributeContains($textInEditor . ' h1', 'style', 'left');
    $i->assertAttributeContains($textInEditor . ' h2', 'style', 'center');
    $i->assertAttributeContains($textInEditor . ' h3', 'style', 'right');
    $i->assertAttributeContains($textInEditor . ' h2 > strong > span', 'style', '#fd0000');
    $i->seeElement($textInEditor . ' > h3 > em');
  }
}
