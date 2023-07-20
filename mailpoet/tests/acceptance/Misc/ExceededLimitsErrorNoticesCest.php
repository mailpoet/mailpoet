<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Test\DataFactories\Settings;

class ExceededLimitsErrorNoticesCest {
  public function emailVolumeLimitsNotices(\AcceptanceTester $i) {
    $i->wantTo('Check the plugin displays correct messages for exceeded email volume limits');
    $mailerErrorMessage = 'Email volume limit message from mailer log!';

    $i->wantTo('Check when the error is logged only in mailer log it is displayed');
    $settings = new Settings();
    $settings->withSendingError($mailerErrorMessage, 'email_limit_reached');

    $i->login();
    $i->amOnMailpoetPage('Homepage');
    $i->waitForText($mailerErrorMessage);

    $i->wantTo('Check when the access restriction is logged from key check it displays proper message and hides the mailer log message');
    $settings->withSendingMethodMailpoetWithRestrictedAccess(Bridge::KEY_ACCESS_EMAIL_VOLUME_LIMIT, 5000);

    $i->amOnMailpoetPage('Homepage');
    $i->waitForText('Congratulations, you sent more than 5,000 emails this month!');
    $i->waitForText('your MailPoet plan includes (5,000)');
    $i->dontSee($mailerErrorMessage);

    $i->wantTo('Check the alternative message without limit info is displayed when the limit info is not available');
    $settings->withSendingMethodMailpoetWithRestrictedAccess(Bridge::KEY_ACCESS_EMAIL_VOLUME_LIMIT, 0);
    $i->amOnMailpoetPage('Homepage');
    $i->waitForText('Congratulations, you sent a lot of emails this month!');
    $i->dontSee($mailerErrorMessage);
  }

  public function subscribersLimitsNotices(\AcceptanceTester $i) {
    $i->wantTo('Check the plugin displays correct messages for exceeded subscribers limits');
    $mailerErrorMessage = 'Subscriber limit message from mailer log!';

    $i->wantTo('Check when the error is logged only in mailer log it is displayed');
    $settings = new Settings();
    $settings->withSendingError($mailerErrorMessage, 'subscriber_limit_reached');

    $i->login();
    $i->amOnMailpoetPage('Homepage');
    $i->waitForText($mailerErrorMessage);

    $i->wantTo('Check when the access restriction is logged from key check it displays proper message and hides the mailer log message');
    $settings->withSendingMethodMailpoetWithRestrictedAccess(Bridge::KEY_ACCESS_SUBSCRIBERS_LIMIT, 5000);

    $i->amOnMailpoetPage('Homepage');
    $i->waitForText('Action required: Upgrade your plan for more than 5,000 subscribers!');
    $i->waitForText('Your plan is limited to 5,000 subscribers');
    $i->dontSee($mailerErrorMessage);

    $i->wantTo('Check the alternative message without limit info is displayed when the limit info is not available');
    $settings->withSendingMethodMailpoetWithRestrictedAccess(Bridge::KEY_ACCESS_SUBSCRIBERS_LIMIT, 0);
    $i->amOnMailpoetPage('Homepage');
    $i->waitForText('Action required: Upgrade your plan!');
    $i->waitForText('Congratulations, you now have more subscribers than your plan’s limit!');
    $i->dontSee($mailerErrorMessage);
  }

  public function allSendingPausedInvalidKeyNotice(\AcceptanceTester $i) {
    $i->wantTo('Check the plugin displays correct messages for invalid API key');
    $settings = new Settings();
    $settings->withSendingMethod(Mailer::METHOD_MAILPOET);
    $settings->withInvalidMssKey();

    $i->login();
    $i->amOnMailpoetPage('Homepage');
    $i->waitForText('All sending is currently paused!');

    $i->wantTo('Check the doesnt display all sending paused notice when user sets another sending method');
    $settings->withSendingMethod('smtl');
    $i->amOnMailpoetPage('Homepage');
    $i->dontSee('All sending is currently paused!');
  }
}
