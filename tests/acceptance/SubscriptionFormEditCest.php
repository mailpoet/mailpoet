<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;

class SubscriptionFormEditCest {

  public function editForm(\AcceptanceTester $i) {

    //Step one - create form from factory
    $formName = 'Testing Form Edit';
    $formEditedName = 'Testing Form Edited';
    $formFactory = new Form();
    $form = $formFactory->withName($formName)->create();
    //Step two - Edit the form title
    $i->wantTo('Edit a form');
    $i->login();
    $i->amOnPage('/wp-admin/admin.php?page=mailpoet-form-editor&legacy=1&id=' . $form->id);
    $titleElement = '[data-automation-id="mailpoet_form_name_input"]';
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $formEditedName);
    $i->selectOptionInSelect2('My First List');
    $i->click('[data-automation-id="save_form"]');
    //Step three - assertions
    $i->waitForText('Saved!');
    $i->amOnMailpoetPage('Forms');
    $i->waitForText($formEditedName);
  }

}
