<?php
namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

require_once __DIR__ . '/../DataFactories/Newsletter.php';

class BrowserPreviewNewsletterCest {
  function previewNewsletteerInBrowser(\AcceptanceTester $I) {
    $I->wantTo('Preview a newsletter in the browser from the newsletter editor page');
    $newsletter_title = 'Preview Newsletter';

    // step 1 - Prepare post notification data
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletter_title)
      ->withType('standard')
      ->create();
    
    // step 2 - Go to editor
    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->click(['css' => '.mailpoet_region.mailpoet_preview_region']);
    $I->click(['css' => '.mailpoet_show_preview']);
    $I->waitForText('Newsletter Preview');
    }
    
}