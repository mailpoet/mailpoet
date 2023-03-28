<?php declare(strict_types = 1);

namespace MailPoet\Statistics;

use MailPoet\Entities\FormEntity;
use MailPoet\Entities\StatisticsFormEntity;
use MailPoet\Entities\SubscriberEntity;

class StatisticsFormsRepositoryTest extends \MailPoetTest {
  /** @var StatisticsFormsRepository */
  private $repository;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(StatisticsFormsRepository::class);
  }

  public function testItCanRecordStats(): void {
    $form = $this->createForm();
    $subscriber = $this->createSubscriber();
    $record = $this->repository->record($form, $subscriber);
    $this->assertInstanceOf(StatisticsFormEntity::class, $record);
    expect($record->getForm())->equals($form);
    expect($record->getSubscriber())->equals($subscriber);
    expect($this->repository->findOneBy(['form' => $form, 'subscriber' => $subscriber]))->isInstanceOf(StatisticsFormEntity::class);
  }

  public function testItDoesNotOverrideStats(): void {
    $form = $this->createForm();
    $subscriber = $this->createSubscriber();
    $record = $this->repository->record($form, $subscriber);
    $this->assertInstanceOf(StatisticsFormEntity::class, $record);
    expect($record->getForm())->equals($form);
    expect($record->getSubscriber())->equals($subscriber);

    $this->repository->record($form, $subscriber);
    expect($this->repository->findAll())->count(1);
  }

  public function testItCanRecordMultipleStats(): void {
    $form1 = $this->createForm();
    $form2 = $this->createForm();
    $subscriber1 = $this->createSubscriber();
    $subscriber2 = $this->createSubscriber();
    $this->repository->record($form1, $subscriber2);
    $this->repository->record($form2, $subscriber2);
    $this->repository->record($form1, $subscriber1);

    expect($this->repository->findAll())->count(3);
  }

  public function testItCanReturnTheTotalSignupsOfAForm(): void {
    $form1 = $this->createForm();
    $form2 = $this->createForm();
    $subscriber1 = $this->createSubscriber();
    $subscriber2 = $this->createSubscriber();
    // simulate 2 signups for form #1
    $this->repository->record($form1, $subscriber2);
    $this->repository->record($form1, $subscriber1);
    // simulate 1 signup for form #2
    $this->repository->record($form2, $subscriber2);

    expect($this->repository->getTotalSignups((int)$form1->getId()))->equals(2);
    expect($this->repository->getTotalSignups((int)$form2->getId()))->equals(1);
    expect($this->repository->findAll())->count(3);
  }

  private function createSubscriber(): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriber->setEmail('subscriber' . rand(0, 10000) . '@example.com');
    $this->entityManager->persist($subscriber);
    $this->entityManager->flush();
    return $subscriber;
  }

  private function createForm(): FormEntity {
    $form = new FormEntity('Form' . rand(0, 10000));
    $this->entityManager->persist($form);
    $this->entityManager->flush();
    return $form;
  }
}
