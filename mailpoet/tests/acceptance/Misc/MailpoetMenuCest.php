<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

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
    $i->click('MailPoet');
    $i->waitForElement('.mailpoet-subscribers-stats');
    $i->seeInCurrentUrl('?page=mailpoet-homepage');
  }

  private function checkNewsletters(\AcceptanceTester $i) {
    $i->click('Emails');
    $i->waitForElement('.mailpoet-newsletter-type');
    $i->seeInCurrentUrl('?page=mailpoet-newsletters');
  }

  private function checkAutomations(\AcceptanceTester $i) {
    $i->click('Automations');
    $i->waitForElement('.mailpoet-section-build-your-own');
    $i->seeInCurrentUrl('?page=mailpoet-automation');
  }

  private function checkForms(\AcceptanceTester $i) {
    $i->click('Forms');
    $i->waitForElement('[data-automation-id="create_new_form"]');
    $i->seeInCurrentUrl('?page=mailpoet-forms');
  }

  private function checkSubscribers(\AcceptanceTester $i) {
    $i->click('Subscribers');
    $i->waitForElement('.mailpoet-subscribers-in-plan');
    $i->seeInCurrentUrl('?page=mailpoet-subscribers');
  }

  private function checkLists(\AcceptanceTester $i) {
    $i->click('Lists');
    $i->waitForElement('[data-automation-id="new-segment"]');
    $i->seeInCurrentUrl('?page=mailpoet-segments');
  }

  private function checkSettings(\AcceptanceTester $i) {
    $i->click('Settings');
    $i->waitForElement('[data-automation-id="basic_settings_tab"]');
    $i->seeInCurrentUrl('?page=mailpoet-settings');
  }

  private function checkHelp(\AcceptanceTester $i) {
    $i->click('Help');
    $i->waitForElement('.mailpoet-tab-content');
    $i->seeInCurrentUrl('?page=mailpoet-help');
  }
}
