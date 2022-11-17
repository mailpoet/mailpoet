<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class EditorFormPreviewCest {
  public function previewUnsavedChangesAndRememberPreviewSettings(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segment = $segmentFactory->withName($segmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$segment])->create();
    $i->wantTo('Add first name to the editor and preview form without saving it');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');

    $i->addFromBlockInEditor('First name');

    // Open preview
    $i->click('[data-automation-id="form_preview_button"]');
    $i->waitForElement('[data-automation-id="form_preview_iframe"]');

    // Check first name was rendered in iframe
    $i->switchToIFrame('[data-automation-id="form_preview_iframe"]');
    $i->waitForElement('[data-automation-id="form_first_name"]');
    $i->switchToIFrame();

    // Change preview type and form type and check again
    $formTypeSelect = '[data-automation-id="form_type_selection"]';
    $i->click('[data-automation-id="preview_type_mobile"]');
    $i->selectOption($formTypeSelect, 'Fixed bar');
    $i->switchToIFrame('[data-automation-id="form_preview_iframe"]');
    $i->waitForElement('[data-automation-id="form_first_name"]');
    $i->switchToIFrame();

    // Reload page and check preview settings
    $i->reloadPage();
    $i->acceptPopup();
    $i->waitForElement('[data-automation-id="form_preview_button"]');
    $i->click('[data-automation-id="form_preview_button"]');
    $i->waitForElement('[data-automation-id="form_preview_iframe"]');
    $i->seeOptionIsSelected($formTypeSelect, 'Fixed bar');
  }
}
