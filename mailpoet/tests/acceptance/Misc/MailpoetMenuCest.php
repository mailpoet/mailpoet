<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;

class MailpoetMenuCest {
  public function rootPages(\AcceptanceTester $i) {
    $i->wantTo('Use MailPoet menu in WordPress admin');

    $i->login();
    $i->amOnPage('/wp-admin');

    $this->checkHomepage($i);
    $this->checkNewsletters($i);
    $this->checkAutomations($i);
    $this->checkForms($i);
    $this->checkSubscribers($i);
    $this->checkLists($i);
    $this->checkSettings($i);
    $this->checkHelp($i);
  }

  private function checkHomepage(\AcceptanceTester $i) {
    $this->clickMenuItem($i, 'MailPoet');
    $i->waitForElement('.mailpoet-subscribers-stats');
    $i->seeInCurrentUrl('?page=mailpoet-homepage');
    $this->assertSelectedMenuItem($i, 'Home');
  }

  private function checkNewsletters(\AcceptanceTester $i) {
    $this->clickMenuItem($i, 'Emails');
    $i->waitForElement('.mailpoet-newsletter-type');
    $i->seeInCurrentUrl('?page=mailpoet-newsletters');
    $this->assertSelectedMenuItem($i, 'Emails');
  }

  private function checkAutomations(\AcceptanceTester $i) {
    $this->clickMenuItem($i, 'Automations');
    $i->waitForElement('.mailpoet-section-build-your-own');
    $i->seeInCurrentUrl('?page=mailpoet-automation');
    $this->assertSelectedMenuItem($i, 'Automations');
  }

  private function checkForms(\AcceptanceTester $i) {
    $this->clickMenuItem($i, 'Forms');
    $i->waitForElement('[data-automation-id="create_new_form"]');
    $i->seeInCurrentUrl('?page=mailpoet-forms');
    $this->assertSelectedMenuItem($i, 'Forms');
  }

  private function checkSubscribers(\AcceptanceTester $i) {
    $this->clickMenuItem($i, 'Subscribers');
    $i->waitForElement('.mailpoet-subscribers-in-plan');
    $i->seeInCurrentUrl('?page=mailpoet-subscribers');
    $this->assertSelectedMenuItem($i, 'Subscribers');
  }

  private function checkLists(\AcceptanceTester $i) {
    $this->clickMenuItem($i, 'Lists');
    $i->waitForElement('[data-automation-id="new-segment"]');
    $i->seeInCurrentUrl('?page=mailpoet-segments');
    $this->assertSelectedMenuItem($i, 'Lists');
  }

  private function checkSettings(\AcceptanceTester $i) {
    $this->clickMenuItem($i, 'Settings');
    $i->waitForElement('[data-automation-id="basic_settings_tab"]');
    $i->seeInCurrentUrl('?page=mailpoet-settings');
    $this->assertSelectedMenuItem($i, 'Settings');
  }

  private function checkHelp(\AcceptanceTester $i) {
    $this->clickMenuItem($i, 'Help');
    $i->waitForElement('.mailpoet-tab-content');
    $i->seeInCurrentUrl('?page=mailpoet-help');
    $this->assertSelectedMenuItem($i, 'Help');
  }

  private function clickMenuItem(\AcceptanceTester $i, string $label): void {
    $i->click($label, '#adminmenu .menu-top.toplevel_page_mailpoet-homepage');
  }

  private function assertSelectedMenuItem(\AcceptanceTester $i, string $label): void {
    $i->seeElement(Locator::contains('#toplevel_page_mailpoet-homepage.wp-has-current-submenu > .wp-submenu > .current', $label));
  }
}
