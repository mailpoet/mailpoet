<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class EditorAddListSelectionCest {
  public function createCustomSelect(\AcceptanceTester $i) {
    $i->wantTo('Add list selection block to the editor');
    $segmentFactory = new Segment();
    $firstSegmentName = 'First fancy list';
    $formSegment = $segmentFactory->withName($firstSegmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$formSegment])->withDisplayBelowPosts()->create();
    $secondSegmentName = 'Second fancy list';
    $segmentFactory->withName($secondSegmentName)->create();

    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');

    $i->wantTo('Insert list selection block');
    $i->addFromBlockInEditor('List selection');

    $i->wantTo('Verify that user must choose a list');
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Please select a list');

    $i->wantTo('Configure list selection block');
    $i->waitForElement('[data-automation-id="mailpoet_list_selection_block"]');
    $i->click('[data-automation-id="mailpoet_list_selection_block"]');
    $i->click('[data-automation-id="mailpoet_block_settings_tab"]');
    $i->fillField('[data-automation-id="settings_first_name_label_input"]', 'Choose your list:');
    $i->selectOption('[data-automation-id="select_list_selections_list"]', $secondSegmentName);
    $i->seeNoJSErrors();

    $i->wantTo('Save the form');
    $i->saveFormInEditor();

    $i->wantTo('Reload the page and check that data were saved');
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="mailpoet_list_selection_block"]');
    $i->click('[data-automation-id="mailpoet_list_selection_block"]');
    $i->seeInField('[data-automation-id="settings_first_name_label_input"]', 'Choose your list:');
    $i->waitForText($secondSegmentName);

    $i->wantTo('Go back to the forms list and verify the attached list');
    $i->amOnMailpoetPage('Forms');
    $i->waitForText($formName);
    $i->waitForText('User choice:');
    $i->waitForText($secondSegmentName);

    $i->wantTo('Check list selection on front end');
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);
    $i->waitForText('Choose your list:');
    $i->waitForText($secondSegmentName);
  }
}
