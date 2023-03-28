<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use Codeception\Stub;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Util\License\Features\Subscribers;
use MailPoet\WP\Functions as WPFunctions;

class SubscribersTest extends \MailPoetTest {
  /** @var Subscribers */
  private $subscribers;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var WPFunctions */
  private $wp;

  public function _before() {
    parent::_before();
    $this->subscribers = $this->diContainer->get(Subscribers::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->wp->deleteTransient(Subscribers::SUBSCRIBERS_COUNT_CACHE_KEY);
  }

  public function testItComputesSubscribersCount() {
    // no subscribers
    $count = $this->subscribers->getSubscribersCount();
    expect($count)->same(0);

    // create some subscribers (unconfirmed, subscribed, and inactive should be counted)
    $this->createSubscriber('unconfirmed@fake.loc', SubscriberEntity::STATUS_UNCONFIRMED);
    $this->createSubscriber('subscribed@fake.loc', SubscriberEntity::STATUS_SUBSCRIBED);
    $this->createSubscriber('inactive@fake.loc', SubscriberEntity::STATUS_INACTIVE);

    // check count
    $count = $this->subscribers->getSubscribersCount();
    expect($count)->same(3);

    // add more subscribers (bounced, unsubscribed, and trashed should not be counted)
    $this->createSubscriber('bounced@fake.loc', SubscriberEntity::STATUS_BOUNCED);
    $this->createSubscriber('unsubscribed@fake.loc', SubscriberEntity::STATUS_UNSUBSCRIBED);
    $trashed = $this->createSubscriber('trashed@fake.loc', SubscriberEntity::STATUS_SUBSCRIBED);
    $trashed->setDeletedAt(new \DateTimeImmutable());
    $this->subscribersRepository->flush();

    // check count
    $count = $this->subscribers->getSubscribersCount();
    expect($count)->same(3);
  }

  public function testItDoesntCacheSubscribersCountForLowValues() {
    // no subscribers
    $count = $this->subscribers->getSubscribersCount();
    expect($count)->same(0);

    // add subscriber
    $this->createSubscriber('one@fake.loc', SubscriberEntity::STATUS_SUBSCRIBED);
    $count = $this->subscribers->getSubscribersCount();
    expect($count)->same(1);

    // add another subscriber (count updates without cache purging)
    $this->createSubscriber('two@fake.loc', SubscriberEntity::STATUS_SUBSCRIBED);
    $count = $this->subscribers->getSubscribersCount();
    expect($count)->same(2);
  }

  public function testItCachesSubscribersCountForHighValues() {
    $subscribers = $this->getServiceWithOverrides(Subscribers::class, [
      'subscribersRepository' => Stub::make(SubscribersRepository::class, [
        'getTotalSubscribers' => 123456,
      ]),
    ]);

    $count = $subscribers->getSubscribersCount();
    expect($count)->same(123456);

    $subscribers = $this->getServiceWithOverrides(Subscribers::class, [
      'subscribersRepository' => Stub::make(SubscribersRepository::class, [
        'getTotalSubscribers' => 999999,
      ]),
    ]);

    // check count (cached value)
    $count = $subscribers->getSubscribersCount();
    expect($count)->same(123456);

    // check count (uncached value)
    $this->wp->deleteTransient(Subscribers::SUBSCRIBERS_COUNT_CACHE_KEY);
    $count = $subscribers->getSubscribersCount();
    expect($count)->same(999999);
  }

  public function testItInvalidatesSubscribersCountCache() {
    $subscribers = $this->getServiceWithOverrides(Subscribers::class, [
      'subscribersRepository' => Stub::make(SubscribersRepository::class, [
        'getTotalSubscribers' => 123456,
      ]),
    ]);
    $subscribers->getSubscribersCount();

    $subscribers = $this->getServiceWithOverrides(Subscribers::class, [
      'subscribersRepository' => Stub::make(SubscribersRepository::class, [
        'getTotalSubscribers' => 999999,
      ]),
    ]);

    // check count (cached value)
    $count = $subscribers->getSubscribersCount();
    expect($count)->same(123456);

    // modify timestamp, check count (-> uncached value)
    $this->wp->updateOption(
      '_transient_timeout_' . Subscribers::SUBSCRIBERS_COUNT_CACHE_KEY,
      $this->wp->currentTime('timestamp') - 1
    );
    $count = $subscribers->getSubscribersCount();
    expect($count)->same(999999);
  }

  public function _after() {
    parent::_after();
    $this->wp->deleteTransient(Subscribers::SUBSCRIBERS_COUNT_CACHE_KEY);
  }

  private function createSubscriber(string $email, string $status): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setStatus($status);
    $this->subscribersRepository->persist($subscriber);
    $this->subscribersRepository->flush();
    return $subscriber;
  }
}
