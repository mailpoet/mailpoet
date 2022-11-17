<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;

class FormsDuplicatingCest {
  public function duplicateForm(\AcceptanceTester $i) {
    $formName = 'Form for duplicate test';
    $form = new Form();
    $form->withName($formName)->create();

    $i->wantTo('Duplicate a form');

    $i->login();
    $i->amOnMailpoetPage('Forms');
    $i->waitForText($formName);

    $i->clickItemRowActionByItemName($formName, 'Duplicate');

    $i->waitForText('has been duplicated');
    $i->waitForText('Copy of ' . $formName);
  }
}
