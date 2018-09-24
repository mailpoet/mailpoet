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
    $form = $form_factory->withName($form_name)->create();
    //Step two - Edit the form title
    $I->wantTo('Edit a form');
    $I->login();
    $I->amOnMailpoetPage('Forms');
    $I->waitForText($form_name, 10);
    $I->clickItemRowActionByItemName($form_name, 'Edit');
    $title_element = '[data-automation-id="mailpoet_form_name_input"]';
    $I->waitForElement($title_element, 10);
    $I->seeInCurrentUrl('mailpoet-form-editor');
    $I->fillField($title_element, $form_edited_name);
    $search_field_element = 'input.select2-search__field';
    $I->fillField($search_field_element, 'My First List');
    $I->pressKey($search_field_element, \WebDriverKeys::ENTER);
    $I->click('[data-automation-id="save_form"]');
    //Step three - assertions
    $I->waitForText('Saved! Add this form to a widget.', 10);
    $I->amOnMailpoetPage('Forms');
    $I->waitForText($form_edited_name);
    }
}