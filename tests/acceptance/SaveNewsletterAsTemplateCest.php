<?php

namespace MailPoet\Test\Acceptance;

class SaveNewsletterAsTemplateCest {
  function saveNewsletterAsTemplate(\AcceptanceTester $I) {
    $I->wantTo('Save a standard newsletter as a template');

    $newsletter_title = 'Template Save Test ' . \MailPoet\Util\Security::generateRandomString();

    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id=\'new_email\']');

    // step 1 - select notification type
    $I->seeInCurrentUrl('#/new');
    $I->click('[data-automation-id=\'create_standard\']');

    // step 2 - select template
    $standard_template = '[data-automation-id=\'select_template_0\']';
    $I->waitForElement($standard_template);
    $I->see('Newsletters', ['css' => 'a.current']);
    $I->seeInCurrentUrl('#/template');
    $I->click($standard_template);

    // step 3 - design newsletter (update subject)
    $title_element = '[data-automation-id=\'newsletter_title\']';
    $I->waitForElement($title_element);
    $I->seeInCurrentUrl('mailpoet-newsletter-editor');
    $I->fillField($title_element, $newsletter_title);
    $I->click(['css' => '.button-primary.mailpoet_save_show_options']);
    $I->click( 'Save as template', '.mailpoet_save_option');
    $I->fillField('template_name', 'Template Test ' .$rand);
    $I->fillField('template_description', 'This is a description.');
    $I->wait(5);
    $I->click('save_as_template');
    $I->waitForText('Template has been saved.', '.mailpoet_notice');
  }
}

