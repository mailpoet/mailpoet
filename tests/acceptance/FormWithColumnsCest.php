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

    $i->addFromBlockInEditor('Columns');
    // Select first variant (2 columns)
    $i->waitForElement('.block-editor-block-variation-picker__variations');
    $i->click('.block-editor-block-variation-picker__variations li:first-child button');
    $i->waitForElement('.block-editor-block-list__block');
    // Add inputs into column
    $i->addFromBlockInEditor('First name', '.block-editor-block-list__block');
    $i->addFromBlockInEditor('Last name', '.block-editor-block-list__block');
    $i->seeNoJSErrors();
    $i->saveFormInEditor();
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
