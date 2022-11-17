<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;

class FormsListingCest {
  public function formsListing(\AcceptanceTester $i) {
    $i->wantTo('Open forms listings page');
    $formName = 'Test Form';
    $form = new Form();
    $form->withName($formName);
    $form->create();

    $i->login();
    $i->amOnMailpoetPage('Forms');
    $i->waitForText($formName, 5, '.mailpoet-listing-table');
    $i->seeNoJSErrors();
    $i->clickItemRowActionByItemName($formName, 'Move to trash');
    $i->waitForText('No forms were found. Why not create a new one?');
    $i->waitForElementVisible('[data-automation-id="add_new_form"]');
  }
}
