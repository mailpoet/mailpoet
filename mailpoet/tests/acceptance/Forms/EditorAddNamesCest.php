<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class EditorAddNamesCest {
  public function addNamesToAForm(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segment = $segmentFactory->withName($segmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$segment])->withDisplayBelowPosts()->create();
    $firstNameLabelModified = 'Your First Name';
    $lastNameLabelModified = 'Your Last Name';

    $i->wantTo('Add first and last name to the editor');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');

    $i->wantTo('Add First & Last name blocks');
    $i->addFromBlockInEditor('First name');
    $i->addFromBlockInEditor('Last name');

    $i->wantTo('Modify First & Last name blocks');
    $firstNameLabelInput = '[data-automation-id="settings_first_name_label_input"]';
    $this->openBlockSettings($i, '[data-automation-id="editor_first_name_input"]', $firstNameLabelInput);
    $i->fillField($firstNameLabelInput, $firstNameLabelModified);
    $lastNameLabelInput = '[data-automation-id="settings_last_name_label_input"]';
    $this->openBlockSettings($i, '[data-automation-id="editor_last_name_input"]', $lastNameLabelInput);
    $i->fillField($lastNameLabelInput, $lastNameLabelModified);
    $i->saveFormInEditor();

    $i->wantTo('Reload page and check data were saved');
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $this->openBlockSettings($i, '[data-automation-id="editor_first_name_input"]', $firstNameLabelInput);
    $i->seeInField($firstNameLabelInput, $firstNameLabelModified);
    $this->openBlockSettings($i, '[data-automation-id="editor_last_name_input"]', $lastNameLabelInput);
    $i->seeInField($lastNameLabelInput, $lastNameLabelModified);

    $i->wantTo('Check first & last names on front end');
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);
    $i->assertAttributeContains('[data-automation-id="form_first_name"]', 'placeholder', $firstNameLabelModified);
    $i->assertAttributeContains('[data-automation-id="form_last_name"]', 'placeholder', $lastNameLabelModified);
  }

  /**
   * Clicks on block ($blockElement) within the block editor area
   * and waits until some element is rendered in the sidebar
   * Sometimes click doesn't open block settings. Can be caused by some react rerendering.
   */
  private function openBlockSettings(\AcceptanceTester $i, $blockElement, $sidebarElement) {
    for ($retry = 0; $retry < 3; $retry++) {
      try {
        $i->click($blockElement);
        $i->waitForElement($sidebarElement);
      } catch (\Exception $e) {
        $i->wait(0.2); // Wait for potential re-rendering
        continue;
      }
    }
  }
}
