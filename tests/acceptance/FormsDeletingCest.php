<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;

class FormsDeletingCest {

  public function moveFormToTrash(\AcceptanceTester $I) {
    $form_name = 'Move to trash form';
    $form = new Form();
    $form->withName($form_name)->create();

    $I->wantTo('Move a form to trash');

    $I->login();
    $I->amOnMailpoetPage('Forms');
    $I->waitForText($form_name);

    $I->clickItemRowActionByItemName($form_name, 'Move to trash');

    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');

    $I->waitForText($form_name);
  }

  public function restoreFormFromTrash(\AcceptanceTester $I) {
    $form_name = 'Restore from trash form';
    $form = new Form();
    $form->withName($form_name)->withDeleted()->create();

    $I->wantTo('Restore a form from trash');

    $I->login();
    $I->amOnMailpoetPage('Forms');

    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($form_name);

    $I->clickItemRowActionByItemName($form_name, 'Restore');
    $I->waitForText('1 form has been restored from the trash.');
    $I->waitForElement('[data-automation-id="filters_all"]');
    $I->click('[data-automation-id="filters_all"]');
    $I->waitForText($form_name);
  }

  public function deleteFormPermanently(\AcceptanceTester $I) {
    $form_name = 'Delete form permanently';
    $form = new Form();
    $form->withName($form_name)->withDeleted()->create();

    $I->wantTo('Delete a form permanently trash');

    $I->login();
    $I->amOnMailpoetPage('Forms');

    $I->waitForElement('[data-automation-id="filters_trash"]');
    $I->click('[data-automation-id="filters_trash"]');
    $I->waitForText($form_name);

    $I->clickItemRowActionByItemName($form_name, 'Delete Permanently');

    $I->waitForText('1 form was permanently deleted.');
    $I->waitForElementNotVisible($form_name);
  }

}
