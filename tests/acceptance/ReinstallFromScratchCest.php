<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;
use MailPoet\Test\DataFactories\Subscriber;
use PHPUnit_Framework_Assert as Asserts;

class ReinstallFromScratchCest {

  function reinstallFromScratch(\AcceptanceTester $I) {
    $I->wantTo('Reinstall from scratch');
    $I->login();

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
    for ($i = 0; $i <= 5; $i++) {
      wp_create_user('test' . $i, 'password', 'imported' . $i . '@from.wordpress');
    }

    // Step 2 - reinstall from scratch
    $I->amOnPage('/wp-admin/admin.php?page=mailpoet-settings#advanced');
    $I->waitForElement('#mailpoet_reinstall');
    $I->click('Reinstall now...');
    $I->acceptPopup();
    $I->waitForElement('#mailpoet_loading');
    $I->waitForElementNotVisible('#mailpoet_loading');

    // Step 3 - skip all tutorials, which could interfere with other tests
    $settings = new Settings();
    $settings->withSkippedTutorials();

    // Step 4 - check if data are emptied and repopulated
    // Check emails
    $I->amOnMailpoetPage('Emails');
    $I->waitForText('Nothing here yet!');
    $I->seeNumberOfElements('[data-automation-id^=listing_item_]', 0);
    // Check forms
    $I->amOnMailpoetPage('Forms');
    $I->waitForText('A GDPR friendly form', 30, '[data-automation-id="listing_item_1"]');
    $I->seeNumberOfElements('[data-automation-id^=listing_item_]', 1);
    // Check lists
    $I->amOnMailpoetPage('Lists');
    $I->waitForText('WordPress Users', 30, '[data-automation-id="listing_item_1"]');
    $I->see('WooCommerce Customers', '[data-automation-id="listing_item_2"]');
    $I->see('My First List', '[data-automation-id="listing_item_3"]');
    $I->seeNumberOfElements('[data-automation-id^=listing_item_]', 3);
    // Check subscribers
    $I->amOnMailPoetPage('Subscribers');
    $I->waitForText('admin', 30, '[data-automation-id="listing_item_1"]');
    $wp_users_count = count_users();
    $subscribers_count = (int)$I->grabTextFrom('.displaying-num');
    Asserts::assertEquals($wp_users_count['total_users'], $subscribers_count);

    $I->logOut(); // to force next test to login again, since DB will be repopulated again
    $I->cli('db query < /wp-core/wp-content/plugins/mailpoet/tests/_data/acceptanceDump.sql --allow-root');
    $settings->withDefaultSettings();
  }
}
