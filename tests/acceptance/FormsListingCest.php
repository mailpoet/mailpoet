<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;

class FormsListingCest {
  public function formsListing(\AcceptanceTester $i) {
    $form = new Form();
    $form->withName('Test Form');
    $form->create();

    $i->wantTo('Open forms listings page');

    $i->login();
    $i->amOnMailpoetPage('Forms');

    $i->waitForText('Test Form', 5, '.mailpoet-listing-table');
    $i->seeNoJSErrors();
  }
}
