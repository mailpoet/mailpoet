<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Mailer\Mailer;
use MailPoet\Test\DataFactories\Settings;

class BasicsPageCest {
  public function checkSettingsPagesLoad(\AcceptanceTester $i) {
    $i->wantTo('Confirm all settings pages load correctly');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    //Basics Tab
    $i->waitForText('Basics');
    $i->seeNoJSErrors();
    //Sign-up Confirmation Tab
    $i->click('[data-automation-id="signup_settings_tab"]');
    $i->waitForText('Enable sign-up confirmation');
    $i->seeNoJSErrors();
    //Send With Tab
    $i->click('[data-automation-id="send_with_settings_tab"]');
    $i->waitForText('MailPoet Sending Service');
    $i->seeNoJSErrors();
    //Advanced Tab
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForText('Newsletter task scheduler');
    $i->seeNoJSErrors();
    //Activation Key Tab
    $i->click('[data-automation-id="activation_settings_tab"]');
    $i->waitForText('Activation Key');
    $i->seeNoJSErrors();
  }

  public function editDefaultSenderInformation(\AcceptanceTester $i) {
    $i->wantTo('Confirm default sender information can be edited');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->fillField('[data-automation-id="from-name-field"]', 'Sender');
    $i->fillField('[data-automation-id="from-email-field"]', 'sender@fake.fake');
    $i->fillField('[data-automation-id="reply_to-name-field"]', 'Reply Name');
    $i->fillField('[data-automation-id="reply_to-email-field"]', 'reply@fake.fake');
    //save settings
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');
  }

  public function allowSubscribeInComments(\AcceptanceTester $i) {
    $i->wantTo('Allow users to subscribe to lists in site comments');
    $postTitle = 'Hello world!';
    $comment = 'Hello!';
    $optinMessage = 'Yes, add me to your mailing list';
    $i->login();
    //go to settings and opt-in for comments
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="subscribe-on_comment-checkbox"]');
    $i->selectOptionInReactSelect('Newsletter mailing list', '[data-automation-id="subscribe-on_comment-segments-selection"]');
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForElementNotVisible('#mailpoet_loading');
    //go to the post and perform commenting + opting
    $i->amOnPage('/');
    $i->waitForText($postTitle);
    $i->click($postTitle);
    $i->waitForText($postTitle);
    $i->scrollTo(['css' => '.comment-form-comment'], 20, 50);
    $i->waitForText($optinMessage);
    $i->click('#mailpoet_subscribe_on_comment');
    $i->waitForElementVisible(['css' => '#comment']);
    $i->click(['css' => '#comment']);
    $i->fillField('#comment', $comment);
    //a patch for fixing flakyness
    try {
      $i->seeInField('#comment', $comment);
      $i->click('Post Comment');
      $i->waitForText($comment, 10, '.comment-content');
    }
    catch (\Exception $e) {
      $i->clearField('#comment');
      $i->fillField('#comment', $comment);
      $i->click('Post Comment');
      $i->waitForText($comment, 10, '.comment-content');
    }
    //check if user is really subscribed to a list
    $i->amOnMailpoetPage('Lists');
    $i->waitForText('Lists');
    $i->clickItemRowActionByItemName('Newsletter mailing list', 'View Subscribers');
    $i->waitForText('Subscribers');
    $i->waitForText('Status'); // fixing flakyness
    $i->click('[data-automation-id="filters_unconfirmed"]');
    $i->waitForText('test@test.com');
    //clear checkbox to hide Select2 from next test
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="subscribe-on_comment-checkbox"]');
    //save settings
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForElementNotVisible('#mailpoet_loading');
    //check to make sure comment subscription form is gone
    $i->amOnPage('/');
    $i->waitForText($postTitle);
    $i->click($postTitle);
    $i->dontSee($optinMessage);
  }

  public function checkSenderFreemailWarning(\AcceptanceTester $i) {
    $settings = new Settings();
    $settings->withSendingMethod(Mailer::METHOD_SMTP);
    $settings->withTodayInstallationDate();
    $nameField = '[data-automation-id="from-name-field"]';
    $emailField = '[data-automation-id="from-email-field"]';
    $i->wantTo('Confirm default sender information can be edited');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->fillField($nameField, 'Sender');
    $i->fillField($emailField, 'sender@email.com');
    $i->seeElement('[data-acceptance-id="freemail-sender-warning-old-installation"]');
    $i->see('contact@' . \AcceptanceTester::WP_DOMAIN);
    $i->fillField($emailField, 'sender@fake.fake');
    $i->dontseeElement('[data-acceptance-id="freemail-sender-warning-old-installation"]');

    $settings = new Settings();
    $settings->withSendingMethodMailPoet();
    $i->reloadPage();
    $i->acceptPopup();

    $i->fillField($emailField, 'sender2@email.com');
    $i->dontSeeElement('[data-acceptance-id="freemail-sender-warning-new-installation"]');
    $i->fillField($emailField, 'sender@fake.fake');
    $i->dontSeeElement('[data-acceptance-id="freemail-sender-warning-new-installation"]');
  }
}
