<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class SaveNewsletterAsTemplateCest {
  public function saveStandardNewsletterAsTemplate(\AcceptanceTester $i) {
    $i->wantTo('Create standard newsletter and save as a template');

    $newsletterTitle = 'Testing Templates ' . \MailPoet\Util\Security::generateRandomString();
    $templateName = 'Magical Unicorn Test Template';
    $templateDescription = 'This is a description';

    // step 1 - Prepare post notification data
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletterTitle)->create();

    // step 2 - Go to editor
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());

    //step 3 - save as a template
    $i->click('[data-automation-id="newsletter_save_options_toggle"]');
    $i->click('[data-automation-id="newsletter_save_as_template_option"]');
    $i->fillField(['name' => 'template_name'], $templateName);
    $i->click('[data-automation-id="newsletter_save_as_template_button"]');
    $i->waitForText('Template has been saved');

    //step 4 - confirm template can be used
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="new_email"]');
    $i->click('[data-automation-id="create_standard"]');
    $i->waitForText('Newsletters');

    $i->checkTemplateIsPresent(0);
    $i->scrollTo('[data-automation-id="templates-standard"]');
    $i->see('Magical Unicorn Test Template');
    $i->click(['xpath' => '//*[text()="' . $templateName . '"]//ancestor::*[@data-automation-id="select_template_box"]//*[starts-with(@data-automation-id,"select_template_")]']);
    $i->waitForElement('[data-automation-id="newsletter_title"]');
  }
}
