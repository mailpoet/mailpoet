<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class SavePostNotificationEmailAsTemplateCest {
  public function saveAsTemplate(\AcceptanceTester $i) {
    $i->wantTo('Save post notification email as template');

    $newsletterTitle = 'Template Test Post Notification';
    $templateTitle = 'Template Test Post Notification Title';
    $templateDescr = 'Template Test Post Notification Descr';

    // step 1 - Prepare post notification data
    $newsletterFactory = new Newsletter();
    $newsletter = $newsletterFactory->withSubject($newsletterTitle)
      ->withPostNotificationsType()
      ->create();

    // step 2 - Go to editor
    $i->login();
    $i->amEditingNewsletter($newsletter->getId());

    // step 3 - Save as template
    $saveTemplateOption = '[data-automation-id="newsletter_save_as_template_option"]';
    $saveTemplateButton = '[data-automation-id="newsletter_save_as_template_button"]';
    $i->click('[data-automation-id="newsletter_save_options_toggle"]');
    $i->waitForElement($saveTemplateOption);
    $i->click($saveTemplateOption);
    $i->waitForElement($saveTemplateButton);
    $i->fillField('template_name', $templateTitle);
    $i->click($saveTemplateButton);
    $i->waitForText('Template has been saved.');

    // step 4 - Use the new template
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="new_email"]');
    $i->click('[data-automation-id="create_notification"]');
    $i->waitForElement('[data-automation-id="post_notification_creation_heading"]');
    $i->click('Next');
    $i->checkTemplateIsPresent(0, 'notification');
    $i->scrollTo('[data-automation-id="templates-notification"]');
    $i->see('Template Test Post Notification Title');
    $i->click(['xpath' => '//*[text()="' . $templateTitle . '"]//ancestor::*[@data-automation-id="select_template_box"]//*[starts-with(@data-automation-id,"select_template_")]']);
    $i->waitForElement('[data-automation-id="newsletter_title"]');
  }
}
