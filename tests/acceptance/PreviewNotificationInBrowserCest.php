<?php
namespace MailPoet\Test\Acceptance;
 use MailPoet\Test\DataFactories\Newsletter;
 require_once __DIR__ . '/../DataFactories/Newsletter.php';
 class BrowserPreviewNotificationCest {
  function previewNotificationInBrowser(\AcceptanceTester $I) {
    
    //step one - create notifcation data
    $I->wantTo('Preview a notification in the browser from the newsletter editor page');
    $newsletter_title = 'Preview Notification';
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletter_title)
      ->withType('notification')
      ->withPostNoticationOptions()
      ->create();
    
    //step two - and now for some fancy previewing
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->click(['css' => '.mailpoet_region.mailpoet_preview_region']);
    $I->wait(5);
    $I->click('View in browser');
    $I->wait (10);
    $I->waitForText('Newsletter Preview');
    }
    
}