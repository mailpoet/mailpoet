<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;

class EditorUpdateNewFormCest {

  const CONFIRMATION_MESSAGE_TIMEOUT = 20;

  public function _before(\AcceptanceTester $i) {
    $settings = new Settings();
    $settings
      ->withConfirmationEmailSubject()
      ->withConfirmationEmailBody()
      ->withConfirmationEmailEnabled();
    $i->havePostInDatabase([
      'post_author' => 1,
      'post_type' => 'page',
      'post_name' => 'form-test',
      'post_title' => 'Form Test',
      'post_content' => '
        Regular form:
          [mailpoet_form id="1"]
      ',
      'post_status' => 'publish',
    ]);
  }

  public function updateNewForm(\AcceptanceTester $i) {
    $i->wantTo('Create and update form');

    $newConfMessage = 'Hey, this is the updated conf message.';
    $subscriberEmail = 'test-form@example.com';
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segmentFactory->withName($segmentName)->create();

    $i->login();
    $i->amOnMailPoetPage('Forms');
    // Create a new form
    $formName = 'My awesome form';
    $updatedFormName = 'My updated awesome form';
    $i->click('[data-automation-id="create_new_form"]');
    $i->waitForElement('[data-automation-id="template_selection_list"]');
    $i->click('[data-automation-id="select_template_template_1_popup"]');
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->clearField('[data-automation-id="form_title_input"]'); // Clear field due to flakiness
    $i->fillField('[data-automation-id="form_title_input"]', $formName);
    // Try saving form without selected list
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Please select a list', 10, '.automation-dismissible-notices');
    $i->seeNoJSErrors();
    // Select list and save form
    $i->selectOptionInSelect2($segmentName);
    $i->saveFormInEditor();
    // Reload page and check data were saved
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->seeInField('[data-automation-id="form_title_input"]', $formName);
    $i->seeSelectedInSelect2($segmentName);
    $i->seeNoJSErrors();
    // Update form name and confirmation message
    $i->fillField('[data-automation-id="form_title_input"]', $updatedFormName);
    $i->fillField('.components-textarea-control__input', $newConfMessage);
    $i->saveFormInEditor();
    // Reload page and check data were saved
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->seeInField('[data-automation-id="form_title_input"]', $updatedFormName);
    $i->seeInField('.components-textarea-control__input', $newConfMessage);
    $i->seeNoJSErrors();
    // Verify new form name in Forms list page and also new conf message on the front end
    $i->amOnMailpoetPage('Forms');
    $i->waitForText('Forms');
    $i->waitForText($updatedFormName);
    $i->amOnPage('/form-test');
    $i->fillField('[data-automation-id="form_email"]', $subscriberEmail);
    $i->click('.mailpoet_submit');
    $i->waitForText($newConfMessage, self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $i->seeNoJSErrors();
  }
}
