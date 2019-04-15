<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Settings;

require_once __DIR__ . '/../DataFactories/Newsletter.php';
require_once __DIR__ . '/../DataFactories/Settings.php';

class RevenueTrackingCookieCest {

  function cookieIsStoredOnClick(\AcceptanceTester $I) {
    $I->wantTo('Test Revenue cookie is saved');
    $newsletter_subject = 'Receive Test';
    $newsletter = (new Newsletter())->withSubject($newsletter_subject)->create();
    (new Settings())->withCookieRevenueTracking()->withTrackingEnabled();
    $segment_name = $I->createListWithSubscriber();

    $I->login();
    $I->amEditingNewsletter($newsletter->id);
    $I->click('Next');

    $I->waitForElement('[data-automation-id="newsletter_send_form"]');
    $I->selectOptionInSelect2($segment_name);
    $I->click('Send');
    $I->waitForElement('.mailpoet_progress_label');

    $I->logOut();
    $I->amOnMailboxAppPage();
    $I->waitForText($newsletter_subject);
    $I->click(Locator::contains('span.subject', $newsletter_subject));
    $I->switchToIframe('preview-html');
    $I->click('Read the post');
    $I->switchToNextTab();
    $I->canSeeCookie('mailpoet_revenue_tracking');
  }

}
