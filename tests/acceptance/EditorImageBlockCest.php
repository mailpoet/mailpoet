<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class EditorImageBlockCest {
  function addImage(\AcceptanceTester $I) {
    $I->wantTo('add image block to newsletter');
    $I->cli('media import /wp-core/wp-content/plugins/mailpoet/tests/_data/unicornsplaceholder.png --allow-root');
    $newsletterTitle = 'Image Block Newsletter';
    (new Newsletter())
      ->withSubject($newsletterTitle)
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->waitForText($newsletterTitle);
    $I->clickItemRowActionByItemName($newsletterTitle, 'Edit');
    // Create image block
    $I->waitForText('Image');
    $I->wait(1); // just to be sure
    $I->dragAndDrop('#automation_editor_block_image', '#mce_0');
    $I->waitForText('Add images');
    $I->click('Media Library');
    $I->waitForElementClickable('.thumbnail');
    $I->click('.thumbnail');
    $I->waitForElementClickable('.media-button-insert', 10);
    $I->click('Select Image');
    $I->waitForText('IMAGE');
    $I->click('Done');
  }
}