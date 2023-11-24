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
    $this->checkSegments($i);
    $this->checkSettings($i);
    $this->checkHelp($i);
    $this->checkWelcomeWizard($i);
    $this->checkWooCommerceSetup($i);
    $this->checkLandingPage($i);
  }

  private function checkHomepage(\AcceptanceTester $i) {
    $i->wantTo('Check Home page and its menu as selected');
    $this->clickMenuItem($i, 'MailPoet');
    $i->waitForElement('.mailpoet-subscribers-stats');
    $i->seeInCurrentUrl('?page=mailpoet-homepage');
    $this->assertSelectedMenuItem($i, 'Home');
  }

  private function checkNewsletters(\AcceptanceTester $i) {
    $i->wantTo('Check Emails page and its menu as selected');
    $this->clickMenuItem($i, 'Emails');
    $i->waitForElement('.mailpoet-newsletter-type');
    $i->seeInCurrentUrl('?page=mailpoet-newsletters');
    $this->assertSelectedMenuItem($i, 'Emails');

    $i->wantTo('Check if the menu is still selected if I go to the editor');
    $i->waitForElementClickable('[data-automation-id="create_standard"]');
    $i->click('[data-automation-id="create_standard"]');
    $i->waitForElementClickable('[data-automation-id="select_template_0"]');
    $i->click('[data-automation-id="select_template_0"]');
    $i->waitForElement('#mailpoet_editor');
    $i->seeInCurrentUrl('?page=mailpoet-newsletter-editor');
    $this->assertSelectedMenuItem($i, 'Emails');
  }

  private function checkAutomations(\AcceptanceTester $i) {
    $i->wantTo('Check Automations page and its menu as selected');
    $this->clickMenuItem($i, 'Automations');
    $i->waitForElement('.mailpoet-section-build-your-own');
    $i->seeInCurrentUrl('?page=mailpoet-automation');
    $this->assertSelectedMenuItem($i, 'Automations');

    $i->wantTo('Check if the menu is still selected if I go to the templates');
    $i->waitForElementClickable(Locator::contains('button', 'Start with a template'));
    $i->click(Locator::contains('button', 'Start with a template'));
    $i->waitForElement('#mailpoet_automation_templates');
    $i->seeInCurrentUrl('?page=mailpoet-automation-templates');
    $this->assertSelectedMenuItem($i, 'Automations');

    $i->wantTo('Check if the menu is still selected if I go to the workflow editor');
    $i->waitForElementClickable(Locator::contains('button', 'Welcome new subscribers'));
    $i->click(Locator::contains('button', 'Welcome new subscribers'));
    $i->click('Start building');
    $i->waitForElement('#mailpoet_automation_editor');
    $i->seeInCurrentUrl('?page=mailpoet-automation-editor');
    $this->assertSelectedMenuItem($i, 'Automations');

    $i->wantTo('Check if the menu is still selected if I go to the choose template page');
    $i->waitForText('Send email');
    $i->click('Send email');
    $i->waitForText('Design email');
    $i->click('Design email');
    $i->waitForElementClickable('[data-automation-id="select_template_0"]');
    $this->assertSelectedMenuItem($i, 'Automations');

    $i->wantTo('Check if the menu is still selected if I go to the emails editor');
    $i->click('[data-automation-id="select_template_0"]');
    $i->waitForElement('#mailpoet_editor');
    $this->assertSelectedMenuItem($i, 'Automations');
  }

  private function checkForms(\AcceptanceTester $i) {
    $i->wantTo('Check Forms page and its menu as selected');
    $this->clickMenuItem($i, 'Forms');
    $i->waitForElement('[data-automation-id="create_new_form"]');
    $i->seeInCurrentUrl('?page=mailpoet-forms');
    $this->assertSelectedMenuItem($i, 'Forms');

    $i->wantTo('Check if the menu is still selected if I go to the choose template page');
    $i->waitForElementClickable(Locator::contains('button', 'New Form'));
    $i->click(Locator::contains('button', 'New Form'));
    $i->waitForElementClickable('[data-automation-id="select_template_template_1_popup"]');
    $i->seeInCurrentUrl('?page=mailpoet-form-editor-template-selection');
    $this->assertSelectedMenuItem($i, 'Forms');

    $i->wantTo('Check if the menu is still selected if I go to the forms editor');
    $i->click('[data-automation-id="select_template_template_1_popup"]');
    $i->waitForElement('#mailpoet_form_edit');
    $i->seeInCurrentUrl('?page=mailpoet-form-editor');
    $this->assertSelectedMenuItem($i, 'Forms');
  }

  private function checkSubscribers(\AcceptanceTester $i) {
    $i->wantTo('Check Subscribers page and its menu as selected');
    $this->clickMenuItem($i, 'Subscribers');
    $i->waitForElement('.mailpoet-subscribers-in-plan');
    $i->seeInCurrentUrl('?page=mailpoet-subscribers');
    $this->assertSelectedMenuItem($i, 'Subscribers');

    $i->wantTo('Check if the menu is still selected if I go to the import page');
    $i->waitForElementClickable('[data-automation-id="import-subscribers-button"]');
    $i->click('[data-automation-id="import-subscribers-button"]');
    $i->waitForElement('#import_container');
    $i->seeInCurrentUrl('?page=mailpoet-import');
    $this->assertSelectedMenuItem($i, 'Subscribers');

    $i->wantTo('Check if the menu is still selected if I go to the export page');
    $this->clickMenuItem($i, 'Subscribers');
    $i->waitForElement('.mailpoet-subscribers-in-plan');
    $i->waitForElementClickable('#mailpoet_export_button');
    $i->click('#mailpoet_export_button');
    $i->waitForElement('#mailpoet-export');
    $i->seeInCurrentUrl('?page=mailpoet-export');
    $this->assertSelectedMenuItem($i, 'Subscribers');

    $i->wantTo('Check if the menu is still selected if I go to the add new subscriber page');
    $this->clickMenuItem($i, 'Subscribers');
    $i->waitForElement('.mailpoet-subscribers-in-plan');
    $i->waitForElementClickable('[data-automation-id="add-new-subscribers-button"]');
    $i->click('[data-automation-id="add-new-subscribers-button"]');
    $i->waitForText('Subscriber');
    $i->waitForElement('[data-automation-id="subscriber_edit_form"]');
    $i->seeInCurrentUrl('?page=mailpoet-subscribers#/new');
    $this->assertSelectedMenuItem($i, 'Subscribers');
  }

  private function checkLists(\AcceptanceTester $i) {
    $i->wantTo('Check Lists page and its menu as selected');
    $this->clickMenuItem($i, 'Lists');
    $i->waitForElement('[data-automation-id="new-list"]');
    $i->seeInCurrentUrl('?page=mailpoet-lists');
    $this->assertSelectedMenuItem($i, 'Lists');

    $i->wantTo('Check if the menu is still selected if I go to the add new list page');
    $i->waitForElementClickable('[data-automation-id="new-list"]');
    $i->click('[data-automation-id="new-list"]');
    $i->waitForText('List visibility');
    $i->seeInCurrentUrl('?page=mailpoet-lists#/new');
    $this->assertSelectedMenuItem($i, 'Lists');
  }

  private function checkSegments(\AcceptanceTester $i) {
    $i->wantTo('Check Segments page and its menu as selected');
    $this->clickMenuItem($i, 'Segments');
    $i->waitForElement('[data-automation-id="new-segment"]');
    $i->seeInCurrentUrl('?page=mailpoet-segments');
    $this->assertSelectedMenuItem($i, 'Segments');

    $i->wantTo('Check if the menu is still selected if I go to the add new segment page');
    $i->waitForElementClickable('[data-automation-id="new-segment"]');
    $i->click('[data-automation-id="new-segment"]');
    $i->waitForElement('[data-automation-id="new-custom-segment"]');
    $i->seeInCurrentUrl('?page=mailpoet-segments#/segment-templates');
    $this->assertSelectedMenuItem($i, 'Segments');
    $i->click('[data-automation-id="new-custom-segment"]');
    $i->waitForText('Segment');
    $i->waitForElement('[data-automation-id="input-name"]');
    $i->seeInCurrentUrl('?page=mailpoet-segments#/new-segment');
    $this->assertSelectedMenuItem($i, 'Segments');
  }

  private function checkSettings(\AcceptanceTester $i) {
    $i->wantTo('Check Settings page and its menu as selected');
    $this->clickMenuItem($i, 'Settings');
    $i->waitForElement('[data-automation-id="basic_settings_tab"]');
    $i->seeInCurrentUrl('?page=mailpoet-settings');
    $this->assertSelectedMenuItem($i, 'Settings');

    $i->wantTo('Check if the menu is still selected if I go to the Advanced tab');
    $i->waitForElementClickable('[data-automation-id="settings-advanced-tab"]');
    $i->click('[data-automation-id="settings-advanced-tab"]');
    $i->waitForElement('[data-automation-id="bounce-address-field"]');
    $i->seeInCurrentUrl('?page=mailpoet-settings#/advanced');
    $this->assertSelectedMenuItem($i, 'Settings');

    $i->wantTo('Check if the menu is still selected if I go to the logs page');
    $i->amOnAdminPage('admin.php?page=mailpoet-logs');
    $i->waitForElement('#adminmenu');
    $this->assertSelectedMenuItem($i, 'Settings');

    $i->wantTo('Check if the menu is still selected if I go to the experimental features');
    $i->amOnAdminPage('admin.php?page=mailpoet-experimental');
    $i->waitForElement('#adminmenu');
    $this->assertSelectedMenuItem($i, 'Settings');
  }

  private function checkHelp(\AcceptanceTester $i) {
    $i->wantTo('Check Help page and its menu as selected');
    $this->clickMenuItem($i, 'Help');
    $i->waitForElement('.mailpoet-tab-content');
    $i->seeInCurrentUrl('?page=mailpoet-help');
    $this->assertSelectedMenuItem($i, 'Help');
  }

  private function checkWelcomeWizard(\AcceptanceTester $i) {
    $i->wantTo('Check Wizard page and not any menu as selected');
    $i->amOnAdminPage('admin.php?page=mailpoet-welcome-wizard');
    $i->waitForElement('#mailpoet-wizard-container');
    $this->assertSelectedMailPoetTopMenu($i);
  }

  private function checkWooCommerceSetup(\AcceptanceTester $i) {
    $i->wantTo('Check WooCommerce setup page and not any menu as selected');
    $i->amOnAdminPage('admin.php?page=mailpoet-woocommerce-setup');
    $i->waitForElement('#mailpoet-wizard-container');
    $this->assertSelectedMailPoetTopMenu($i);
  }

  private function checkLandingPage(\AcceptanceTester $i) {
    $i->wantTo('Check Landing page and not any menu as selected');
    $i->amOnAdminPage('admin.php?page=mailpoet-landingpage');
    $i->waitForElement('#mailpoet_landingpage_container');
    $this->assertSelectedMailPoetTopMenu($i);
  }

  private function clickMenuItem(\AcceptanceTester $i, string $label): void {
    $i->click($label, '#adminmenu .menu-top.toplevel_page_mailpoet-homepage');
  }

  private function assertSelectedMenuItem(\AcceptanceTester $i, string $label): void {
    $i->seeElement(Locator::contains('#adminmenu .menu-top.wp-has-current-submenu', 'MailPoet'));
    $i->seeElement(Locator::contains('#adminmenu #toplevel_page_mailpoet-homepage.wp-has-current-submenu > .wp-submenu > .current', $label));
  }

  private function assertSelectedMailPoetTopMenu(\AcceptanceTester $i): void {
    $i->seeElement(Locator::contains('#adminmenu .menu-top.current', 'MailPoet'));
  }
}
