<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;

class FormsListingCest {

  public function formsListing(\AcceptanceTester $I) {
    $form = new Form();
    $form->withName('Test Form');
    $form->create();

    $I->wantTo('Open forms listings page');

    $I->login();
    $I->amOnMailpoetPage('Forms');

    $I->waitForText('Test Form', 5, '.mailpoet_listing_table');
    $I->seeNoJSErrors();
  }

}
