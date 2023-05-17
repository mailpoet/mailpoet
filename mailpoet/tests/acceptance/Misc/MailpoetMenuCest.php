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
    $this->checkWelcomeWizard($i);
    $this->checkWooCommerceSetup($i);
    $this->checkLandingPage($i);
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

    // editor
    $i->waitForElementClickable('[data-automation-id="create_standard"]');
    $i->click('[data-automation-id="create_standard"]');
    $i->waitForElementClickable('[data-automation-id="select_template_0"]');
    $i->click('[data-automation-id="select_template_0"]');
    $i->waitForElement('#mailpoet_editor');
    $i->seeInCurrentUrl('?page=mailpoet-newsletter-editor');
    $this->assertSelectedMenuItem($i, 'Emails');
  }

  private function checkAutomations(\AcceptanceTester $i) {
    $this->clickMenuItem($i, 'Automations');
    $i->waitForElement('.mailpoet-section-build-your-own');
    $i->seeInCurrentUrl('?page=mailpoet-automation');
    $this->assertSelectedMenuItem($i, 'Automations');

    // templates
    $i->waitForElementClickable(Locator::contains('button', 'Start with a template'));
    $i->click(Locator::contains('button', 'Start with a template'));
    $i->waitForElement('.mailpoet-automation-templates');
    $i->seeInCurrentUrl('?page=mailpoet-automation-templates');
    $this->assertSelectedMenuItem($i, 'Automations');

    // editor
    $i->waitForElementClickable(Locator::contains('button', 'Welcome new subscribers'));
    $i->click(Locator::contains('button', 'Welcome new subscribers'));
    $i->waitForElement('#mailpoet_automation_editor');
    $i->seeInCurrentUrl('?page=mailpoet-automation-editor');
    $this->assertSelectedMenuItem($i, 'Automations');
  }

  private function checkForms(\AcceptanceTester $i) {
    $this->clickMenuItem($i, 'Forms');
    $i->waitForElement('[data-automation-id="create_new_form"]');
    $i->seeInCurrentUrl('?page=mailpoet-forms');
    $this->assertSelectedMenuItem($i, 'Forms');

    // templates
    $i->waitForElementClickable(Locator::contains('button', 'New Form'));
    $i->click(Locator::contains('button', 'New Form'));
    $i->waitForElementClickable('[data-automation-id="select_template_template_1_popup"]');
    $i->seeInCurrentUrl('?page=mailpoet-form-editor-template-selection');
    $this->assertSelectedMenuItem($i, 'Forms');

    // editor
    $i->click('[data-automation-id="select_template_template_1_popup"]');
    $i->waitForElement('#mailpoet_form_edit');
    $i->seeInCurrentUrl('?page=mailpoet-form-editor');
    $this->assertSelectedMenuItem($i, 'Forms');
  }

  private function checkSubscribers(\AcceptanceTester $i) {
    $this->clickMenuItem($i, 'Subscribers');
    $i->waitForElement('.mailpoet-subscribers-in-plan');
    $i->seeInCurrentUrl('?page=mailpoet-subscribers');
    $this->assertSelectedMenuItem($i, 'Subscribers');

    // import
    $i->waitForElementClickable('[data-automation-id="import-subscribers-button"]');
    $i->click('[data-automation-id="import-subscribers-button"]');
    $i->waitForElement('#import_container');
    $i->seeInCurrentUrl('?page=mailpoet-import');
    $this->assertSelectedMenuItem($i, 'Subscribers');

    // export
    $this->clickMenuItem($i, 'Subscribers');
    $i->waitForElement('.mailpoet-subscribers-in-plan');
    $i->waitForElementClickable('#mailpoet_export_button');
    $i->click('#mailpoet_export_button');
    $i->waitForElement('#mailpoet-export');
    $i->seeInCurrentUrl('?page=mailpoet-export');
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

    // logs
    $i->amOnAdminPage('admin.php?page=mailpoet-logs');
    $i->waitForElement('#adminmenu');
    $this->assertSelectedMenuItem($i, 'Settings');

    // experimental features
    $i->amOnAdminPage('admin.php?page=mailpoet-experimental');
    $i->waitForElement('#adminmenu');
    $this->assertSelectedMenuItem($i, 'Settings');
  }

  private function checkHelp(\AcceptanceTester $i) {
    $this->clickMenuItem($i, 'Help');
    $i->waitForElement('.mailpoet-tab-content');
    $i->seeInCurrentUrl('?page=mailpoet-help');
    $this->assertSelectedMenuItem($i, 'Help');
  }

  private function checkWelcomeWizard(\AcceptanceTester $i) {
    $i->amOnAdminPage('admin.php?page=mailpoet-welcome-wizard');
    $i->waitForElement('#mailpoet-wizard-container');
    $this->assertSelectedMailPoetTopMenu($i);
  }

  private function checkWooCommerceSetup(\AcceptanceTester $i) {
    $i->amOnAdminPage('admin.php?page=mailpoet-woocommerce-setup');
    $i->waitForElement('#mailpoet-wizard-container');
    $this->assertSelectedMailPoetTopMenu($i);
  }

  private function checkLandingPage(\AcceptanceTester $i) {
    $i->amOnAdminPage('admin.php?page=mailpoet-landingpage');
    $i->waitForElement('#mailpoet_landingpage_container');
    $this->assertSelectedMailPoetTopMenu($i);
  }

  private function clickMenuItem(\AcceptanceTester $i, string $label): void {
    $i->click($label, '#adminmenu .menu-top.toplevel_page_mailpoet-homepage');
  }

  private function assertSelectedMenuItem(\AcceptanceTester $i, string $label): void {
    $i->seeElement(Locator::contains('#toplevel_page_mailpoet-homepage.wp-has-current-submenu > .wp-submenu > .current', $label));
  }

  private function assertSelectedMailPoetTopMenu(\AcceptanceTester $i): void {
    $i->seeElement(Locator::contains('#adminmenu .menu-top.current', 'MailPoet'));
  }
}
