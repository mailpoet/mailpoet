<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class FormWithColumnsCest {
  public function createAndTestFormWithColumns(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segment = $segmentFactory->withName($segmentName)->create();
    $formName = 'My fancy form with columns';
    $formMessage = 'Form submitted';
    $form = new Form();
    $form->withName($formName)
      ->withSegments([$segment])
      ->withSuccessMessage($formMessage)
      ->withDisplayBelowPosts()
      ->create();
    $i->wantTo('Add columns with firs and last name');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');

    $i->click('.block-list-appender button');// CLICK the big button that adds new blocks
    $i->waitForElement('.block-editor-inserter__results .components-panel__body-toggle');
    $i->click('.block-editor-inserter__results .components-panel__body:nth-child(1) .components-panel__body-toggle'); // toggle layout
    $i->click('.editor-block-list-item-columns'); // columns block
    $i->waitForElement('.block-editor-block-variation-picker__variations');
    $i->click('.block-editor-block-variation-picker__variations li:first-child button');
    $i->waitForElement('.block-editor-inner-blocks');
    $i->click('.block-editor-inner-blocks .block-list-appender button'); // CLICK the big button in column that adds new blocks
    $i->click('.block-editor-inserter__results .components-panel__body:nth-child(3) .components-panel__body-toggle'); // toggle fields
    $i->click('.editor-block-list-item-mailpoet-form-first-name-input'); // add first name block to the editor
    $i->click('.block-editor-inner-blocks .block-list-appender button');// CLICK the big button in column that adds new blocks
    $i->waitForElement('.block-editor-inserter__results .components-panel__body-toggle');
    $i->click('.block-editor-inserter__results .components-panel__body:nth-child(3) .components-panel__body-toggle'); // toggle fields, get the second field, first one is now "Most Used"
    $i->click('.editor-block-list-item-mailpoet-form-last-name-input'); // add last name block to the editor
    $i->seeNoJSErrors();
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');
    // Reload page and check data were saved
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->seeElement('[data-automation-id="editor_first_name_input"]');
    $i->seeElement('[data-automation-id="editor_last_name_input"]');

    // Go to post page
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);

    // Subscribe using the form
    $subscriberEmail = "subscriber_columns@example.com";
    $subscriberFirstName = "subscriber_columns_first_name";
    $subscriberLastName = "subscriber_columns_last_name";
    $i->fillField('[data-automation-id="form_email"]', $subscriberEmail);
    $i->fillField('[data-automation-id="form_first_name"]', $subscriberFirstName);
    $i->fillField('[data-automation-id="form_last_name"]', $subscriberLastName);
    $i->click('[data-automation-id="subscribe-submit-button"]');
    $i->waitForText($formMessage);

    // Check subscriber data were saved
    $i->amOnMailpoetPage('Subscribers');
    $i->waitForText($subscriberEmail);
    $i->waitForText($subscriberFirstName);
    $i->waitForText($subscriberLastName);
  }
}
