<?php

namespace MailPoet\API\JSON\v1;

use Codeception\Stub;
use MailPoet\API\JSON\ResponseBuilders\DynamicSegmentsResponseBuilder;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Listing\BulkActionController;
use MailPoet\Listing\Handler;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\DynamicSegmentFilter;
use MailPoet\Segments\DynamicSegments\DynamicSegmentsListingRepository;
use MailPoet\Segments\DynamicSegments\SegmentSaveController;
use MailPoet\Segments\SegmentsRepository;

class DynamicSegmentsTest extends \MailPoetTest {

  const SUCCESS_RESPONSE_CODE = 200;
  const SEGMENT_NOT_FOUND_RESPONSE_CODE = 404;
  const INVALID_DATA_RESPONSE_CODE = 400;
  const SERVER_ERROR_RESPONSE_CODE = 409;

  /** @var BulkActionController */
  private $bulkAction;

  /** @var Handler */
  private $listingHandler;
  /** @var DynamicSegmentsListingRepository */
  private $listingRepository;
  /** @var DynamicSegmentsResponseBuilder */
  private $responseBuilder;
  /** @var SegmentsRepository */
  private $segmentsRepository;
  /** @var SegmentSaveController */
  private $saveController;

  public function _before() {
    $this->bulkAction = ContainerWrapper::getInstance()->get(BulkActionController::class);
    $this->listingHandler = ContainerWrapper::getInstance()->get(Handler::class);
    $this->listingRepository = ContainerWrapper::getInstance()->get(DynamicSegmentsListingRepository::class);
    $this->responseBuilder = ContainerWrapper::getInstance()->get(DynamicSegmentsResponseBuilder::class);
    $this->segmentsRepository = ContainerWrapper::getInstance()->get(SegmentsRepository::class);
    $this->saveController = ContainerWrapper::getInstance()->get(SegmentSaveController::class);
  }

  public function testGetReturnsResponse() {
    $segment = $this->createDynamicSegmentEntity('s1', '');
    $endpoint = new DynamicSegments(
      $this->bulkAction,
      $this->listingHandler,
      $this->listingRepository,
      $this->responseBuilder,
      $this->segmentsRepository,
      $this->saveController
    );
    $response = $endpoint->get(['id' => $segment->getId()]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\SuccessResponse');
    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data['id'])->equals($segment->getId());
  }

  public function testGetReturnsError() {
    $endpoint = new DynamicSegments($this->bulkAction, $this->listingHandler, $this->listingRepository, $this->responseBuilder, $this->segmentsRepository, $this->saveController);
    $response = $endpoint->get(['id' => 5]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\ErrorResponse');
    expect($response->status)->equals(self::SEGMENT_NOT_FOUND_RESPONSE_CODE);
  }

  public function testSaverSavesData() {
    $endpoint = new DynamicSegments($this->bulkAction, $this->listingHandler, $this->listingRepository, $this->responseBuilder, $this->segmentsRepository, $this->saveController);
    $response = $endpoint->save([
      'name' => 'Test dynamic',
      'description' => 'description dynamic',
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => 'editor',
    ]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\SuccessResponse');
    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data['name'])->equals('Test dynamic');
  }

  public function testSaverReturnsErrorOnInvalidFilterData() {
    $endpoint = new DynamicSegments($this->bulkAction, $this->listingHandler, $this->listingRepository, $this->responseBuilder, $this->segmentsRepository, $this->saveController);
    $response = $endpoint->save([
      'name' => 'Test dynamic',
    ]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\ErrorResponse');
    expect($response->status)->equals(self::INVALID_DATA_RESPONSE_CODE);
    expect($response->errors[0]['message'])->equals('Segment type is missing.');
  }

  public function testSaverReturnsErrorOnDuplicateRecord() {
    $endpoint = new DynamicSegments($this->bulkAction, $this->listingHandler, $this->listingRepository, $this->responseBuilder, $this->segmentsRepository, $this->saveController);
    $data = [
      'name' => 'Test dynamic',
      'description' => 'description dynamic',
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
      'wordpressRole' => 'editor',
    ];
    $endpoint->save($data);
    $response = $endpoint->save($data);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\ErrorResponse');
    expect($response->status)->equals(self::INVALID_DATA_RESPONSE_CODE);
  }

  public function testItCanTrashASegment() {
    $dynamicSegment = $this->createDynamicSegmentEntity('Trash test', 'description');

    $endpoint = new DynamicSegments($this->bulkAction, $this->listingHandler, $this->listingRepository, $this->responseBuilder, $this->segmentsRepository, $this->saveController);
    $response = $endpoint->trash(['id' => $dynamicSegment->getId()]);

    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data['name'])->equals($dynamicSegment->getName());
    expect($response->meta['count'])->equals(1);

    $this->entityManager->refresh($dynamicSegment);
    assert($dynamicSegment instanceof SegmentEntity);
    expect($dynamicSegment->getDeletedAt())->notNull();
  }

  public function testItCanRestoreASegment() {
    $dynamicSegment = $this->createDynamicSegmentEntity('Trash test', 'description');

    $endpoint = new DynamicSegments($this->bulkAction, $this->listingHandler, $this->listingRepository, $this->responseBuilder, $this->segmentsRepository, $this->saveController);
    $response = $endpoint->restore(['id' => $dynamicSegment->getId()]);

    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data['name'])->equals($dynamicSegment->getName());
    expect($response->meta['count'])->equals(1);

    $this->entityManager->refresh($dynamicSegment);
    assert($dynamicSegment instanceof SegmentEntity);
    expect($dynamicSegment->getDeletedAt())->null();
  }

