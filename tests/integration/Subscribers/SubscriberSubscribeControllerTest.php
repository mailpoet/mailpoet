<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use Codeception\Util\Fixtures;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;

class SubscriberSubscribeControllerTest extends \MailPoetTest {
  /** @var SettingsController */
  private $settings;

  /** @var SubscriberSubscribeController */
  private $subscribeController;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var FieldNameObfuscator */
  private $obfuscator;

  /** @var string */
  private $obfuscatedEmail;

  /** @var string */
  private $obfuscatedSegments;

  public function _before() {
    parent::_before();
    $this->cleanup();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->obfuscator = $this->diContainer->get(FieldNameObfuscator::class);
    $this->obfuscatedEmail = $this->obfuscator->obfuscate('email');
    $this->obfuscatedSegments = $this->obfuscator->obfuscate('segments');
    $this->subscribeController = $this->diContainer->get(SubscriberSubscribeController::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
  }

  public function testItCanSubscribeSubscriberWithoutConfirmation(): void {
    $this->settings->set('signup_confirmation.enabled', false);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);

    $data = [
      $this->obfuscatedEmail => 'subscriber' . rand(0, 10000) . '@example.com',
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
    ];
    $this->subscribeController->subscribe($data);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $data[$this->obfuscatedEmail]]);
    assert($subscriber instanceof SubscriberEntity);
    expect($subscriber)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
  }

  public function testItCanSubscribeSubscriberWithConfirmation(): void {
    $this->settings->set('signup_confirmation.enabled', true);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);

    $data = [
      $this->obfuscatedEmail => 'subscriber' . rand(0, 10000) . '@example.com',
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
    ];
    $this->subscribeController->subscribe($data);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $data[$this->obfuscatedEmail]]);
    assert($subscriber instanceof SubscriberEntity);
    expect($subscriber)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
  }

  public function _after(): void {
    $this->cleanup();
  }

  private function createForm(SegmentEntity $segment): FormEntity {
    $form = new FormEntity('Form' . rand(0, 10000));
    $body = Fixtures::get('form_body_template');
    // Add segment selection block
    $body[] = [
      'type' => 'segment',
      'params' => [
        'values' => [['id' => $segment->getId()]],
      ],
    ];
    $form->setBody($body);
    $form->setSettings([
      'segments_selected_by' => 'user',
      'segments' => [$segment->getId()],
    ]);
    $this->entityManager->persist($form);
    $this->entityManager->flush();
    return $form;
  }

  private function cleanup(): void {
    $this->truncateEntity(FormEntity::class);
    $this->truncateEntity(SubscriberEntity::class);
    $this->truncateEntity(SubscriberSegmentEntity::class);
    $this->truncateEntity(SegmentEntity::class);
  }
}
