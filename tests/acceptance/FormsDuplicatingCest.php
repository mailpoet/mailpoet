<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;

class FormsDuplicatingCest {

  public function duplicateForm(\AcceptanceTester $I) {
    $form_name = 'Form for duplicate test';
    $form = new Form();
    $form->withName($form_name)->create();

    $I->wantTo('Duplicate a form');

    $I->login();
    $I->amOnMailpoetPage('Forms');
    $I->waitForText($form_name);

    $I->clickItemRowActionByItemName($form_name, 'Duplicate');

    $I->waitForText('has been duplicated');
    $I->waitForText('Copy of ' . $form_name);
  }

}
