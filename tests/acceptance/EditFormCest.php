<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;

require_once __DIR__ . '/../DataFactories/Form.php';

class EditFormCest {

  function editForm(\AcceptanceTester $I) {
    
    //Step one - create form from factory
    $form_name = 'Edit Form Test';
    $form_edited_name = 'Edited Form Test';
    $form = new Form();
    $form->withName($form_name)->create();
    
    //Step two - Edit the form title
    $I->wantTo('Edit a form');
    $I->login();
    $I->amOnMailpoetPage('Forms');
    $I->waitForText($form_name);
    $I->clickItemRowActionByItemName($newsletter_title, 'Edit');
    $title_element = '[data-automation-id="mailpoet_form_name_input"]';
    $I->waitForElement($title_element);
    $I->seeInCurrentUrl('mailpoet-form-editor');
    $I->fillField($title_element, $form_edited_name);
    $I->click('Save');
    
    //Step three - assertions
    $I->waitForText('Saved! Add this form to a widget.');
    $I->amOnMailpoetPage('Forms');
    $I->waitForText($form_edited_name);
    }
}