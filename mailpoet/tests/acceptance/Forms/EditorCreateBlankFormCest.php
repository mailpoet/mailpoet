<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;

class EditorCreateBlankFormCest {
  public function createBlankForm(\AcceptanceTester $i) {
    $i->wantTo('Create a blank form');

    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segmentFactory->withName($segmentName)->create();

    $i->login();

    $i->amOnMailPoetPage('Forms');

    // Create a new form
    $formName = 'My awesome form';
    $i->click('[data-automation-id="create_new_form"]');
    $i->waitForElement('[data-automation-id="template_selection_list"]');
    $i->click('[data-automation-id="create_blank_form"]');
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->fillField('[data-automation-id="form_title_input"]', $formName);

    // Verify the elements of a blank form
    $i->seeElement('[data-automation-id="editor_email_input"]');
    $i->seeElement('[data-automation-id="editor_submit_input"]');
    $i->dontSeeElement('[data-automation-id="editor_first_name_input"]');
    $i->dontSeeElement('[data-automation-id="editor_divider_block"]');
    $i->dontSeeElement('[data-automation-id="editor_spacer_block"]');

    // Verify the form default toggles
    $i->seeCheckboxIsChecked('.components-form-toggle__input');
    $i->click('[data-automation-id="form_preview_button"]');
    $i->waitForElementVisible('[data-automation-id="form_type_selection"]');
    $i->seeOptionIsSelected('[data-automation-id="form_type_selection"]', 'Others (widget)');
    $i->click('[data-automation-id="mailpoet-modal-close"]');

    // Select list and save form
    $i->waitForElementVisible('[data-automation-id="editor_submit_input"]');
    $i->selectOptionInSelect2($segmentName);
    $i->saveFormInEditor();

    // Reload page and check data were saved
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->seeInField('[data-automation-id="form_title_input"]', $formName);
  }
}
