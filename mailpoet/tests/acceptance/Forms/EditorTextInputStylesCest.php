<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Form;
use MailPoet\Test\DataFactories\Segment;

/**
 * @group frontend
 */
class EditorTextInputStylesCest {
  public function changeTextInputStyles(\AcceptanceTester $i) {
    $segmentFactory = new Segment();
    $segmentName = 'Fancy List';
    $segment = $segmentFactory->withName($segmentName)->create();
    $formName = 'My fancy form';
    $form = new Form();
    $form->withName($formName)->withSegments([$segment])->withDisplayBelowPosts()->create();
    $i->wantTo('Set text input styles');
    $i->login();
    $i->amOnMailPoetPage('Forms');
    $i->waitForText($formName);
    $i->clickItemRowActionByItemName($formName, 'Edit');
    $i->waitForElement('[data-automation-id="form_title_input"]');

    $i->wantTo('Add first name');
    $i->addFromBlockInEditor('First name');

    $i->wantTo('Apply some styles to first name');
    $i->click('.mailpoet-automation-label-within-input-toggle input'); // Toggle label to be rendered outside the input
    $i->click('.mailpoet-automation-input-styles-panel');
    $i->waitForElement('[data-automation-id="input_styles_settings"]');
    $i->click('.mailpoet-automation-inherit-theme-toggle input'); // Display custom settings
    $i->click('Font');
    $i->selectPanelColor('[6]'); // Select Vivid orange
    $i->click('[data-automation-id="editor_first_name_input"]');
    $i->click('(//button[@class="components-button block-editor-panel-color-gradient-settings__dropdown"])[2]'); // Click Background color
    $i->selectPanelColor('[10]'); // Select Cyan blue
    $i->click('[data-automation-id="editor_first_name_input"]');
    $i->click('Border');
    $i->selectPanelColor('[7]'); // Select Vivid amber
    $i->click('[data-automation-id="editor_first_name_input"]');
    $i->click('.mailpoet-automation-styles-bold-toggle input'); // Toggle bold on
    $i->clearFormField('.mailpoet-automation-styles-border-size input[type="number"]');
    $i->fillField('.mailpoet-automation-styles-border-size input[type="number"]', 10); // Set border width size
    $i->clearFormField('.mailpoet-automation-styles-border-radius-size input[type="number"]');
    $i->fillField('.mailpoet-automation-styles-border-radius-size input[type="number"]', 40); // Set border radius size
    
    $i->wantTo('Check that reset button resets the value');
    $i->click('Reset');
    $i->assertCssProperty('[data-automation-id="editor_first_name_input"]', 'border-width', '1px');
    $i->seeInField('.mailpoet-automation-styles-border-size input[type="number"]', '1');
    $i->clearFormField('.mailpoet-automation-styles-border-size input[type="number"]');
    $i->fillField('.mailpoet-automation-styles-border-size input[type="number"]', 10); // Set border width size

    $i->wantTo('Check element has styles');
    $i->assertCssProperty('[data-automation-id="editor_first_name_input"]', 'border-width', '10px');
    $i->assertCssProperty('[data-automation-id="editor_first_name_label"]', 'font-weight', '700');

    $i->wantTo('Apply to all');
    $i->click('[data-automation-id="styles_apply_to_all"]');

    $i->wantTo('Add heading block and write some title');
    $i->addFromBlockInEditor('Heading');
    $i->fillField('[data-title="Heading"]', 'Heading Lorem');
    $i->see('Heading Lorem');

    $i->wantTo('Add paragraph block and write some text');
    $i->addFromBlockInEditor('Paragraph');
    $i->fillField('[data-title="Paragraph"]', 'Paragraph ipsum dolor');
    $i->see('Paragraph ipsum dolor');

    $i->wantTo('Check email block has styles too and save the form');
    $i->assertCssProperty('[data-automation-id="editor_email_input"]', 'border-width', '10px');
    $i->assertCssProperty('[data-automation-id="editor_email_input"]', 'border-radius', '40px');
    $i->saveFormInEditor();

    $i->wantTo('Reload page and check data were saved');
    $i->reloadPage();
    $i->waitForElement('[data-automation-id="form_title_input"]');
    $i->assertCssProperty('[data-automation-id="editor_first_name_input"]', 'border-width', '10px');
    $i->assertCssProperty('[data-automation-id="editor_first_name_input"]', 'border-radius', '40px');
    $i->assertCssProperty('[data-automation-id="editor_first_name_label"]', 'font-weight', '700');
    $i->assertCssProperty('[data-automation-id="editor_email_input"]', 'border-width', '10px');
    $i->assertCssProperty('[data-automation-id="editor_email_input"]', 'border-radius', '40px');
    $i->assertCssProperty('[data-automation-id="editor_first_name_input"]', 'border-color', 'rgb(252, 185, 0)');
    $i->assertCssProperty('[data-automation-id="editor_first_name_input"]', 'background-color', 'rgba(142, 209, 252, 1)');
    $i->assertCssProperty('[data-automation-id="editor_email_input"]', 'border-color', 'rgb(252, 185, 0)');
    $i->assertCssProperty('[data-automation-id="editor_email_input"]', 'background-color', 'rgba(142, 209, 252, 1)');
    $i->see('Heading Lorem');
    $i->see('Paragraph ipsum dolor');

    $i->wantTo('Check styles are applied on frontend page');
    $postUrl = $i->createPost('Title', 'Content');
    $i->amOnUrl($postUrl);
    $i->fillField('[data-automation-id="form_first_name"]', 'John Doe');
    $i->assertCssProperty('[data-automation-id="form_first_name"]', 'border-width', '10px');
    $i->assertCssProperty('[data-automation-id="form_first_name"]', 'border-radius', '40px');
    $i->assertCssProperty('[data-automation-id="form_first_name_label"]', 'font-weight', '700');
    $i->assertCssProperty('[data-automation-id="form_email"]', 'border-width', '10px');
    $i->assertCssProperty('[data-automation-id="form_email"]', 'border-radius', '40px');
    $i->assertCssProperty('[data-automation-id="form_first_name"]', 'color', 'rgba(255, 105, 0, 1)');
    $i->assertCssProperty('[data-automation-id="form_first_name"]', 'border-color', 'rgb(252, 185, 0)');
    $i->assertCssProperty('[data-automation-id="form_first_name"]', 'background-color', 'rgba(142, 209, 252, 1)');
    $i->assertCssProperty('[data-automation-id="form_email"]', 'color', 'rgba(255, 105, 0, 1)');
    $i->assertCssProperty('[data-automation-id="form_email"]', 'border-color', 'rgb(252, 185, 0)');
    $i->assertCssProperty('[data-automation-id="form_email"]', 'background-color', 'rgba(142, 209, 252, 1)');
    $i->see('Heading Lorem');
    $i->see('Paragraph ipsum dolor');
  }
}
