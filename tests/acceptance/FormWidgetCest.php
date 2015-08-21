<?php

class FormWidgetCest {

  function _before(AcceptanceTester $I) {
    $I->login();
  }

  function iCanAddTheWidget(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/widgets.php');

    $I->see('MailPoet Subscription Form', '#widgets-left');

    // select the mailpoet form widget
    $I->click('#widgets-left div[id*="mailpoet_form"] .widget-title');

    $I->waitForText(
      'Add Widget',
      1,
      '#widgets-left div[id*="mailpoet_form"]'
    );

    // add it as a widget
    $I->click(
      'Add Widget',
      '#widgets-left div[id*="mailpoet_form"]'
    );

    $I->waitForElementVisible(
      '#widgets-right div[id*="mailpoet_form"]:last-child '.
      'input[name="savewidget"]',
      1
    );

    // save
    $I->click(
      'Save',
      '#widgets-right div[id*="mailpoet_form"]:last-child'
    );
  }

  function iSeeTheWidget(AcceptanceTester $I) {
    $I->amOnPage('/');
    // make sure we are not in responsive mode
    $I->resizeWindow(960, 600);

    $I->see('Subscribe to our Newsletter');

    $I->seeElement('.widget_mailpoet_form');
    $I->seeElement('input', ['name' => 'email']);
    $I->seeElement('input', ['value' => 'Subscribe!']);
  }

  function iCanDeleteTheWidget(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/widgets.php');

    $I->see('MailPoet Subscription Form', '#widgets-right');

    // select an active mailpoet form widget
    $I->click('#widgets-right div[id*="mailpoet_form"] .widget-action');

    $I->waitForElementVisible(
      '#widgets-right div[id*="mailpoet_form"]:last-child '.
      '.widget-control-remove',
      1
    );

    // delete widget
    $I->click(
      'Delete',
      '#widgets-right div[id*="mailpoet_form"]:last-child'
    );
  }
}
