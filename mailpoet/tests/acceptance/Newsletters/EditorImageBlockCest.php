<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class EditorImageBlockCest {
  public function addImage(\AcceptanceTester $i) {
    $i->wantTo('add image block to newsletter');
    $i->cli(['media', 'import', '/wp-core/wp-content/plugins/mailpoet/tests/_data/unicornsplaceholder.png']);
    $newsletter = (new Newsletter())
      ->loadBodyFrom('newsletterWithText.json')
      ->create();
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());
    $i->dragAndDrop('#automation_editor_block_image', '#mce_0');
    $i->waitForText('Add images');
    $i->click('Media Library');
    $i->waitForElementClickable('.thumbnail');
    $i->click('.thumbnail');
    $i->waitForElementClickable('.media-button-insert', 10);
    $i->click('Select Image');
    $i->waitForText('IMAGE');
    $i->click('Done');
  }
}
