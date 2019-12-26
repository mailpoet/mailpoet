<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class SaveNewsletterAsTemplateCest {

  public function saveStandardNewsletterAsTemplate(\AcceptanceTester $I) {
    $I->wantTo('Create standard newsletter and save as a template');

    $newsletter_title = 'Testing Templates ' . \MailPoet\Util\Security::generateRandomString();
    $template_name = 'Magical Unicorn Test Template';
    $template_description = 'This is a description';

    // step 1 - Prepare post notification data
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletter_title)->create();

    // step 2 - Go to editor
    $I->login();
    $I->amEditingNewsletter($newsletter->id);

    //step 3 - save as a template
    $I->click('[data-automation-id="newsletter_save_options_toggle"]');
    $I->click('[data-automation-id="newsletter_save_as_template_option"]');
    $I->fillField(['name' => 'template_name'], $template_name);
    $I->fillField(['name' => 'template_description'], $template_description);
    $I->click('[data-automation-id="newsletter_save_as_template_button"]');
    $I->waitForText('Template has been saved');

    //step 4 - confirm template can be used
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="new_email"]');
    $I->click('[data-automation-id="create_standard"]');
    $I->waitForText('Newsletters');

    $I->waitForElement('[data-automation-id="select_template_0"]');
    $I->see('Magical Unicorn Test Template');
    $I->click(['xpath' => '//*[text()="' . $template_name . '"]//ancestor::*[@data-automation-id="select_template_box"]//*[starts-with(@data-automation-id,"select_template_")]']);
    $I->waitForElement('[data-automation-id="newsletter_title"]');
  }

}
