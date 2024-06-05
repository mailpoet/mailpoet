<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use PHPUnit\Framework\Exception;

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

    $i->click('#activate-mailpoet');
    
    // Sometimes it is "flaky" and shows the my plugins page instead homepage
    try {
      $i->waitForText('Better email — without leaving WordPress', 10);
      $i->seeInCurrentUrl('mailpoet-landingpage');
    } catch (\Exception $e) {
      $i->waitForNoticeAndClose('Plugin activated.');
      $i->deactivatePlugin('mailpoet');
      $i->waitForNoticeAndClose('Selected plugins deactivated.');
      $i->click('#activate-mailpoet');
      $i->waitForText('Better email — without leaving WordPress', 10);
      $i->seeInCurrentUrl('mailpoet-landingpage');
    }
  }

  public function _after(\AcceptanceTester $i) {
    $i->deactivateWooCommerce();
  }
}
