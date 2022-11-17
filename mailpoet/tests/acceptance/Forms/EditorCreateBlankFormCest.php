<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;

class EditorCreateBlankFormCest {
  public function createBlankForm(\AcceptanceTester $i) {
    $i->wantTo('Create a blamk form');
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segmentFactory->withName($segmentName)->create();

    $i->login();
    $i->amOnMailPoetPage('Forms');
    // Create a new form
    $formName = 'My awesome form';
    $i->click('[data-automation-id="create_new_form"]');
    $i->waitForElement('[data-automation-id="template_selection_list"]');
    $i->click('[data-automation-id="create_blank_form"]');
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->fillField('[data-automation-id="form_title_input"]', $formName);
    // Select list and save form
    $i->selectOptionInSelect2($segmentName);
    $i->saveFormInEditor();
    // Reload page and check data were saved
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->seeInField('[data-automation-id="form_title_input"]', $formName);
  }
}
