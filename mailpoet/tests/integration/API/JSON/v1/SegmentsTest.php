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
use MailPoet\Segments\SegmentsRepository;

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

  public function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(Segments::class);
    $this->responseBuilder = ContainerWrapper::getInstance()->get(SegmentsResponseBuilder::class);
    $this->segmentRepository = ContainerWrapper::getInstance()->get(SegmentsRepository::class);

    $this->segment1 = $this->segmentRepository->createOrUpdate('Segment 1');
    $this->segment2 = $this->segmentRepository->createOrUpdate('Segment 2');
    $this->segment3 = $this->segmentRepository->createOrUpdate('Segment 3');
  }

  public function testItCanGetASegment(): void {
    $response = $this->endpoint->get(/* missing id */);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    verify($response->errors[0]['message'])->equals('This list does not exist.');

    $response = $this->endpoint->get(['id' => 'not_an_id']);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    verify($response->errors[0]['message'])->equals('This list does not exist.');

    $response = $this->endpoint->get(['id' => $this->segment1->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data)->equals(
      $this->responseBuilder->build($this->segment1)
    );
  }

  public function testItCanSaveASegment(): void {
    $name = 'New Segment';
    $segmentData = [
      'name' => $name,
    ];

    $response = $this->endpoint->save(/* missing data */);
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    verify($response->errors[0]['message'])->equals('Please specify a name.');
    $this->entityManager->clear();

    $response = $this->endpoint->save($segmentData);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $segment = $this->segmentRepository->findOneBy(['name' => $name]);
    $this->assertInstanceOf(SegmentEntity::class, $segment);
    verify($response->data)->equals(
      $this->responseBuilder->build($segment)
    );
  }

  public function testItCanSaveAndReturnPublicDescription(): void {
    $name = 'Public description segment';
    $publicDescription = "Public description\nSecond line";

    $response = $this->endpoint->save([
      'name' => $name,
      'description' => 'Regular description',
      'public_description' => $publicDescription,
    ]);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    $segment = $this->segmentRepository->findOneBy(['name' => $name]);
    $this->assertInstanceOf(SegmentEntity::class, $segment);
    verify($segment->getPublicDescription())->equals($publicDescription);
    verify($response->data['public_description'])->equals($publicDescription);

    $response = $this->endpoint->get(['id' => $segment->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['public_description'])->equals($publicDescription);

    $response = $this->endpoint->get(['id' => $segment->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['public_description'])->equals($publicDescription);
  }

  public function testItPreservesAndClearsPublicDescriptionOnSave(): void {
    $segment = $this->segmentRepository->createOrUpdate(
      'Public description update',
      'Description',
      SegmentEntity::TYPE_DEFAULT,
      [],
      null,
      true,
      null,
      null,
      'Keep me'
    );

    $response = $this->endpoint->save([
      'id' => $segment->getId(),
      'name' => 'Public description update',
    ]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $this->entityManager->refresh($segment);
    verify($segment->getPublicDescription())->equals('Keep me');
    verify($response->data['public_description'])->equals('Keep me');

    $response = $this->endpoint->save([
      'id' => $segment->getId(),
      'name' => 'Public description update',
      'public_description' => '',
    ]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $this->entityManager->refresh($segment);
    verify($segment->getPublicDescription())->equals('');
    verify($response->data['public_description'])->equals('');
  }

  public function testItCannotSaveDuplicate(): void {
    $duplicateEntry = [
      'name' => 'Segment 1',
    ];

    $response = $this->endpoint->save($duplicateEntry);
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    verify($response->errors[0]['message'])->equals('Another record already exists. Please specify a different "name".');
  }

  public function testItCanRestoreASegment(): void {
    $this->segment1->setDeletedAt(new DateTime());
    $this->segmentRepository->flush();

    $trashedSegment = $this->segmentRepository->findOneById($this->segment1->getId());
    $this->assertInstanceOf(SegmentEntity::class, $trashedSegment);
    verify($trashedSegment->getDeletedAt())->notNull();
    $this->entityManager->clear();

    $response = $this->endpoint->restore(['id' => $this->segment1->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    $segment = $this->segmentRepository->findOneById($trashedSegment->getId());
    $this->assertInstanceOf(SegmentEntity::class, $segment);
    verify($response->data)->equals(
      $this->responseBuilder->build($segment)
    );
    verify($response->data['deleted_at'])->null();
    verify($response->meta['count'])->equals(1);
  }

  public function testItCanTrashASegment() {
    $response = $this->endpoint->trash(['id' => $this->segment2->getId()]);
    $this->entityManager->clear();
    $segment = $this->segmentRepository->findOneById($this->segment2->getId());
    $this->assertInstanceOf(SegmentEntity::class, $segment);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data)->equals(
      $this->responseBuilder->build($segment)
    );
    verify($response->data['deleted_at'])->notNull();
    verify($response->meta['count'])->equals(1);
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
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    verify($response->errors[0]['message'])->equals("List cannot be deleted because it’s used for 'Subject' email");
  }

  public function testItReturnsErrorWhenTrashingSegmentWithActiveForm() {
    $settings = ['segments' => [(string)$this->segment3->getId()]];
    $this->createForm('My Form', $settings);

    $response = $this->endpoint->trash(['id' => $this->segment3->getId()]);
    $this->entityManager->refresh($this->segment3);
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    verify($response->errors[0]['message'])->equals("List cannot be deleted because it’s used for 'My Form' form");
  }

  public function testItReturnsPluralErrorWhenTrashingSegmentWithActiveForms() {
    $settings = ['segments' => [(string)$this->segment3->getId()]];
    $this->createForm('My Form', $settings);
    $this->createForm('My other Form', $settings);

    $response = $this->endpoint->trash(['id' => $this->segment3->getId()]);
    $this->entityManager->refresh($this->segment3);
    verify($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    verify($response->errors[0]['message'])->equals("List cannot be deleted because it’s used for 'My Form', 'My other Form' forms");
  }

  public function testItCanTrashSegmentWithoutActiveForm() {
    $settings = ['segments' => [(string)$this->segment3->getId()]];
    $this->createForm('My Form', $settings);

    $response = $this->endpoint->trash(['id' => $this->segment2->getId()]);
    $this->entityManager->refresh($this->segment2);
    $segment = $this->segmentRepository->findOneById($this->segment2->getId());
    $this->assertInstanceOf(SegmentEntity::class, $segment);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data)->equals(
      $this->responseBuilder->build($segment)
    );
    verify($response->data['deleted_at'])->notNull();
    verify($response->meta['count'])->equals(1);
  }

  public function testItCanDeleteASegment() {
    $response = $this->endpoint->delete(['id' => $this->segment3->getId()]);
    verify($response->data)->empty();
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->meta['count'])->equals(1);
  }

  public function testItCanDuplicateASegment() {
    $response = $this->endpoint->duplicate(['id' => $this->segment1->getId()]);
    $segment = $this->segmentRepository->findOneBy(['name' => 'Copy of Segment 1']);
    $this->assertInstanceOf(SegmentEntity::class, $segment);

    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data)->equals(
      $this->responseBuilder->build($segment)
    );
    verify($response->meta['count'])->equals(1);
  }

  private function createForm(string $formName, array $settings) {
    $form = new FormEntity($formName);
    $form->setSettings($settings);
    $this->entityManager->persist($form);
    $this->entityManager->flush();
    return $form;
  }
}
