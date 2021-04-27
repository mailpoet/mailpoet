<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Segment;

class Forms_CreateFormUsingTemplateCest {
  public function createFormUsingTemplate(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segmentFactory->withName($segmentName)->create();

    $i->wantTo('Create a new form');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->click('[data-automation-id="create_new_form"]');

    $i->waitForElement('[data-automation-id="template_selection_list"]');
    $i->waitForElement('[data-automation-id="select_template_template_1_popup"]');
    $i->wantTo('Switch template category and crete a form');
    $i->click('[data-title="Fixed bar"]');
    $i->waitForElement('[data-automation-id="select_template_template_1_fixed_bar"]');
    $i->click('[data-automation-id="select_template_template_1_fixed_bar"]');

    $i->waitForElement('[data-automation-id="form_title_input"]', 20);
    $i->selectOptionInSelect2($segmentName);
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');
  }
}
