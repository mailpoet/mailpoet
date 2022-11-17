<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\UserFlags;

class EditorTutorialCest {
  public function tutorial(\AcceptanceTester $i) {
    $i->wantTo('Prepare data');
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segment = $segmentFactory->withName($segmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$segment])->withDisplayBelowPosts()->create();
    $userFlags = new UserFlags(1);
    $userFlags->withFormEditorTutorialSeen('');

    $i->wantTo('Check form tutorial');
    $i->wantTo('Open the form editor');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');

    $i->wantTo('Check the tutorial is present');
    $i->waitForElement('[data-automation-id="form-editor-tutorial"]');
    $i->waitForElement('[data-automation-id="mailpoet-modal-close"]');
    $i->click('[data-automation-id="mailpoet-modal-close"]');

    $i->wantTo('Check the tutorial is not present after it has been dismissed');
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->dontSee('[data-automation-id="form-editor-tutorial"]');
  }
}
