<?php

namespace MailPoet\Test\Acceptance;

class SaveNewsletterAsDraftCest {

  public function saveStandardNewsletterAsDraft(\AcceptanceTester $I) {
    $I->wantTo('Create standard newsletter and save as a draft');

    $newsletter_title = 'Testing Newsletter ' . \MailPoet\Util\Security::generateRandomString();
    $segment_name = $I->createListWithSubscriber();

    $I->login();
    $I->amOnMailpoetPage('Emails');

    // step 1 - select notification type
    $I->click('[data-automation-id="create_standard"]');

    // step 2 - select template
    $standard_template = '[data-automation-id="select_template_0"]';
    $I->waitForElement($standard_template);
    $I->see('Newsletters', ['css' => 'a.current']);
    $I->click($standard_template);

    // step 3 - design newsletter (update subject)
    $title_element = '[data-automation-id="newsletter_title"]';
    $I->waitForElement($title_element);
    $I->fillField($title_element, $newsletter_title);
    $I->click('Next');

    // step 4 - Choose list and send
    $send_form_element = '[data-automation-id="newsletter_send_form"]';
    $I->waitForElement($send_form_element);
    $I->selectOptionInSelect2($segment_name);
    $I->click('Save as draft and close');
    $I->waitForText($newsletter_title);
  }

}
