<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use DateTimeInterface;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\NewsletterLink;
use MailPoet\Test\DataFactories\StatisticsClicks;
use MailPoet\Test\DataFactories\StatisticsNewsletters;
use MailPoet\Test\DataFactories\StatisticsOpens;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Carbon\Carbon;

/**
 * @group woo
 */
class EngagementDataBackfillerTest extends \MailPoetTest {

  /** @var EngagementDataBackfiller */
  private $backfiller;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before() {
    parent::_before();
    $this->backfiller = $this->diContainer->get(EngagementDataBackfiller::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
  }

  public function testItRetrievesPurchaseData(): void {
    $customerId = $this->tester->createCustomer('1@e.com');
    $sub1 = $this->subscribersRepository->findOneBy(['email' => '1@e.com']);
    $older = Carbon::now()->subDays(200);
    $newer = Carbon::now()->subDays(100);
    $this->createOrder($customerId, $older);
    $this->createOrder($customerId, $newer);

    $customerId2 = $this->tester->createCustomer('2@e.com');
    $sub2 = $this->subscribersRepository->findOneBy(['email' => '2@e.com']);
    $older2 = Carbon::now()->subDays(150);
    $newer2 = Carbon::now()->subDays(50);
    $this->createOrder($customerId2, $newer2);
    $this->createOrder($customerId2, $older2);

    $customerId3 = $this->tester->createCustomer('3@e.com');

    $batch = $this->backfiller->getBatch();
    $subscriberIds = array_map(function($subscriber) {
      return $subscriber->getId();
    }, $batch);
    expect($batch)->count(3);
    $data = $this->backfiller->getPurchaseDataForBatch($subscriberIds);
    $this->assertInstanceOf(SubscriberEntity::class, $sub1);
    $this->assertInstanceOf(SubscriberEntity::class, $sub2);
    expect($data[$sub1->getId()]['last_purchase_at'])->equals( (string)$newer);
    expect($data[$sub2->getId()]['last_purchase_at'])->equals((string)$newer2);
    expect(!isset($data[$customerId3]))->true();
  }

  public function testItUpdatesSubscribersPurchaseData(): void {
    $customerId = $this->tester->createCustomer('1@e.com');
    $sub1 = $this->subscribersRepository->findOneBy(['email' => '1@e.com']);
    $older = Carbon::now()->subDays(200);
    $newer = Carbon::now()->subDays(100);
    $this->createOrder($customerId, $older);
    $this->createOrder($customerId, $newer);

    $customerId2 = $this->tester->createCustomer('2@e.com');
    $sub2 = $this->subscribersRepository->findOneBy(['email' => '2@e.com']);
    $older2 = Carbon::now()->subDays(150);
    $newer2 = Carbon::now()->subDays(50);
    $this->createOrder($customerId2, $newer2);
    $this->createOrder($customerId2, $older2);

    $this->assertInstanceOf(SubscriberEntity::class, $sub1);
    $this->assertInstanceOf(SubscriberEntity::class, $sub2);
    expect($sub2->getLastPurchaseAt())->null();
    expect($sub1->getLastPurchaseAt())->null();

    $this->backfiller->updateBatch([$sub1, $sub2]);

    $this->entityManager->refresh($sub1);
    $this->entityManager->refresh($sub2);

    $this->assertInstanceOf(DateTimeInterface::class, $sub1->getLastPurchaseAt());
    $this->assertInstanceOf(DateTimeInterface::class, $sub2->getLastPurchaseAt());
    expect($sub1->getLastPurchaseAt()->getTimestamp())->equals($newer->getTimestamp());
    expect($sub2->getLastPurchaseAt()->getTimestamp())->equals($newer2->getTimestamp());
  }

  public function testItRetrievesOpensData(): void {
    $sub1 = (new Subscriber())->withEmail('1@e.com')->create();
    $sub2 = (new Subscriber())->withEmail('2@e.com')->create();
    $sub3 = (new Subscriber())->withEmail('3@e.com')->create();
    $sub4 = (new Subscriber())->withEmail('4@e.com')->create();
    $this->assertInstanceOf(SubscriberEntity::class, $sub1);
    $this->assertInstanceOf(SubscriberEntity::class, $sub2);
    $this->assertInstanceOf(SubscriberEntity::class, $sub3);
    $this->assertInstanceOf(SubscriberEntity::class, $sub4);
    $newsletter = (new Newsletter())->withSendingQueue()->create();

    $sub1open = (new StatisticsOpens($newsletter, $sub1))->create();
    $older = Carbon::now()->subDays(200);
    $sub1open->setCreatedAt($older);

    $sub1open2 = (new StatisticsOpens($newsletter, $sub1))->create();
    $newer = Carbon::now()->subDays(20);
    $sub1open2->setCreatedAt($newer);

    $sub2open = (new StatisticsOpens($newsletter, $sub2))->create();
    $sub2date = Carbon::now()->subDays(22);
    $sub2open->setCreatedAt($sub2date);

    $sub3open = (new StatisticsOpens($newsletter, $sub3))->create();
    $sub3date = Carbon::now()->subDays(33);
    $sub3open->setCreatedAt($sub3date);

    $this->entityManager->flush();
    $openData = $this->backfiller->getOpenDataForBatch([$sub1->getId(), $sub2->getId(), $sub3->getId(), $sub4->getId()]);
    expect($openData)->count(3);
    expect($openData[$sub1->getId()]['last_open_at'])->equals((string)$newer);
    expect($openData[$sub2->getId()]['last_open_at'])->equals((string)$sub2date);
    expect($openData[$sub3->getId()]['last_open_at'])->equals((string)$sub3date);
    expect(!isset($openData[$sub4->getId()]))->true();
  }

  public function testItUpdatesOpensData(): void {
    $sub1 = (new Subscriber())->withEmail('1@e.com')->create();
    $sub2 = (new Subscriber())->withEmail('2@e.com')->create();
    $sub3 = (new Subscriber())->withEmail('3@e.com')->create();
    $sub4 = (new Subscriber())->withEmail('4@e.com')->create();
    $this->assertInstanceOf(SubscriberEntity::class, $sub1);
    $this->assertInstanceOf(SubscriberEntity::class, $sub2);
    $this->assertInstanceOf(SubscriberEntity::class, $sub3);
    $this->assertInstanceOf(SubscriberEntity::class, $sub4);
    $newsletter = (new Newsletter())->withSendingQueue()->create();

    $sub1open = (new StatisticsOpens($newsletter, $sub1))->create();
    $older = Carbon::now()->subDays(200);
    $sub1open->setCreatedAt($older);

    $sub1open2 = (new StatisticsOpens($newsletter, $sub1))->create();
    $newer = Carbon::now()->subDays(20);
    $sub1open2->setCreatedAt($newer);

    $sub2open = (new StatisticsOpens($newsletter, $sub2))->create();
    $sub2date = Carbon::now()->subDays(22);
    $sub2open->setCreatedAt($sub2date);

    $sub3open = (new StatisticsOpens($newsletter, $sub3))->create();
    $sub3date = Carbon::now()->subDays(33);
    $sub3open->setCreatedAt($sub3date);

    $this->entityManager->flush();

    expect($sub1->getLastOpenAt())->null();
    expect($sub2->getLastOpenAt())->null();
    expect($sub3->getLastOpenAt())->null();
    expect($sub4->getLastOpenAt())->null();

    $this->backfiller->updateBatch([$sub1, $sub2, $sub3, $sub4]);

    $this->entityManager->refresh($sub1);
    $this->entityManager->refresh($sub2);
    $this->entityManager->refresh($sub3);
    $this->entityManager->refresh($sub4);

    $this->assertInstanceOf(DateTimeInterface::class, $sub1->getLastOpenAt());
    $this->assertInstanceOf(DateTimeInterface::class, $sub2->getLastOpenAt());
    $this->assertInstanceOf(DateTimeInterface::class, $sub3->getLastOpenAt());

    expect($sub1->getLastOpenAt()->getTimestamp())->equals($newer->getTimestamp());
    expect($sub2->getLastOpenAt()->getTimestamp())->equals($sub2date->getTimestamp());
    expect($sub3->getLastOpenAt()->getTimestamp())->equals($sub3date->getTimestamp());
    expect($sub4->getLastOpenAt())->null();
  }

  public function testItRetrievesClicksData(): void {
    $sub1 = (new Subscriber())->withEmail('1@e.com')->create();
    $sub2 = (new Subscriber())->withEmail('2@e.com')->create();
    $sub3 = (new Subscriber())->withEmail('3@e.com')->create();
    $sub4 = (new Subscriber())->withEmail('4@e.com')->create();
    $this->assertInstanceOf(SubscriberEntity::class, $sub1);
    $this->assertInstanceOf(SubscriberEntity::class, $sub2);
    $this->assertInstanceOf(SubscriberEntity::class, $sub3);
    $this->assertInstanceOf(SubscriberEntity::class, $sub4);
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $link = (new NewsletterLink($newsletter))->create();

    $sub1open = (new StatisticsClicks($link, $sub1))->create();
    $older = Carbon::now()->subDays(200);
    $sub1open->setCreatedAt($older);

    $sub1open2 = (new StatisticsClicks($link, $sub1))->create();
    $newer = Carbon::now()->subDays(20);
    $sub1open2->setCreatedAt($newer);

    $sub2open = (new StatisticsClicks($link, $sub2))->create();
    $sub2date = Carbon::now()->subDays(22);
    $sub2open->setCreatedAt($sub2date);

    $sub3open = (new StatisticsClicks($link, $sub3))->create();
    $sub3date = Carbon::now()->subDays(33);
    $sub3open->setCreatedAt($sub3date);

    $this->entityManager->flush();
    $clickData = $this->backfiller->getClickDataForBatch([$sub1->getId(), $sub2->getId(), $sub3->getId(), $sub4->getId()]);
    expect($clickData)->count(3);
    expect($clickData[$sub1->getId()]['last_click_at'])->equals((string)$newer);
    expect($clickData[$sub2->getId()]['last_click_at'])->equals((string)$sub2date);
    expect($clickData[$sub3->getId()]['last_click_at'])->equals((string)$sub3date);
    expect(!isset($clickData[$sub4->getId()]))->true();
  }

  public function testItUpdatesClicksData(): void {
    $sub1 = (new Subscriber())->withEmail('1@e.com')->create();
    $sub2 = (new Subscriber())->withEmail('2@e.com')->create();
    $sub3 = (new Subscriber())->withEmail('3@e.com')->create();
    $sub4 = (new Subscriber())->withEmail('4@e.com')->create();
    $this->assertInstanceOf(SubscriberEntity::class, $sub1);
    $this->assertInstanceOf(SubscriberEntity::class, $sub2);
    $this->assertInstanceOf(SubscriberEntity::class, $sub3);
    $this->assertInstanceOf(SubscriberEntity::class, $sub4);
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $link = (new NewsletterLink($newsletter))->create();

    $sub1open = (new StatisticsClicks($link, $sub1))->create();
    $older = Carbon::now()->subDays(200);
    $sub1open->setCreatedAt($older);

    $sub1open2 = (new StatisticsClicks($link, $sub1))->create();
    $newer = Carbon::now()->subDays(20);
    $sub1open2->setCreatedAt($newer);

    $sub2open = (new StatisticsClicks($link, $sub2))->create();
    $sub2date = Carbon::now()->subDays(22);
    $sub2open->setCreatedAt($sub2date);

    $sub3open = (new StatisticsClicks($link, $sub3))->create();
    $sub3date = Carbon::now()->subDays(33);
    $sub3open->setCreatedAt($sub3date);

    $this->entityManager->flush();

    expect($sub1->getLastClickAt())->null();
    expect($sub2->getLastClickAt())->null();
    expect($sub3->getLastClickAt())->null();
    expect($sub4->getLastClickAt())->null();

    $this->backfiller->updateBatch([$sub1, $sub2, $sub3, $sub4]);

    $this->entityManager->refresh($sub1);
    $this->entityManager->refresh($sub2);
    $this->entityManager->refresh($sub3);
    $this->entityManager->refresh($sub4);

    $this->assertInstanceOf(DateTimeInterface::class, $sub1->getLastClickAt());
    $this->assertInstanceOf(DateTimeInterface::class, $sub2->getLastClickAt());
    $this->assertInstanceOf(DateTimeInterface::class, $sub3->getLastClickAt());


    expect($sub1->getLastClickAt()->getTimestamp())->equals($newer->getTimestamp());
    expect($sub2->getLastClickAt()->getTimestamp())->equals($sub2date->getTimestamp());
    expect($sub3->getLastClickAt()->getTimestamp())->equals($sub3date->getTimestamp());
    expect($sub4->getLastOpenAt())->null();
  }

  public function testItRetrievesSendingData(): void {
    $sub1 = (new Subscriber())->withEmail('1@e.com')->create();
    $sub2 = (new Subscriber())->withEmail('2@e.com')->create();
    $sub3 = (new Subscriber())->withEmail('3@e.com')->create();
    $sub4 = (new Subscriber())->withEmail('4@e.com')->create();
    $this->assertInstanceOf(SubscriberEntity::class, $sub1);
    $this->assertInstanceOf(SubscriberEntity::class, $sub2);
    $this->assertInstanceOf(SubscriberEntity::class, $sub3);
    $this->assertInstanceOf(SubscriberEntity::class, $sub4);
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $newsletter2 = (new Newsletter())->withSendingQueue()->create();

    $sub1sending = (new StatisticsNewsletters($newsletter, $sub1))->create();
    $older = Carbon::now()->subDays(200);
    $sub1sending->setSentAt($older);

    $sub1sending2 = (new StatisticsNewsletters($newsletter2, $sub1))->create();
    $newer = Carbon::now()->subDays(20);
    $sub1sending2->setSentAt($newer);

    $sub2sending = (new StatisticsNewsletters($newsletter2, $sub2))->create();
    $sub2date = Carbon::now()->subDays(22);
    $sub2sending->setSentAt($sub2date);

    $sub3sending = (new StatisticsNewsletters($newsletter, $sub3))->create();
    $sub3date = Carbon::now()->subDays(33);
    $sub3sending->setSentAt($sub3date);

    $this->entityManager->flush();
    $clickData = $this->backfiller->getSendingDataForBatch([$sub1->getId(), $sub2->getId(), $sub3->getId(), $sub4->getId()]);
    expect($clickData)->count(3);
    expect($clickData[$sub1->getId()]['last_sending_at'])->equals((string)$newer);
    expect($clickData[$sub2->getId()]['last_sending_at'])->equals((string)$sub2date);
    expect($clickData[$sub3->getId()]['last_sending_at'])->equals((string)$sub3date);
    expect(!isset($clickData[$sub4->getId()]))->true();
  }

  public function testItUpdatesSendingData(): void {
    $sub1 = (new Subscriber())->withEmail('1@e.com')->create();
    $sub2 = (new Subscriber())->withEmail('2@e.com')->create();
    $sub3 = (new Subscriber())->withEmail('3@e.com')->create();
    $sub4 = (new Subscriber())->withEmail('4@e.com')->create();
    $this->assertInstanceOf(SubscriberEntity::class, $sub1);
    $this->assertInstanceOf(SubscriberEntity::class, $sub2);
    $this->assertInstanceOf(SubscriberEntity::class, $sub3);
    $this->assertInstanceOf(SubscriberEntity::class, $sub4);
    $newsletter = (new Newsletter())->withSendingQueue()->create();
    $newsletter2 = (new Newsletter())->withSendingQueue()->create();

    $sub1sending = (new StatisticsNewsletters($newsletter, $sub1))->create();
    $older = Carbon::now()->subDays(200);
    $sub1sending->setSentAt($older);

    $sub1sending2 = (new StatisticsNewsletters($newsletter2, $sub1))->create();
    $newer = Carbon::now()->subDays(20);
    $sub1sending2->setSentAt($newer);

    $sub2sending = (new StatisticsNewsletters($newsletter2, $sub2))->create();
    $sub2date = Carbon::now()->subDays(22);
    $sub2sending->setSentAt($sub2date);

    $sub3sending = (new StatisticsNewsletters($newsletter, $sub3))->create();
    $sub3date = Carbon::now()->subDays(33);
    $sub3sending->setSentAt($sub3date);

    $this->entityManager->flush();

    expect($sub1->getLastSendingAt())->null();
    expect($sub2->getLastSendingAt())->null();
    expect($sub3->getLastSendingAt())->null();
    expect($sub4->getLastSendingAt())->null();

    $this->backfiller->updateBatch([$sub1, $sub2, $sub3, $sub4]);

    $this->entityManager->refresh($sub1);
    $this->entityManager->refresh($sub2);
    $this->entityManager->refresh($sub3);
    $this->entityManager->refresh($sub4);

    $this->assertInstanceOf(DateTimeInterface::class, $sub1->getLastSendingAt());
    $this->assertInstanceOf(DateTimeInterface::class, $sub2->getLastSendingAt());
    $this->assertInstanceOf(DateTimeInterface::class, $sub3->getLastSendingAt());

    expect($sub1->getLastSendingAt()->getTimestamp())->equals($newer->getTimestamp());
    expect($sub2->getLastSendingAt()->getTimestamp())->equals($sub2date->getTimestamp());
    expect($sub3->getLastSendingAt()->getTimestamp())->equals($sub3date->getTimestamp());
    expect($sub4->getLastSendingAt())->null();
  }

  public function testItRetrievesSubscribersInBatches(): void {
    for ($i = 0; $i < 30; $i++) {
      (new Subscriber())->withEmail("$i@example.com")->create();
    }
    $batch = $this->backfiller->getBatch(0, 10);
    expect($batch)->count(10);
    $last = end($batch);
    $this->assertInstanceOf(SubscriberEntity::class, $last);
    $this->assertIsInt($last->getId());

    $batch2 = $this->backfiller->getBatch($last->getId(), 5);
    expect($batch2)->count(5);
  }

  private function createOrder(int $customerId, Carbon $createdAt, $status = 'wc-completed'): int {
    $order = $this->tester->createWooCommerceOrder();
    $order->set_customer_id($customerId);
    $order->set_date_created($createdAt->toDateTimeString());
    $order->set_status($status);
    $order->save();
    $this->tester->updateWooOrderStats($order->get_id());

    return $order->get_id();
  }
}
