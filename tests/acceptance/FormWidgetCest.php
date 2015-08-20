<?php

class FormWidgetCest {

  function _before(AcceptanceTester $I) {
    $I->login();
  }

  function iCanAddAWidget(AcceptanceTester $I) {
    $I->amOnPage('/wp-admin/widgets.php');
    $I->see('MailPoet Subscription Form');

    // select the mailpoet form widget
    $I->click('#widgets-left div[id*="mailpoet_form"] .widget-title');

    // add it as a widget
    $I->waitForText(
      'Add Widget',
      1,
      '#widgets-left div[id*="mailpoet_form"]'
    );

    $I->click(
      'Add Widget',
      '#widgets-left div[id*="mailpoet_form"]'
    );

    // wait for the JS animation to end
    sleep(1);

    // save the widget
    $I->click(
      'Save',
      '#widgets-right div[id*="mailpoet_form"]:last-child'
    );
  }

  function _after(AcceptanceTester $I) {
    // delete widget
    $I->click(
      'Delete',
      '#widgets-right div[id*="mailpoet_form"]:last-child'
    );
  }
}
