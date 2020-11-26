<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Settings;

class ReceiveStandardEmailCest {

  /** @var Settings */
  private $settings;

  public function _before() {
    $this->settings = new Settings();
  }

  public function receiveStandardEmail(\AcceptanceTester $i) {
    $this->settings->withCronTriggerMethod('WordPress');

    // try some special characters in the subject to ensure they are received correctly
    $specialChars = '… © & ěščřžýáíéůėę€żąß∂‍‍‍';

    $newsletterTitle = 'Receive Test ' . $specialChars;
    $standardTemplate = '[data-automation-id="select_template_0"]';
    $titleElement = '[data-automation-id="newsletter_title"]';
    $sendFormElement = '[data-automation-id="newsletter_send_form"]';
    $segmentName = $i->createListWithSubscriber();
    $i->wantTo('Receive a standard newsletter as a subscriber');

    $i->wantTo('Create a wp user with wp role subscriber');
    $i->cli(['user', 'create', 'narwhal', 'standardtest@example.com', '--role=subscriber']);

    $i->wantTo('Create a newsletter with template');
    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->click('[data-automation-id="create_standard"]');
    $i->waitForElement('[data-automation-id="templates-standard"]');
    $i->click('[data-automation-id="templates-standard"]');
    $i->waitForElement($standardTemplate);
    $i->see('Newsletters', ['css' => '.mailpoet-categories-item.active']);
    $i->click($standardTemplate);
    $i->waitForElement($titleElement);
    $i->fillField($titleElement, $newsletterTitle);
    $i->click('Next');

    $i->wantTo('Choose list and send');
    $i->waitForElement($sendFormElement);
    $i->selectOptionInSelect2($segmentName);
    $i->click('Send');
    $i->waitForEmailSendingOrSent();

    $i->wantTo('confirm newsletter is received');
    $i->checkEmailWasReceived($newsletterTitle);
    $i->click(Locator::contains('span.subject', $newsletterTitle));
  }
}
