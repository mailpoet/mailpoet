<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;

class FormsListingCest {
  public function formsListing(\AcceptanceTester $i) {
    $i->wantTo('Open forms listings page');
    $formName = 'Test Form';
    $newFormButton = '.mailpoet-button';
    $form = new Form();
    $form->withName($formName);
    $form->create();

    $i->login();
    $i->amOnMailpoetPage('Forms');
    $i->waitForText($formName, 5, '.mailpoet-listing-table');
    $i->seeNoJSErrors();
    $i->seeNumberOfElements($newFormButton, 1);
    $i->clickItemRowActionByItemName($formName, 'Move to trash');
    $i->waitForText('No forms were found. Why not create a new one?');
    $i->seeNumberOfElements($newFormButton, 2);
  }
}
