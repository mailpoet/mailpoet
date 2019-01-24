<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;

require_once __DIR__ . '/../DataFactories/Form.php';

class SubscriptionFormEditCest {

  function editForm(\AcceptanceTester $I) {

    //Step one - create form from factory
    $form_name = 'Testing Form Edit';
    $form_edited_name = 'Testing Form Edited';
    $form_factory = new Form();
    $form_factory->withName($form_name)->create();
    //Step two - Edit the form title
    $I->wantTo('Edit a form');
    $I->login();
    $I->amOnMailpoetPage('Forms');
    $I->waitForText($form_name);
    $I->clickItemRowActionByItemName($form_name, 'Edit');
    $title_element = '[data-automation-id="mailpoet_form_name_input"]';
    $I->waitForElement($title_element);
    $I->seeInCurrentUrl('mailpoet-form-editor');
    $I->fillField($title_element, $form_edited_name);
    $I->selectOptionInSelect2('My First List');
    $I->click('[data-automation-id="save_form"]');
    //Step three - assertions
    $I->waitForText('Saved!');
    $I->amOnMailpoetPage('Forms');
    $I->waitForText($form_edited_name);
  }

}
