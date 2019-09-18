<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorImageBlockCest {
  function addImage(\AcceptanceTester $I) {
    $I->wantTo('add image block to newsletter');
    $I->cli(['media', 'import', '/wp-core/wp-content/plugins/mailpoet/tests/_data/unicornsplaceholder.png', '--allow-root']);
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
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