  public function testItCanDeleteASegment() {
    DynamicSegment::deleteMany();
    $dynamicSegment = DynamicSegment::createOrUpdate([
      'name' => 'Delete test',
      'description' => 'description',
    ]);
    $filter = DynamicSegmentFilter::createOrUpdate([
      'segment_id' => $dynamicSegment->id,
    ]);
    $loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader', [
      'load' => function () use($dynamicSegment) {
        return $dynamicSegment;
      },
    ]);

    $endpoint = new DynamicSegments($this->bulkAction, $this->listingHandler, $this->listingRepository, $this->responseBuilder, $this->segmentsRepository, $this->saveController, $loader);
    $response = $endpoint->delete(['id' => $dynamicSegment->id]);

    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data)->equals(null);
    expect($response->meta['count'])->equals(1);

    expect(DynamicSegment::findOne($dynamicSegment->id))->equals(false);
    expect(DynamicSegmentFilter::findOne($filter->id))->equals(false);
  }

  public function testItCanBulkDeleteSegments() {
    DynamicSegment::deleteMany();
    $dynamicSegment1 = DynamicSegment::createOrUpdate([
      'name' => 'Test 1',
      'description' => 'description',
    ]);
    $dynamicSegment2 = DynamicSegment::createOrUpdate([
      'name' => 'Test 2',
      'description' => 'description',
    ]);
    $filter = DynamicSegmentFilter::createOrUpdate([
      'segment_id' => $dynamicSegment1->id,
    ]);

    $endpoint = new DynamicSegments(
      $this->bulkAction,
      $this->listingHandler,
      $this->listingRepository,
      $this->responseBuilder,
      $this->segmentsRepository,
      $this->saveController
    );
    $response = $endpoint->bulkAction([
      'action' => 'trash',
      'listing' => ['group' => 'all'],
    ]);
    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->meta['count'])->equals(2);

    $response = $endpoint->bulkAction([
      'action' => 'delete',
      'listing' => ['group' => 'trash'],
    ]);

    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->meta['count'])->equals(2);

    $response = $endpoint->bulkAction([
      'action' => 'delete',
      'listing' => ['group' => 'trash'],
    ]);
    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->meta['count'])->equals(0);

    expect(DynamicSegment::count())->equals(0);
    expect(DynamicSegmentFilter::findOne($filter->id))->equals(false);
  }

  private function createDynamicSegmentEntity(string $name, string $description): SegmentEntity {
    $segment = new SegmentEntity($name, SegmentEntity::TYPE_DYNAMIC, $description);
    $dynamicFilter = new DynamicSegmentFilterEntity($segment, new DynamicSegmentFilterData([
      'wordpressRole' => 'editor',
      'segmentType' => DynamicSegmentFilterData::TYPE_USER_ROLE,
    ]));
    $segment->getDynamicFilters()->add($dynamicFilter);
    $this->entityManager->persist($segment);
    $this->entityManager->persist($dynamicFilter);
    $this->entityManager->flush();
    return $segment;
  }

  public function _after() {
    parent::_after();
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(DynamicSegmentFilterEntity::class);
  }
}
