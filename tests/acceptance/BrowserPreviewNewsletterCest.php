<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class BrowserPreviewNewsletterCest {
  function previewNewsletteerInBrowser(\AcceptanceTester $I) {
    $I->wantTo('Preview a newsletter in the browser from the newsletter editor page');
    $newsletter_title = 'Preview Newsletter';
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletter_title)
      ->withType('standard')
      ->create();
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->click(['css' => '.mailpoet_region.mailpoet_preview_region']);
    $I->waitForElement('[data-automation-id="preview_newsletter_in_browser_button"]',5);
    $I->click("View in browser");
    $I->waitForText('Newsletter Preview',15);
  }
    
}