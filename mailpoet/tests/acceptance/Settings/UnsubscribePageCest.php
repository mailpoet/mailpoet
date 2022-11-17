<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

class UnsubscribePageCest {
  public function previewDefaultUnsubscribePage(\AcceptanceTester $i) {
    $i->wantTo('Preview default MailPoet Unsubscribe page from MP Settings page');
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->click('[data-automation-id="unsubscribe_page_preview_link"]');
    $i->switchToNextTab();
    $i->waitForElement(['css' => '.entry-title']);
  }

  public function createNewUnsubscribeConfirmationPage(\AcceptanceTester $i) {
    $i->wantTo('Make a custom unsubscribe confirmation page');
    $pageTitle = 'Custom Unsubscribe Confirmation';
    $pageContent = 'This is custom unsubscribe confirmation page [mailpoet_page]';
    $i->cli(['post', 'create', '--post_status=publish', '--post_type=page', "--post_title='$pageTitle'", "--post_content='$pageContent'"]);
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->scrollTo('[data-automation-id="subscription-manage-page-selection"]');
    $i->click(['css' => '[data-automation-id="unsubscribe-confirmation-page-selection"]']);
    $i->selectOption('[data-automation-id="unsubscribe-confirmation-page-selection"]', $pageTitle);
    //save settings and then verify the page
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');
    $i->click('[data-automation-id="unsubscribe_page_preview_link_confirmation"]');
    $i->switchToNextTab();
    $i->waitForText('This is custom unsubscribe confirmation page');
    $i->waitForText('Simply click on this link to stop receiving emails from us.');
    $i->waitForText('Yes, unsubscribe me');
  }

  public function createNewUnsubscribeSuccessPage(\AcceptanceTester $i) {
    $i->wantTo('Make a custom unsubscribe success page');
    $pageTitle = 'Custom Unsubscribe Success';
    $pageContent = 'This is custom unsubscribe success page [mailpoet_page]';
    $i->cli(['post', 'create', '--post_status=publish', '--post_type=page', "--post_title='$pageTitle'", "--post_content='$pageContent'"]);
    $i->login();
    $i->amOnMailPoetPage('Settings');
    $i->scrollTo('[data-automation-id="subscription-manage-page-selection"]');
    $i->click(['css' => '[data-automation-id="unsubscribe-success-page-selection"]']);
    $i->selectOption('[data-automation-id="unsubscribe-success-page-selection"]', $pageTitle);
    //save settings and then verify the page
    $i->click('[data-automation-id="settings-submit-button"]');
    $i->waitForText('Settings saved');
    $i->click('[data-automation-id="unsubscribe_page_preview_link"]');
    $i->switchToNextTab();
    $i->waitForText('This is custom unsubscribe success page');
  }
}
