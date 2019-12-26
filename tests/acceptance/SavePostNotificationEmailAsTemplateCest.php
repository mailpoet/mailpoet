<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class SavePostNotificationEmailAsTemplateCest {

  public function saveAsTemplate(\AcceptanceTester $I) {
    $I->wantTo('Save post notification email as template');

    $newsletter_title = 'Template Test Post Notification';
    $template_title = 'Template Test Post Notification Title';
    $template_descr = 'Template Test Post Notification Descr';

    // step 1 - Prepare post notification data
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletter_title)
      ->withPostNotificationsType()
      ->create();

    // step 2 - Go to editor
    $I->login();
    $I->amEditingNewsletter($newsletter->id);

    // step 3 - Save as template
    $save_template_option = '[data-automation-id="newsletter_save_as_template_option"]';
    $save_template_button = '[data-automation-id="newsletter_save_as_template_button"]';
    $I->click('[data-automation-id="newsletter_save_options_toggle"]');
    $I->waitForElement($save_template_option);
    $I->click($save_template_option);
    $I->waitForElement($save_template_button);
    $I->fillField('template_name', $template_title);
    $I->fillField('template_description', $template_descr);
    $I->click($save_template_button);
    $I->waitForText('Template has been saved.');

    // step 4 - Use the new template
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id="new_email"]');
    $I->click('[data-automation-id="create_notification"]');
    $I->waitForText('Latest Post Notifications');
    $I->click('Next');
    $I->waitForElement('[data-automation-id="select_template_0"]');
    $I->see('Template Test Post Notification Title');
    $I->click(['xpath' => '//*[text()="' . $template_title . '"]//ancestor::*[@data-automation-id="select_template_box"]//*[starts-with(@data-automation-id,"select_template_")]']);
    $I->waitForElement('[data-automation-id="newsletter_title"]');
  }

}
