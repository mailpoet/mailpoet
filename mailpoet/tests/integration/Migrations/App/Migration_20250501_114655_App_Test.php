<?php declare(strict_types = 1);

namespace integration\Migrations\App;

use DateTimeImmutable;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Migrations\App\Migration_20250501_114655_App;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterLink as NewsletterLinkFactory;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsUnsubscribes;
use MailPoet\Test\DataFactories\Subscriber;

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20250501_114655_App_Test extends \MailPoetTest {
  /** @var Migration_20250501_114655_App */
  private $migration;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20250501_114655_App($this->diContainer);
  }

  public function testItPausesInvalidTasksWithUnprocessedSubscribers(): void {
    $subscriberFactory = new Subscriber();
    $subscriberUnsubscribedBefore = $subscriberFactory->withEmail('subscriber1@example.com')
      ->withCreatedAt(new DateTimeImmutable('2024-12-01 10:00:00'))
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $subscriberUnsubscribedByBot = $subscriberFactory->withEmail('subscriber2@example.com')
      ->withCreatedAt(new DateTimeImmutable('2024-12-01 10:00:00'))
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $subscriberUnsubscribedByUserManyClicks = $subscriberFactory->withEmail('subscriber3@example.com')
      ->withCreatedAt(new DateTimeImmutable('2024-12-01 10:00:00'))
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();
    $subscriberUnsubscribedByUserSingleClick = $subscriberFactory->withEmail('subscriber4@example.com')
      ->withCreatedAt(new DateTimeImmutable('2024-12-01 10:00:00'))
      ->withStatus(SubscriberEntity::STATUS_UNSUBSCRIBED)
      ->create();

    $newsletterFactory = new NewsletterFactory();
    $newsletter = $newsletterFactory->withType(NewsletterEntity::TYPE_STANDARD)
      ->withSubject('Test Newsletter')
      ->withSendingQueue()
      ->withStatus(NewsletterEntity::STATUS_SENT)
      ->create();

    $newsletterLinkFactory = new NewsletterLinkFactory($newsletter);
    $newsletterLink = $newsletterLinkFactory
      ->withUrl('https://example.com/test')
      ->create();

    $newsletterLink2 = $newsletterLinkFactory
      ->withUrl('https://example.com/test2')
      ->create();

    $newsletterLink3 = $newsletterLinkFactory
      ->withUrl('https://example.com/test3')
      ->create();

    // $subscriberUnsubscribedBefore Has many suspicious clicks but unsubscribed before the issue
    $subscriber1ClickFactory = new StatisticsClicks($newsletterLink, $subscriberUnsubscribedBefore);
    $subscriber1ClickFactory->withCreatedAt(new DateTimeImmutable('2024-12-01 10:00:00'))->create();
    $subscriber1ClickFactory = new StatisticsClicks($newsletterLink2, $subscriberUnsubscribedBefore);
    $subscriber1ClickFactory->withCreatedAt(new DateTimeImmutable('2024-12-01 10:00:00'))->create();
    $subscriber1ClickFactory = new StatisticsClicks($newsletterLink3, $subscriberUnsubscribedBefore);
    $subscriber1ClickFactory->withCreatedAt(new DateTimeImmutable('2024-12-01 10:00:00'))->create();
    $subscriber1UnsubscribeFactory = new StatisticsUnsubscribes($newsletter, $subscriberUnsubscribedBefore);
    $subscriber1UnsubscribeFactory->withCreatedAt(new DateTimeImmutable('2024-12-01 10:00:00'))->create();

    // $subscriberUnsubscribedByBot Has many suspicious clicks but and unsubscribed after the issue
    $subscriber2ClickFactory = new StatisticsClicks($newsletterLink, $subscriberUnsubscribedByBot);
    $subscriber2ClickFactory->withCreatedAt(new DateTimeImmutable('2025-04-01 10:00:00'))->create();
    $subscriber2ClickFactory = new StatisticsClicks($newsletterLink2, $subscriberUnsubscribedByBot);
    $subscriber2ClickFactory->withCreatedAt(new DateTimeImmutable('2025-04-01 10:00:02'))->create();
    $subscriber2ClickFactory = new StatisticsClicks($newsletterLink3, $subscriberUnsubscribedByBot);
    $subscriber2ClickFactory->withCreatedAt(new DateTimeImmutable('2025-04-01 10:00:05'))->create();
    $subscriber2UnsubscribeFactory = new StatisticsUnsubscribes($newsletter, $subscriberUnsubscribedByBot);
    $subscriber2UnsubscribeFactory->withCreatedAt(new DateTimeImmutable('2025-04-01 10:00:03'))->create();

    // $subscriberUnsubscribedByUserManyClicks Has many clicks but they are spread in time so it's not suspicious
    $subscriber3ClickFactory = new StatisticsClicks($newsletterLink, $subscriberUnsubscribedByUserManyClicks);
    $subscriber3ClickFactory->withCreatedAt(new DateTimeImmutable('2025-04-01 10:00:00'))->create();
    $subscriber3ClickFactory = new StatisticsClicks($newsletterLink2, $subscriberUnsubscribedByUserManyClicks);
    $subscriber3ClickFactory->withCreatedAt(new DateTimeImmutable('2025-04-01 10:00:30'))->create();
    $subscriber3ClickFactory = new StatisticsClicks($newsletterLink3, $subscriberUnsubscribedByUserManyClicks);
    $subscriber3ClickFactory->withCreatedAt(new DateTimeImmutable('2025-04-01 10:00:50'))->create();
    $subscriber3UnsubscribeFactory = new StatisticsUnsubscribes($newsletter, $subscriberUnsubscribedByUserManyClicks);
    $subscriber3UnsubscribeFactory->withCreatedAt(new DateTimeImmutable('2025-04-01 10:00:55'))->create();

    // $subscriberUnsubscribedByUserSingleClick Has one click and unsubscribed after the issue
    $subscriber4ClickFactory = new StatisticsClicks($newsletterLink, $subscriberUnsubscribedByUserSingleClick);
    $subscriber4ClickFactory->withCreatedAt(new DateTimeImmutable('2025-04-01 10:00:00'))->create();
    $subscriber4UnsubscribeFactory = new StatisticsUnsubscribes($newsletter, $subscriberUnsubscribedByUserSingleClick);
    $subscriber4UnsubscribeFactory->withCreatedAt(new DateTimeImmutable('2025-04-01 10:00:00'))->create();

    $this->migration->run();

    $this->entityManager->refresh($subscriberUnsubscribedBefore);
    $this->entityManager->refresh($subscriberUnsubscribedByBot);
    $this->entityManager->refresh($subscriberUnsubscribedByUserManyClicks);
    $this->entityManager->refresh($subscriberUnsubscribedByUserSingleClick);

    $this->assertEquals(SubscriberEntity::STATUS_UNSUBSCRIBED, $subscriberUnsubscribedBefore->getStatus());
    $this->assertEquals(SubscriberEntity::STATUS_SUBSCRIBED, $subscriberUnsubscribedByBot->getStatus());
    $this->assertEquals(SubscriberEntity::STATUS_UNSUBSCRIBED, $subscriberUnsubscribedByUserManyClicks->getStatus());
    $this->assertEquals(SubscriberEntity::STATUS_UNSUBSCRIBED, $subscriberUnsubscribedByUserSingleClick->getStatus());
  }
}
