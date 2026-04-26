<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\SubscriberStats;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Test\DataFactories\CustomField as CustomFieldFactory;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsNewsletters;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Test\DataFactories\Tag as TagFactory;
use MailPoetVendor\Carbon\Carbon;

class SubscriberStatsTest extends \MailPoetTest {
  /** @var SubscriberStats */
  private $endpoint;

  public function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(SubscriberStats::class);
  }

  public function testItReturnsProfileInformation(): void {
    $segment = (new SegmentFactory())->withName('Customers')->create();
    $tag = (new TagFactory())->withName('VIP')->create();
    $lastEngagementAt = Carbon::now()->subDay();
    $subscriber = (new SubscriberFactory())
      ->withEmail('john.geller@example.com')
      ->withFirstName('John')
      ->withLastName('Geller')
      ->withEngagementScore(60)
      ->withLastEngagementAt($lastEngagementAt)
      ->withSegments([$segment])
      ->withTags([$tag])
      ->create();
    $customField = (new CustomFieldFactory())
      ->withName('Favorite color')
      ->withSubscriber((int)$subscriber->getId(), 'Blue')
      ->create();

    $response = $this->endpoint->get(['subscriber_id' => $subscriber->getId()]);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['last_engagement_at'])->equals($lastEngagementAt->format('Y-m-d H:i:s'));
    verify($response->data['profile']['first_name'])->equals('John');
    verify($response->data['profile']['last_name'])->equals('Geller');
    verify($response->data['profile']['email'])->equals('john.geller@example.com');
    verify($response->data['profile']['segments'])->equals([
      [
        'id' => (string)$segment->getId(),
        'name' => 'Customers',
      ],
    ]);
    verify($response->data['profile']['tags'])->equals([
      [
        'id' => (string)$subscriber->getSubscriberTags()->first()->getId(),
        'subscriber_id' => (string)$subscriber->getId(),
        'tag_id' => (string)$tag->getId(),
        'name' => 'VIP',
      ],
    ]);
    verify($response->data['profile']['custom_fields'])->equals([
      [
        'id' => (string)$customField->getId(),
        'name' => 'Favorite color',
        'value' => 'Blue',
      ],
    ]);
  }

  public function testItReturnsEngagementPeriods(): void {
    $subscriber = (new SubscriberFactory())->create();
    $now = Carbon::now();

    $this->createNewsletterStats($subscriber, $now->copy()->subDays(5));
    $this->createNewsletterStats($subscriber, $now->copy()->subMonths(6));
    $this->createNewsletterStats($subscriber, $now->copy()->subYears(2));

    $response = $this->endpoint->get(['subscriber_id' => $subscriber->getId()]);
    $periods = [];
    foreach ($response->data['periodic_stats'] as $period) {
      $periods[$period['key']] = $period;
    }

    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify(array_keys($periods))->equals([
      '7_days',
      '30_days',
      '3_months',
      '12_months',
      'lifetime',
    ]);
    verify($periods['7_days']['label'])->equals('7 days');
    verify($periods['30_days']['label'])->equals('30 days');
    verify($periods['3_months']['label'])->equals('3 months');
    verify($periods['12_months']['label'])->equals('12 months');
    verify($periods['lifetime']['label'])->equals('Lifetime');
    verify($periods['7_days']['total_sent'])->equals(1);
    verify($periods['30_days']['total_sent'])->equals(1);
    verify($periods['3_months']['total_sent'])->equals(1);
    verify($periods['12_months']['total_sent'])->equals(2);
    verify($periods['lifetime']['total_sent'])->equals(3);
    verify($periods['lifetime']['open'])->equals(3);
    verify($periods['lifetime']['click'])->equals(3);
  }

  private function createNewsletterStats($subscriber, \DateTimeInterface $date): void {
    $newsletter = (new NewsletterFactory())->withSendingQueue()->create();
    $link = (new NewsletterLink($newsletter))->create();
    (new StatisticsNewsletters($newsletter, $subscriber))
      ->withSentAt($date)
      ->create();
    (new StatisticsOpens($newsletter, $subscriber))
      ->withCreatedAt($date)
      ->create();
    (new StatisticsClicks($link, $subscriber))
      ->withCreatedAt($date)
      ->create();
  }
}
