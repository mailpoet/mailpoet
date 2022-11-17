<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class EditorCustomClassesCest {
  public function setCustomClassName(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segment = $segmentFactory->withName($segmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $classNames = 'my-class1 myclass-2';
    $form->withName($formName)->withSegments([$segment])->withDisplayBelowPosts()->create();
    $i->wantTo('Set custom class name to email input');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');

    // Add class name to email block
    $i->click('[data-type="mailpoet-form/email-input"]');
    $i->waitForElement('.block-editor-block-inspector__advanced'); // Wait for advanced settings panel
    $i->click('.block-editor-block-inspector__advanced button'); // Open the panel
    $i->fillField('.block-editor-block-inspector__advanced div:first-child input', $classNames);

    // Check element has proper classes
    $i->assertAttributeContains('[data-type="mailpoet-form/email-input"] .mailpoet_paragraph', 'class', $classNames);

    // Save form
    $i->saveFormInEditor();

    // Reload page and check data were saved
    $i->reloadPage();
    $i->assertAttributeContains('[data-type="mailpoet-form/email-input"] .mailpoet_paragraph', 'class', $classNames);

    // Check that classes are applied on frontend page
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);
    $i->assertAttributeContains('//*[@data-automation-id="form_email"]/parent::div', 'class', $classNames);
  }
}
