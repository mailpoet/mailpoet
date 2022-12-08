<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;
use PHPUnit\Framework\Assert;

class ReinstallFromScratchCest {
  public function reinstallFromScratch(\AcceptanceTester $i) {
    $i->wantTo('Reinstall from scratch');
    $i->login();

    // Step 1 - create email, form, list and subscribers
    $settings = new Settings();
    $settings->withSkippedWelcomeWizard();
    $newsletter = new Newsletter();
    $newsletter->create();
    $form = new Form();
    $form->create();
    $segment = new Segment();
    $segment->create();
    $subscriber = new Subscriber();
    $subscriber->create();
    // Create few WP users, which should be imported after reinstall
    for ($index = 0; $index <= 5; $index++) {
      wp_create_user('test' . $index, 'password', 'imported' . $index . '@from.wordpress');
    }

    // Step 2 - reinstall from scratch
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-settings#advanced');
    $i->waitForElement('[data-automation-id="reinstall-button"]');
    $i->click('Reinstall now...');
    $i->acceptPopup();
    $i->waitForText('Start by configuring your sender information');

    // Step 3 - skip all tutorials, which could interfere with other tests
    $settings = new Settings();
    $settings
      ->withSkippedTutorials()
      ->withSkippedWelcomeWizard();

    // Step 4 - check if data are emptied and repopulated
    // Check emails
    $i->amOnMailpoetPage('Emails');
    $i->seeInCurrentUrl('#/new');
    // Check forms
    $i->amOnMailpoetPage('Forms');
    $i->waitForText('No forms were found. Why not create a new one?', 30);
    // Check lists
    $i->amOnMailpoetPage('Lists');
    $i->waitForText('WordPress Users', 30, '[data-automation-id="listing_item_1"]');
    $i->see('Newsletter mailing list', '[data-automation-id="listing_item_3"]');
    $i->seeNumberOfElements('[data-automation-id^=listing_item_]', 2);
    // Check subscribers
    $i->amOnMailPoetPage('Subscribers');
    $i->waitForText('admin', 30, '.mailpoet-listing-table');

    for ($index = 0; $index <= 5; $index++) {
      $i->waitForText('imported' . $index . '@from.wordpress');
    }

    $subscribersCount = (int)$i->grabTextFrom('.mailpoet-listing-pages-num');
    Assert::assertEquals('7', $subscribersCount);
  }
}
