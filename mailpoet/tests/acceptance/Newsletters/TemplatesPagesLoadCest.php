<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

class TemplatesPagesLoadCest {
  public function loadTemplatesPage(\AcceptanceTester $i) {

    $templateThumbnail = '.mailpoet-template-thumbnail';
    $templateTab = '[data-automation-id="templates-standard"]';

    $i->wantTo('Confirm template page loads and tabs can be clicked through');

    $i->login();
    $i->activateWooCommerce();

    // Get to the Template Selection page
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_standard"]');
    $i->waitForElement('[data-automation-id="email_template_selection_heading"]');
    $i->waitForElement($templateTab);
    $i->click($templateTab);

    // Standard email templates tab
    $i->see('Newsletters', ['css' => '.mailpoet-categories-item.active']);
    $i->waitForElementVisible($templateThumbnail);
    $this->checkTemplatePreview($i, $templateThumbnail);
    $i->waitForElement('[data-automation-id="select_template_8"]');
    $i->waitForElement('[data-automation-id="select_template_14"]');
    $i->waitForElement('[data-automation-id="select_template_23"]');
    $i->seeNoJSErrors();

    // Post Notification templates tab
    $i->click('Post Notifications');
    $i->see('Post Notifications', ['css' => '.mailpoet-categories-item.active']);
    $i->waitForElementVisible($templateThumbnail);
    $this->checkTemplatePreview($i, $templateThumbnail);
    $i->waitForElement('[data-automation-id="select_template_5"]');
    $i->waitForElement('[data-automation-id="select_template_8"]');
    $i->seeNoJSErrors();

    // Welcome Emails templates tab
    $i->click('Welcome Emails');
    $i->see('Welcome Emails', ['css' => '.mailpoet-categories-item.active']);
    $i->waitForElementVisible($templateThumbnail);
    $this->checkTemplatePreview($i, $templateThumbnail);
    $i->waitForElement('[data-automation-id="select_template_5"]');
    $i->waitForElement('[data-automation-id="select_template_9"]');
    $i->seeNoJSErrors();

    // WooCommerce templates tab
    $i->click('WooCommerce Emails');
    $i->see('WooCommerce Emails', ['css' => '.mailpoet-categories-item.active']);
    $i->waitForElementVisible($templateThumbnail);
    $this->checkTemplatePreview($i, $templateThumbnail);
    $i->waitForElement('[data-automation-id="select_template_5"]');
    $i->waitForElement('[data-automation-id="select_template_2"]');
    $i->seeNoJSErrors();
  }

  private function checkTemplatePreview(\AcceptanceTester $i, $templateThumbnail) {
    $i->moveMouseOver($templateThumbnail);
    $i->click($templateThumbnail);
    $i->waitForElementVisible('.mailpoet_popup_title');
    $i->waitForElementVisible('.mailpoet_popup_wrapper');
    $i->waitForElementVisible('.mailpoet_popup_body > img');
    $i->click('.mailpoet_modal_close');
  }
}
