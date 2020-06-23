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
    $i->click('[data-automation-id="template_index_0"]');

    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->selectOptionInSelect2($segmentName);
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');
  }

  public function createFormUsingBlankTemplate(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segmentFactory->withName($segmentName)->create();

    $i->wantTo('Create a new form');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->click('[data-automation-id="create_new_form"]');

    $i->waitForElement('[data-automation-id="template_selection_list"]');
    $i->waitForElement('[data-automation-id="blank_template"]');
    $i->click('[data-automation-id="blank_template"]');

    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->selectOptionInSelect2($segmentName);
    $i->click('[data-automation-id="form_save_button"]');
    $i->waitForText('Form saved', 10, '.automation-dismissible-notices');
  }
}
