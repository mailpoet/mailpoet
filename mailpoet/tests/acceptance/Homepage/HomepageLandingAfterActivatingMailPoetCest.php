<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

class HomepageLandingAfterActivatingMailPoetCest {
  public function _before(\AcceptanceTester $i) {
    $i->activateWooCommerce();
  }
  
  public function homepageLanding(\AcceptanceTester $i) {
    $i->wantTo('Check that Homepage is shown after activating fresh new MailPoet plugin'); // ref: [MAILPOET-5020]

    $i->login();

    // Workaround to have fresh new MailPoet plugin before activation
    $i->amOnMailpoetPage('Settings');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->click('Reinstall now...');
    $i->acceptPopup();
    $i->waitForText('Better email — without leaving WordPress');

    $i->amOnPluginsPage();
    $i->deactivatePlugin('mailpoet');
    $i->waitForNoticeAndClose('Selected plugins deactivated.');

    $i->activatePlugin('mailpoet');
    $i->waitForText('Better email — without leaving WordPress');
    $i->seeInCurrentUrl('mailpoet-landingpage');
  }

  public function _after(\AcceptanceTester $i) {
    $i->deactivateWooCommerce();
  }
}
