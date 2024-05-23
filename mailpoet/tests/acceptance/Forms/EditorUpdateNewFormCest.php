<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;

/**
 * @group frontend
 */
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
    $invalidEmail = 'invalid@';
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segmentFactory->withName($segmentName)->create();

    $i->login();

    $i->amOnMailPoetPage('Forms');

    $formName = 'My awesome form';
    $updatedFormName = 'My updated awesome form';
    $i->click('[data-automation-id="create_new_form"]');
    $i->waitForElement('[data-automation-id="template_selection_list"]');
    $i->click('[data-automation-id="select_template_template_1_popup"]');
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->clearField('[data-automation-id="form_title_input"]'); // Clear field due to flakiness
    $i->fillField('[data-automation-id="form_title_input"]', $formName);

    $i->wantTo('Try saving form without selected list');
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Please select a list', 10, '.automation-dismissible-notices');
    $i->seeNoJSErrors();

    $i->wantTo('Select list and save form');
    $i->selectOptionInSelect2($segmentName);
    $i->saveFormInEditor();

    $i->wantTo('Reload page and check data were saved');
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->seeInField('[data-automation-id="form_title_input"]', $formName);
    $i->seeSelectedInSelect2($segmentName);
    $i->seeNoJSErrors();

    $i->wantTo('Update form name and confirmation message');
    $i->fillField('[data-automation-id="form_title_input"]', $updatedFormName);
    $i->fillField('.components-textarea-control__input', $newConfMessage);

    $i->wantTo('Update success and error message colors');
    $i->click('[data-automation-id="mailpoet_form_settings_tab"]');
    $i->click('Styles');
    $i->click('Success');
    $i->selectPanelColor('[10]'); // Select Cyan blue
    $i->click('[data-automation-id="mailpoet_form_settings_tab"]');
    $i->click('Error');
    $i->selectPanelColor('[12]'); // Select Vivid purple
    $i->click('[data-automation-id="mailpoet_form_settings_tab"]');
    
    $i->saveFormInEditor();

    $i->wantTo('Reload page and check data were saved');
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->seeInField('[data-automation-id="form_title_input"]', $updatedFormName);
    $i->seeInField('.components-textarea-control__input', $newConfMessage);
    $i->seeNoJSErrors();

    $i->wantTo('Verify new form name in Forms list page and also new conf message and colors on the front end');
    $i->amOnMailpoetPage('Forms');
    $i->waitForText('Forms');
    $i->waitForText($updatedFormName);
    $i->amOnPage('/form-test');
    $i->waitForElement('.mailpoet_submit');
    $i->fillField('[data-automation-id="form_email"]', $invalidEmail);
    $i->click('.mailpoet_submit');
    $i->assertCssProperty('.parsley-type', 'color', 'rgba(155, 81, 224, 1)');
    $i->fillField('[data-automation-id="form_email"]', $subscriberEmail);
    $i->click('.mailpoet_submit');
    $i->waitForText($newConfMessage, self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $i->assertCssProperty('.mailpoet_validate_success', 'color', 'rgba(142, 209, 252, 1)');
    $i->seeNoJSErrors();
  }
}
