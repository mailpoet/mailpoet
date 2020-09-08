<?php

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

class FormEditorPlaceFormOnSpecifiedPageCest {
  public function testFormPlacement(\AcceptanceTester $i) {
    $pageTitle = 'Lorem';
    $pageContent = 'Ipsum';
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segment = $segmentFactory->withName($segmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$segment])->create();
    $page = $i->cliToArray(['post', 'create', '--format=json', '--porcelain', '--post_status=publish', '--post_type=page', "--post_title=$pageTitle", "--post_content=$pageContent"]);
    $pageData = $i->cliToArray(['post', 'get', $page[0], '--format=json']);
    $pageData = json_decode($pageData[0], true);
    $pageUrl = $pageData['guid'];

    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->click('.form-sidebar-form-placement-panel');
    $i->click('[data-automation-id="form-placement-option-Pop up"]');
    $i->checkOption('Enable');
    $i->waitForText('Display on all posts');
    $i->selectOptionInSelect2($pageTitle, '[data-automation-id="form-placement-select-page"] input.select2-search__field');

    // Save form
    $i->saveFormInEditor();

    // check the form
    $i->amOnUrl($pageUrl);
    $i->wantTo('Place form on a specific page');
    $i->waitForElement('[data-automation-id="form_email"]');
  }
}
