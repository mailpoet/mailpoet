<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Features\FeaturesController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Segment;

class CreateFormUsingTemplateCest {
  public function _before() {
    $features = new Features();
    $features->withFeatureEnabled(FeaturesController::TEMPLATES_SELECTION);
  }

  public function createFormUsingTemplate(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segmentFactory->withName($segmentName)->create();

    $i->wantTo('Create a new form');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->click('[data-automation-id="create_new_form"]');

    $i->waitForElement('[data-automation-id="template_selection_list"]');
    $i->click('[data-automation-id="select_template_template_1_popup"]');

    $i->waitForElement('[data-automation-id="form_title_input"]', 20);
    $i->selectOptionInSelect2($segmentName);
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');
  }
}
