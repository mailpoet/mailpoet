<?php

namespace MailPoet\Test\Acceptance;

class SaveNewsletterAsTemplateCest {
  function saveStandardNewsletterAsTemplate(\AcceptanceTester $I) {
    $I->wantTo('Create standard newsletter and save as a template');

    $newsletter_title = 'Testing Templates ' . \MailPoet\Util\Security::generateRandomString();
    $template_name = 'Magical Unicorn Test Template';
    $template_description = 'This is a description';

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
    $I->waitForElement($title_element, 30);
    $I->seeInCurrentUrl('mailpoet-newsletter-editor');
    $I->fillField($title_element, $newsletter_title);
    
    //step 4 - save as a template
    $I->click(['css'=>'.dashicons.mailpoet_save_show_options_icon']);
    $I->click('[data-automation-id="newsletter_save_as_template_option"]');
    $I->fillField(['name' => 'template_name'], $template_name);
    $I->fillField(['name' => 'template_description'], $template_description);
    $I->click('[data-automation-id="newsletter_save_as_template_button"]');
    $I->waitForText('Template has been saved', 30);
    }
}