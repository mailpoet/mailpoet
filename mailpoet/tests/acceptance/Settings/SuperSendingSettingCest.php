<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Mailer\Mailer;
use MailPoet\Test\DataFactories\Settings;

class SuperSendingSettingCest {
  private const CHECKBOX = '#mss-super-sending';
  private const LABEL = 'label[for="mss-super-sending"]';

  public function toggleSuperSendingWithMss(\AcceptanceTester $i) {
    $i->wantTo('Turn Super Sending on and off when sending with MailPoet Sending Service');
    (new Settings())->withSendingMethodMailPoet();

    $i->login();
    $i->amOnMailpoetPage('Settings#/mta');
    $i->waitForElement(self::LABEL);
    $i->see('Send to large lists in a fraction of the time, even on regular hosting.');
    $i->dontSeeCheckboxIsChecked(self::CHECKBOX);

    $i->click(self::LABEL);
    $i->waitForElementNotVisible('#mailpoet_loading');
    $i->reloadPage();
    $i->waitForElement(self::LABEL);
    $i->seeCheckboxIsChecked(self::CHECKBOX);

    $i->click(self::LABEL);
    $i->waitForElementNotVisible('#mailpoet_loading');
    $i->reloadPage();
    $i->waitForElement(self::LABEL);
    $i->dontSeeCheckboxIsChecked(self::CHECKBOX);
  }

  public function hideSuperSendingWithOtherSendingMethod(\AcceptanceTester $i) {
    $i->wantTo('Not see the Super Sending option when sending with another method');
    (new Settings())->withSendingMethod(Mailer::METHOD_PHPMAIL);

    $i->login();
    $i->amOnMailpoetPage('Settings#/mta');
    $i->waitForText('Other');
    $i->dontSeeElement(self::LABEL);
  }
}
