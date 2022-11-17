<?php declare(strict_types = 1);

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

    $i->wantTo('Add columns block with 2 columns');
    $i->addFromBlockInEditor('Columns');
    $i->waitForElement('.block-editor-block-variation-picker__variations');
    $i->click('.block-editor-block-variation-picker__variations li:nth-child(2) button');
    $i->waitForElement('.block-editor-block-list__block');

    $i->wantTo('Add inputs into column');
    $this->addFieldInColumn($i, 'First name');
    $this->addFieldInColumn($i, 'Last name');
    $i->seeNoJSErrors();
    $i->saveFormInEditor();

    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->seeElement('[data-automation-id="editor_first_name_input"]');
    $i->seeElement('[data-automation-id="editor_last_name_input"]');

    $i->wantTo('Go to post page');
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);

    $i->wantTo('Subscribe using the form');
    $subscriberEmail = "subscriber_columns@example.com";
    $subscriberFirstName = "subscriber_columns_first_name";
    $subscriberLastName = "subscriber_columns_last_name";
    $i->fillField('[data-automation-id="form_email"]', $subscriberEmail);
    $i->fillField('[data-automation-id="form_first_name"]', $subscriberFirstName);
    $i->fillField('[data-automation-id="form_last_name"]', $subscriberLastName);
    $i->click('[data-automation-id="subscribe-submit-button"]');
    $i->waitForText($formMessage);

    $i->wantTo('Check subscriber data were saved');
    $i->amOnMailpoetPage('Subscribers');
    $i->waitForText($subscriberEmail);
    $i->waitForText($subscriberFirstName);
    $i->waitForText($subscriberLastName);
  }

  private function addFieldInColumn(\AcceptanceTester $i, $name) {
    $i->click('(//button[@class="components-button block-editor-button-block-appender"])[1]');
    $blockInserterSearchInput = '.block-editor-inserter__search .components-search-control__input';
    $i->waitForElementVisible($blockInserterSearchInput);
    $i->fillField($blockInserterSearchInput, $name);
    $i->waitForText($name, 5, '.block-editor-block-types-list__item-title');
    $i->click($name, '.block-editor-block-types-list__list-item');
  }
}
