<?php declare(strict_types = 1);

namespace MailPoet\Test\Acceptance;

use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\Subscriber;

class StatsPageCest {
  public function statsPage(\AcceptanceTester $i, $scenario) {
    $i->wantTo('Open stats page of a sent newsletter');

    $newsletterTitle = 'Stats Page Test';
    $gaCampaignSlug = 'stats-page-test-ga-campaign';
    $date = (new \DateTimeImmutable('2024-01-01 06:00:00'));
    $newsletter = (new Newsletter())->withSubject($newsletterTitle)
      ->withSentStatus()
      ->withSendingQueue(['created_at' => $date])
      ->withGaCampaign($gaCampaignSlug)
      ->create();
    $this->createClickInNewsletter($newsletter);

    $subscriber1 = (new Subscriber())
      ->withEmail('stats_test1@example.com')
      ->create();
    $subscriber2 = (new Subscriber())
      ->withEmail('stats_test2@example.com')
      ->create();
    (new StatisticsOpens($newsletter, $subscriber1))->withMachineUserAgentType()->create();
    (new StatisticsOpens($newsletter, $subscriber2))->withMachineUserAgentType()->create();

    $i->login();
    $i->amOnMailpoetPage('Emails');
    $i->waitForText($newsletterTitle);
    $i->clickItemRowActionByItemName($newsletterTitle, 'Statistics');
    $i->waitForText($newsletterTitle);
    $i->waitForText('6:00 am');
    $i->waitForText($gaCampaignSlug);
    $i->waitForText('100.0%');
    $i->waitForText('200.0%');
    $i->cli(['option', 'update', 'timezone_string', 'Etc/GMT+10']);
    $i->reloadPage();
    $i->waitForText($newsletterTitle);
    $i->waitForText('8:00 pm');
    $i->waitForText($gaCampaignSlug);
    $i->waitForText('100.0%');
    $i->waitForText('200.0%');

    if (!$i->checkPluginIsActive('mailpoet-premium/mailpoet-premium.php')) {
      // the premium plugin is not active
      $i->see('This is a Premium feature');

      $href = $i->grabAttributeFrom('//a[span[text()="Upgrade"]]', 'href');
      verify($href)->stringContainsString('https://account.mailpoet.com/?s=4&email=test%40test.com&g=starter&utm_source=plugin&utm_medium=stats&utm_campaign=signup');
      $href = $i->grabAttributeFrom('//a[text()="Learn more"]', 'href');
      verify($href)->stringEndsWith('page=mailpoet-upgrade');
    }
  }

  private function createClickInNewsletter($newsletter) {
    $subscriber = (new Subscriber())->create();
    $newsletterLink = (new NewsletterLink($newsletter))->create();
    return (new StatisticsClicks($newsletterLink, $subscriber))->create();
  }
}
