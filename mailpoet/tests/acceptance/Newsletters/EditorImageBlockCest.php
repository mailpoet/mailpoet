<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Facebook\WebDriver\Exception\TimeoutException;
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
    for ($attempt = 1; $attempt <= 3; $attempt++) {
      $i->dragAndDrop('#automation_editor_block_image', '#mce_0');
      try {
        $i->waitForText('Add images');
        break;
      } catch (TimeoutException $e) {
        if ($attempt === 3) {
          throw $e;
        }
      }
    }
    $i->click('Media Library');
    $i->waitForElementClickable('.thumbnail');
    $i->click('.thumbnail');
    $i->waitForElementClickable('.media-button-insert', 10);
    $i->click('Select Image');
    $i->waitForText('IMAGE');
    $i->click('Done');
  }
}
