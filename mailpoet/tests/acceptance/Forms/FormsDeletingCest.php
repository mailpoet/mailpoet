<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;

class FormsDeletingCest {
  public function moveFormToTrash(\AcceptanceTester $i) {
    $formName = 'Move to trash form';
    $form = new Form();
    $form->withName($formName)->create();

    $i->wantTo('Move a form to trash');

    $i->login();
    $i->amOnMailpoetPage('Forms');
    $i->waitForText($formName);

    $i->clickItemRowActionByItemName($formName, 'Move to trash');

    $i->changeGroupInListingFilter('trash');

    $i->waitForText($formName);
  }

  public function restoreFormFromTrash(\AcceptanceTester $i) {
    $formName = 'Restore from trash form';
    $form = new Form();
    $form->withName($formName)->withDeleted()->create();

    $i->wantTo('Restore a form from trash');

    $i->login();
    $i->amOnMailpoetPage('Forms');

    $i->changeGroupInListingFilter('trash');
    $i->waitForText($formName);

    $i->clickItemRowActionByItemName($formName, 'Restore');
    $i->waitForText('1 form has been restored from the trash.');
    $i->changeGroupInListingFilter('all');
    $i->waitForText($formName);
  }

  public function deleteFormPermanently(\AcceptanceTester $i) {
    $formName = 'Delete form permanently';
    $form = new Form();
    $form->withName($formName)->withDeleted()->create();
    $form->withName($formName . '2')->withDeleted()->create();

    $i->wantTo('Delete a form permanently trash');

    $i->login();
    $i->amOnMailpoetPage('Forms');

    $i->changeGroupInListingFilter('trash');
    $i->waitForText($formName);

    $i->clickItemRowActionByItemName($formName, 'Delete Permanently');

    $i->waitForText('1 form was permanently deleted.');
    $i->waitForElementNotVisible($formName);
    $i->waitForText($formName . '2');
  }

  public function emptyTrash(\AcceptanceTester $i) {
    $formName = 'Delete form permanently';
    $form = new Form();
    $form->withName($formName)->withDeleted()->create();
    $form = new Form();
    $form->withName($formName . '2')->create();

    $i->wantTo('Empty a trash on Forms page');

    $i->login();
    $i->amOnMailpoetPage('Forms');

    $i->changeGroupInListingFilter('trash');
    $i->waitForText($formName);

    $i->click('[data-automation-id="empty_trash"]');

    $i->waitForText('1 form was permanently deleted.');
    $i->waitForElementNotVisible($formName);
    $i->changeGroupInListingFilter('all');

    $i->waitForText($formName . '2');
  }
}
