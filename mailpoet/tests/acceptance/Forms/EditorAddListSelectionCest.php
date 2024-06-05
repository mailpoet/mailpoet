<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Settings;

/**
 * @group frontend
 */
class EditorAddListSelectionCest {

  const CONFIRMATION_MESSAGE_TIMEOUT = 20;

  public function createCustomSelect(\AcceptanceTester $i) {

    $settings = new Settings();
    $settings->withConfirmationEmailEnabled();

    $i->wantTo('Add list selection block to the editor');

    $segmentFactory = new Segment();
    $firstSegmentName = 'First fancy list';
    $formSegment = $segmentFactory->withName($firstSegmentName)->create();

    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$formSegment])->withDisplayBelowPosts()->create();

    $secondSegmentName = 'Second fancy list';
    $segmentFactory->withName($secondSegmentName)->create();

    $thirdSegmentName = 'Third fancy list';
    $segmentFactory->withName($thirdSegmentName)->create();

    $subscriberEmail = 'test-form@example.com';

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
    $i->click('Block', '.editor-sidebar__panel-tabs');
    $i->fillField('[data-automation-id="settings_first_name_label_input"]', 'Choose your list:');
    $i->selectOption('[data-automation-id="select_list_selections_list"]', $firstSegmentName);
    $i->selectOption('[data-automation-id="select_list_selections_list"]', $secondSegmentName);
    $i->selectOption('[data-automation-id="select_list_selections_list"]', $thirdSegmentName);
    $i->checkOption('(//input[@class="components-checkbox-control__input"])[1]'); // Mark the first list as checked
    $i->checkOption('(//input[@class="components-checkbox-control__input"])[2]'); // Mark the second list as checked
    $i->checkOption('(//input[@class="components-checkbox-control__input"])[3]'); // Mark the third list as checked
    $i->seeNoJSErrors();

    $i->wantTo('Save the form');
    $i->saveFormInEditor();

    $i->wantTo('Reload the page and check that data were saved');
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="mailpoet_list_selection_block"]');
    $i->click('[data-automation-id="mailpoet_list_selection_block"]');
    $i->seeInField('[data-automation-id="settings_first_name_label_input"]', 'Choose your list:');
    $i->waitForText($firstSegmentName);
    $i->waitForText($secondSegmentName);
    $i->waitForText($thirdSegmentName);
    $i->seeCheckboxIsChecked('(//input[@class="components-checkbox-control__input"])[1]'); // Verify the first list is checked
    $i->seeCheckboxIsChecked('(//input[@class="components-checkbox-control__input"])[2]'); // Verify the second list is checked
    $i->seeCheckboxIsChecked('(//input[@class="components-checkbox-control__input"])[3]'); // Verify the third list is checked

    $i->wantTo('Go back to the forms list and verify the attached list');
    $i->amOnMailpoetPage('Forms');
    $i->waitForText($formName);
    $i->waitForText('User choice:');
    $i->waitForText($firstSegmentName);
    $i->waitForText($secondSegmentName);
    $i->waitForText($thirdSegmentName);

    $i->wantTo('Check list selection on front end');
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);
    $i->waitForText('Choose your list:');
    $i->waitForText($firstSegmentName);
    $i->waitForText($secondSegmentName);
    $i->waitForText($thirdSegmentName);
    $i->seeCheckboxIsChecked('(//input[@class="mailpoet_checkbox"])[1]'); // Verify the first list is checked
    $i->seeCheckboxIsChecked('(//input[@class="mailpoet_checkbox"])[2]'); // Verify the second list is checked
    $i->seeCheckboxIsChecked('(//input[@class="mailpoet_checkbox"])[3]'); // Verify the third list is checked

    $i->wantTo('Subscribe with list selection and confirm subscribed lists');
    $i->fillField('[data-automation-id="form_email"]', $subscriberEmail);
    $i->uncheckOption('(//input[@class="mailpoet_checkbox"])[3]'); // Uncheck the third list
    $i->click('.mailpoet_submit');
    $i->waitForText('Check your inbox or spam folder to confirm your subscription.', self::CONFIRMATION_MESSAGE_TIMEOUT, '.mailpoet_validate_success');
    $i->seeNoJSErrors();

    $i->wantTo('Make sure the subscriber is subscribed to those lists');
    $i->amOnMailPoetPage('Subscribers');
    $i->waitForListingItemsToLoad();
    $i->see($firstSegmentName);
    $i->see($secondSegmentName);
    $i->dontSee($thirdSegmentName);
  }
}
