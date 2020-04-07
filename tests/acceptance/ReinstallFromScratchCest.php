<?php

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
    $i->waitForText('Welcome! Let’s get you started on the right foot.');

    // Step 3 - skip all tutorials, which could interfere with other tests
    $settings = new Settings();
    $settings->withSkippedTutorials();

    // Step 4 - check if data are emptied and repopulated
    // Check emails
    $i->amOnMailpoetPage('Emails');
    $i->seeInCurrentUrl('#/new');
    // Check forms
    $i->amOnMailpoetPage('Forms');
    $i->waitForText('My First Form', 30, '[data-automation-id="listing_item_1"]');
    $i->seeNumberOfElements('[data-automation-id^=listing_item_]', 1);
    // Check lists
    $i->amOnMailpoetPage('Lists');
    $i->waitForText('WordPress Users', 30, '[data-automation-id="listing_item_1"]');
    $i->see('My First List', '[data-automation-id="listing_item_3"]');
    $i->seeNumberOfElements('[data-automation-id^=listing_item_]', 2);
    // Check subscribers
    $i->amOnMailPoetPage('Subscribers');
    $i->waitForText('admin', 30, '.mailpoet_listing_table');
    $wpUsersCount = count_users();
    $subscribersCount = (int)$i->grabTextFrom('.displaying-num');
    Assert::assertEquals($wpUsersCount['total_users'], $subscribersCount);
  }
}
