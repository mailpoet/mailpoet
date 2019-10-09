<?php

namespace MailPoet\Test\Acceptance;

use Codeception\Util\Locator;
use MailPoet\Test\DataFactories\Settings;

class ReceiveStandardEmailCest {

  /** @var Settings */
  private $settings;

  protected function _inject(Settings $settings) {
    $this->settings = $settings;
  }

  function receiveStandardEmail(\AcceptanceTester $I) {
    $this->settings->withCronTriggerMethod('WordPress');

    // try some special characters in the subject to ensure they are received correctly
    $special_chars = '… © & ěščřžýáíéůėę€żąß∂‍‍‍';

    $newsletter_title = 'Receive Test ' . $special_chars;
    $standard_template = '[data-automation-id=\'select_template_0\']';
    $title_element = '[data-automation-id=\'newsletter_title\']';
    $send_form_element = '[data-automation-id="newsletter_send_form"]';
    $segment_name = $I->createListWithSubscriber();
    $I->wantTo('Receive a standard newsletter as a subscriber');

    //create a wp user with wp role subscriber
    $I->cli(['user', 'create', 'narwhal', 'standardtest@example.com', '--role=subscriber']);
    //Create a newsletter with template
    $I->login();
    $I->amOnMailpoetPage('Emails');
    $I->click('[data-automation-id=\'new_email\']');
    $I->click('[data-automation-id=\'create_standard\']');
    $I->waitForElement($standard_template);
    $I->see('Newsletters', ['css' => 'a.current']);
    $I->click($standard_template);
    $I->waitForElement($title_element);
    $I->fillField($title_element, $newsletter_title);
    $I->click('Next');
    //Choose list and send
    $I->waitForElement($send_form_element);
    $I->selectOptionInSelect2($segment_name);
    $I->click('Send');
    $I->waitForElement('.mailpoet_progress_label', 90);
    //confirm newsletter is received
    $I->amOnMailboxAppPage();
    $I->waitForText($newsletter_title, 90);
    $I->click(Locator::contains('span.subject', $newsletter_title));
  }
}
