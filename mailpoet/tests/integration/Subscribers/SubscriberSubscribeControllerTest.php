<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use Codeception\Util\Fixtures;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberCustomFieldEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\TagEntity;
use MailPoet\Form\Util\FieldNameObfuscator;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Tag;

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

  /** @var SubscriberCustomFieldRepository */
  private $subscriberCustomFieldRepository;

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->obfuscator = $this->diContainer->get(FieldNameObfuscator::class);
    $this->obfuscatedEmail = $this->obfuscator->obfuscate('email');
    $this->obfuscatedSegments = $this->obfuscator->obfuscate('segments');
    $this->subscribeController = $this->diContainer->get(SubscriberSubscribeController::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->segmentsRepository = $this->diContainer->get(SegmentsRepository::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);
    $this->subscriberCustomFieldRepository = $this->diContainer->get(SubscriberCustomFieldRepository::class);
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
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
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
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_UNCONFIRMED);
  }

  public function testItReturnsInfoAboutErrorWhenConfirmationEmailFails(): void {
    $confirmationEmailMailerMock = $this->createMock(ConfirmationEmailMailer::class);
    $confirmationEmailMailerMock->method('sendConfirmationEmailOnce')
      ->willThrowException(new \Exception('Confirmation email error'));
    $subscriberActions = $this->getServiceWithOverrides(SubscriberActions::class, ['confirmationEmailMailer' => $confirmationEmailMailerMock]);
    $subscriberController = $this->getServiceWithOverrides(SubscriberSubscribeController::class, ['subscriberActions' => $subscriberActions]);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $form = $this->createForm($segment);

    $data = [
      $this->obfuscatedEmail => 'subscriber' . rand(0, 10000) . '@example.com',
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
    ];
    $result = $subscriberController->subscribe($data);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $data[$this->obfuscatedEmail]]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($result)->hasKey('error');
    expect($result['error'])->equals('Confirmation email error');
  }

  public function testItCanSubscribeSubscriberWithCustomField(): void {
    $this->settings->set('signup_confirmation.enabled', false);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $customField = $this->createCustomField('Custom Field');
    $form = $this->createForm($segment, [$customField]);

    $data = [
      $this->obfuscatedEmail => 'subscriber' . rand(0, 10000) . '@example.com',
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
      'cf_' . $customField->getId() => 'field value',
    ];
    $this->subscribeController->subscribe($data);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $data[$this->obfuscatedEmail]]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    expect($subscriber)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber->getStatus())->equals(SubscriberEntity::STATUS_SUBSCRIBED);
    $subscriberCustomFields = $this->subscriberCustomFieldRepository->findBy(['subscriber' => $subscriber]);
    expect($subscriberCustomFields)->count(1);
    $subscriberCustomField = reset($subscriberCustomFields);
    $this->assertInstanceOf(SubscriberCustomFieldEntity::class, $subscriberCustomField);
    expect($subscriberCustomField)->isInstanceOf(SubscriberCustomFieldEntity::class);
    expect($subscriberCustomField->getSubscriber())->equals($subscriber);
    expect($subscriberCustomField->getCustomField())->equals($customField);
    expect($subscriberCustomField->getValue())->equals($data['cf_' . $customField->getId()]);
  }

  public function testItCanSubscribeSubscriberWithTags(): void {
    $this->settings->set('signup_confirmation.enabled', false);
    $segment = $this->segmentsRepository->createOrUpdate('Segment 1');
    $tag = (new Tag())->withName('My Tag')->create();
    $form = $this->createForm($segment, [], [$tag]);

    $data = [
      $this->obfuscatedEmail => 'subscriber' . rand(0, 10000) . '@example.com',
      $this->obfuscatedSegments => [$segment->getId()],
      'form_id' => $form->getId(),
    ];
    $this->subscribeController->subscribe($data);

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $data[$this->obfuscatedEmail]]);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber);
    $this->assertEquals(SubscriberEntity::STATUS_SUBSCRIBED, $subscriber->getStatus());
    $this->assertCount(1, $subscriber->getSubscriberTags());
    $this->assertNotNull($subscriber->getSubscriberTag($tag));
  }

  /**
   * @param CustomFieldEntity[] $customFields
   * @param TagEntity[] $tags
   */
  private function createForm(
    SegmentEntity $segment,
    array $customFields = [],
    array $tags = []
  ): FormEntity {
    $form = new FormEntity('Form' . rand(0, 10000));
    $body = Fixtures::get('form_body_template');
    // Add segment selection block
    $body[] = [
      'type' => 'segment',
      'params' => [
        'values' => [['id' => $segment->getId()]],
      ],
    ];
    foreach ($customFields as $customField) {
      $body[] = [
        'type' => $customField->getType(),
        'name' => $customField->getName(),
        'id' => $customField->getId(),
        'params' => $customField->getParams(),
      ];
    }
    $tagNames = [];
    foreach ($tags as $tag) {
      $tagNames[] = $tag->getName();
    }

    $form->setBody($body);
    $form->setSettings([
      'segments_selected_by' => 'user',
      'segments' => [$segment->getId()],
      'tags' => $tagNames,
    ]);
    $this->entityManager->persist($form);
    $this->entityManager->flush();
    return $form;
  }

  private function createCustomField(string $name): CustomFieldEntity {
    $customField = new CustomFieldEntity();
    $customField->setType(CustomFieldEntity::TYPE_TEXT);
    $customField->setName($name);
    $this->entityManager->persist($customField);
    $this->entityManager->flush();
    return $customField;
  }
}
