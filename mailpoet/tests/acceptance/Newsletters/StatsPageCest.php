<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;

class StatsPageCest {
  public function statsPage(\AcceptanceTester $i, $scenario) {
    $i->wantTo('Open stats page of a sent newsletter');

    $newsletterTitle = 'Stats Page Test';
    (new Newsletter())->withSubject($newsletterTitle)
      ->withSentStatus()
      ->withSendingQueue()
      ->create();

    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText($newsletterTitle);
    $i->clickItemRowActionByItemName($newsletterTitle, 'Statistics');
    $i->waitForText($newsletterTitle);

    if (!$i->checkPluginIsActive('mailpoet-premium/mailpoet-premium.php')) {
      // the premium plugin is not active
      $i->see('This is a Premium feature');

      $href = $i->grabAttributeFrom('//a[span[text()="Upgrade"]]', 'href');
      expect($href)->stringContainsString('https://account.mailpoet.com/?s=1&email=test%40test.com&g=starter&utm_source=plugin&utm_medium=stats&utm_campaign=signup');
      $href = $i->grabAttributeFrom('//a[text()="Learn more"]', 'href');
      expect($href)->endsWith('page=mailpoet-upgrade');
    }
  }
}
