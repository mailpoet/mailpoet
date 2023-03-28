<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use DateTime;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\ResponseBuilders\SegmentsResponseBuilder;
use MailPoet\API\JSON\v1\Segments;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\FormEntity;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterSegmentEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Entities\SubscriberSegmentEntity;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;

class SegmentsTest extends \MailPoetTest {
  /** @var SegmentEntity */
  public $segment3;
  /** @var SegmentEntity */
  public $segment2;
  /** @var SegmentEntity */
  public $segment1;

  /** @var Segments */
  private $endpoint;

  /** @var SegmentsResponseBuilder */
  private $responseBuilder;

  /** @var SegmentsRepository */
  private $segmentRepository;

  /** @var SubscribersRepository */
  private $subscriberRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  public function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(Segments::class);
    $this->responseBuilder = ContainerWrapper::getInstance()->get(SegmentsResponseBuilder::class);
    $this->segmentRepository = ContainerWrapper::getInstance()->get(SegmentsRepository::class);
    $this->subscriberRepository = ContainerWrapper::getInstance()->get(SubscribersRepository::class);
    $this->subscriberSegmentRepository = ContainerWrapper::getInstance()->get(SubscriberSegmentRepository::class);

    $this->segment1 = $this->segmentRepository->createOrUpdate('Segment 1');
    $this->segment2 = $this->segmentRepository->createOrUpdate('Segment 2');
    $this->segment3 = $this->segmentRepository->createOrUpdate('Segment 3');
  }

  public function testItCanGetASegment(): void {
    $response = $this->endpoint->get(/* missing id */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This list does not exist.');

    $response = $this->endpoint->get(['id' => 'not_an_id']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This list does not exist.');

    $response = $this->endpoint->get(['id' => $this->segment1->getId()]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      $this->responseBuilder->build($this->segment1)
    );
  }

  public function testItCanGetListingData(): void {
    $response = $this->endpoint->listing();

    expect($response->status)->equals(APIResponse::STATUS_OK);

    expect($response->meta)->hasKey('filters');
    expect($response->meta)->hasKey('groups');
    expect($response->meta['count'])->equals(3);

    expect($response->data)->count(3);
    expect($response->data[0]['name'])->equals($this->segment1->getName());
    expect($response->data[1]['name'])->equals($this->segment2->getName());
    expect($response->data[2]['name'])->equals($this->segment3->getName());
  }

  public function testItCanSaveASegment(): void {
    $name = 'New Segment';
    $segmentData = [
      'name' => $name,
    ];

    $response = $this->endpoint->save(/* missing data */);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a name.');
    $this->entityManager->clear();

    $response = $this->endpoint->save($segmentData);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $segment = $this->segmentRepository->findOneBy(['name' => $name]);
    $this->assertInstanceOf(SegmentEntity::class, $segment);
    expect($response->data)->equals(
      $this->responseBuilder->build($segment)
    );
  }

  public function testItCannotSaveDuplicate(): void {
    $duplicateEntry = [
      'name' => 'Segment 1',
    ];

    $response = $this->endpoint->save($duplicateEntry);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Another record already exists. Please specify a different "name".');
  }

  public function testItCanRestoreASegment(): void {
    $this->segment1->setDeletedAt(new DateTime());
    $this->segmentRepository->flush();

    $trashedSegment = $this->segmentRepository->findOneById($this->segment1->getId());
    $this->assertInstanceOf(SegmentEntity::class, $trashedSegment);
    expect($trashedSegment->getDeletedAt())->notNull();
    $this->entityManager->clear();

    $response = $this->endpoint->restore(['id' => $this->segment1->getId()]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    $segment = $this->segmentRepository->findOneById($trashedSegment->getId());
    $this->assertInstanceOf(SegmentEntity::class, $segment);
    expect($response->data)->equals(
      $this->responseBuilder->build($segment)
    );
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanTrashASegment() {
    $response = $this->endpoint->trash(['id' => $this->segment2->getId()]);
    $this->entityManager->clear();
    $segment = $this->segmentRepository->findOneById($this->segment2->getId());
    $this->assertInstanceOf(SegmentEntity::class, $segment);

    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      $this->responseBuilder->build($segment)
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  public function testItReturnsErrorWhenTrashingSegmentWithActiveNewsletter() {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Subject');
    $newsletter->setType(NewsletterEntity::TYPE_NOTIFICATION);
    $newsletterSegment = new NewsletterSegmentEntity($newsletter, $this->segment2);
    $this->entityManager->persist($newsletter);
    $this->entityManager->persist($newsletterSegment);
    $this->entityManager->flush();

    $response = $this->endpoint->trash(['id' => $this->segment2->getId()]);
    $this->entityManager->refresh($this->segment2);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals("List cannot be deleted because it’s used for 'Subject' email");
  }

  public function testItReturnsErrorWhenTrashingSegmentWithActiveForm() {
    $settings = ['segments' => [(string)$this->segment3->getId()]];
    $this->createForm('My Form', $settings);

    $response = $this->endpoint->trash(['id' => $this->segment3->getId()]);
    $this->entityManager->refresh($this->segment3);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals("List cannot be deleted because it’s used for 'My Form' form");
  }

  public function testItReturnsPluralErrorWhenTrashingSegmentWithActiveForms() {
    $settings = ['segments' => [(string)$this->segment3->getId()]];
    $this->createForm('My Form', $settings);
    $this->createForm('My other Form', $settings);

    $response = $this->endpoint->trash(['id' => $this->segment3->getId()]);
    $this->entityManager->refresh($this->segment3);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals("List cannot be deleted because it’s used for 'My Form', 'My other Form' forms");
  }

  public function testItCanTrashSegmentWithoutActiveForm() {
    $settings = ['segments' => [(string)$this->segment3->getId()]];
    $this->createForm('My Form', $settings);

    $response = $this->endpoint->trash(['id' => $this->segment2->getId()]);
    $this->entityManager->refresh($this->segment2);
    $segment = $this->segmentRepository->findOneById($this->segment2->getId());
    $this->assertInstanceOf(SegmentEntity::class, $segment);

    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      $this->responseBuilder->build($segment)
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanDeleteASegment() {
    $response = $this->endpoint->delete(['id' => $this->segment3->getId()]);
    expect($response->data)->isEmpty();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanDuplicateASegment() {
    $response = $this->endpoint->duplicate(['id' => $this->segment1->getId()]);
    $segment = $this->segmentRepository->findOneBy(['name' => 'Copy of Segment 1']);
    $this->assertInstanceOf(SegmentEntity::class, $segment);

    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      $this->responseBuilder->build($segment)
    );
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanBulkDeleteSegments() {
    $subscriber = $this->createSubscriber('test@mailpoet.com');
    $subscriberSegment = $this->createSubscriberSegment($subscriber, $this->segment1);

    $response = $this->endpoint->bulkAction([
      'action' => 'trash',
      'listing' => ['group' => 'all'],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(3);

    $response = $this->endpoint->bulkAction([
      'action' => 'delete',
      'listing' => ['group' => 'trash'],
    ]);

    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(3);

    $response = $this->endpoint->bulkAction([
      'action' => 'delete',
      'listing' => ['group' => 'trash'],
    ]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(0);

    $subsribers = $this->subscriberSegmentRepository->findBy(['segment' => $this->segment1]);
    expect($subsribers)->count(0);
  }

  private function createForm(string $formName, array $settings) {
    $form = new FormEntity($formName);
    $form->setSettings($settings);
    $this->entityManager->persist($form);
    $this->entityManager->flush();
    return $form;
  }

  private function createSubscriberSegment(SubscriberEntity $subscriber, SegmentEntity $segment): SubscriberSegmentEntity {
    $subscriberSegment = new SubscriberSegmentEntity($segment, $subscriber, SubscriberEntity::STATUS_SUBSCRIBED);
    $this->entityManager->persist($subscriberSegment);
    $this->entityManager->flush();
    return $subscriberSegment;
  }

  private function createSubscriber(string $email): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $this->subscriberRepository->persist($subscriber);
    $this->subscriberRepository->flush();
    return $subscriber;
  }
}
