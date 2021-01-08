<?php

namespace MailPoet\API\JSON\v1;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\API\JSON\ResponseBuilders\DynamicSegmentsResponseBuilder;
use MailPoet\DI\ContainerWrapper;
use MailPoet\DynamicSegments\Exceptions\ErrorSavingException;
use MailPoet\DynamicSegments\Exceptions\InvalidSegmentTypeException;
use MailPoet\DynamicSegments\Filters\UserRole;
use MailPoet\Listing\BulkActionController;
use MailPoet\Listing\Handler;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\DynamicSegmentFilter;
use MailPoet\Models\Model;
use MailPoet\Segments\DynamicSegments\DynamicSegmentsListingRepository;

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

  public function _before() {
    $this->bulkAction = ContainerWrapper::getInstance()->get(BulkActionController::class);
    $this->listingHandler = ContainerWrapper::getInstance()->get(Handler::class);
    $this->listingRepository = ContainerWrapper::getInstance()->get(DynamicSegmentsListingRepository::class);
    $this->responseBuilder = ContainerWrapper::getInstance()->get(DynamicSegmentsResponseBuilder::class);
  }

  public function testGetReturnsResponse() {
    $loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader', [
      'load' => function () {
        $dynamicSegment = DynamicSegment::create();
        $dynamicSegment->hydrate([
          'name' => 's1',
          'description' => '',
        ]);
        $dynamicSegment->setFilters([new UserRole('Editor', 'or')]);
        return $dynamicSegment;
      },
    ]);
    $endpoint = new DynamicSegments($this->bulkAction, $this->listingHandler, $this->listingRepository, $this->responseBuilder, null, null, $loader);
    $response = $endpoint->get(['id' => 5]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\SuccessResponse');
    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data)->equals([
      'id' => null,
      'name' => 's1',
      'description' => '',
      'segmentType' => 'userRole',
      'wordpressRole' => 'Editor',
      'connect' => 'or',
    ]);
  }

  public function testGetReturnsError() {
    $loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader', [
      'load' => function () {
        throw new \InvalidArgumentException('segment not found');
      },
    ]);
    $endpoint = new DynamicSegments($this->bulkAction, $this->listingHandler, $this->listingRepository, $this->responseBuilder, null, null, $loader);
    $response = $endpoint->get(['id' => 5]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\ErrorResponse');
    expect($response->status)->equals(self::SEGMENT_NOT_FOUND_RESPONSE_CODE);
  }

  public function testSaverSavesData() {
    $mapper = Stub::makeEmpty('\MailPoet\DynamicSegments\Mappers\FormDataMapper', ['mapDataToDB' => Expected::once(function () {
      $dynamicSegment = DynamicSegment::create();
      $dynamicSegment->hydrate([
        'name' => 'name',
        'description' => 'description',
      ]);
      return $dynamicSegment;
    })]);
    $saver = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Saver', ['save' => Expected::once()]);

    $endpoint = new DynamicSegments($this->bulkAction, $this->listingHandler, $this->listingRepository, $this->responseBuilder, $mapper, $saver);
    $response = $endpoint->save([]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\SuccessResponse');
    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
  }

  public function testSaverReturnsErrorOnInvalidData() {
    $mapper = Stub::makeEmpty('\MailPoet\DynamicSegments\Mappers\FormDataMapper', ['mapDataToDB' => Expected::once(function () {
      throw new InvalidSegmentTypeException();
    })]);
    $saver = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Saver', ['save' => Expected::never()]);

    $endpoint = new DynamicSegments($this->bulkAction, $this->listingHandler, $this->listingRepository, $this->responseBuilder, $mapper, $saver);
    $response = $endpoint->save([]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\ErrorResponse');
    expect($response->status)->equals(self::INVALID_DATA_RESPONSE_CODE);
  }

  public function testSaverReturnsErrorOnSave() {
    $mapper = Stub::makeEmpty('\MailPoet\DynamicSegments\Mappers\FormDataMapper', ['mapDataToDB' => Expected::once(function () {
      $dynamicSegment = DynamicSegment::create();
      $dynamicSegment->hydrate([
        'name' => 'name',
        'description' => 'description',
      ]);
      return $dynamicSegment;
    })]);
    $saver = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Saver', ['save' => Expected::once(function () {
      throw new ErrorSavingException('Error saving data', Model::DUPLICATE_RECORD);
    })]);

    $endpoint = new DynamicSegments($this->bulkAction, $this->listingHandler, $this->listingRepository, $this->responseBuilder, $mapper, $saver);
    $response = $endpoint->save([]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\ErrorResponse');
    expect($response->status)->equals(self::SERVER_ERROR_RESPONSE_CODE);
    expect($response->errors[0]['message'])->equals('Error saving data');
  }

  public function testItCanTrashASegment() {
    DynamicSegment::deleteMany();
    $dynamicSegment = DynamicSegment::createOrUpdate([
      'name' => 'Trash test',
      'description' => 'description',
    ]);
    $loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader', [
      'load' => function () use($dynamicSegment) {
        return $dynamicSegment;
      },
    ]);

    $endpoint = new DynamicSegments($this->bulkAction, $this->listingHandler, $this->listingRepository, $this->responseBuilder, null, null, $loader);
    $response = $endpoint->trash(['id' => $dynamicSegment->id]);

    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data)->equals($dynamicSegment->asArray());
    expect($response->meta['count'])->equals(1);

    $dynamicSegment = DynamicSegment::findOne($dynamicSegment->id);
    expect($dynamicSegment->deletedAt)->notNull();

    $dynamicSegment->delete();
  }

  public function testItCanRestoreASegment() {
    DynamicSegment::deleteMany();
    $dynamicSegment = DynamicSegment::createOrUpdate([
      'name' => 'Restore test',
      'description' => 'description',
    ]);
    $loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader', [
      'load' => function () use($dynamicSegment) {
        return $dynamicSegment;
      },
    ]);

    $endpoint = new DynamicSegments($this->bulkAction, $this->listingHandler, $this->listingRepository, $this->responseBuilder, null, null, $loader);
    $response = $endpoint->restore(['id' => $dynamicSegment->id]);

    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data)->equals($dynamicSegment->asArray());
    expect($response->meta['count'])->equals(1);

    $dynamicSegment = DynamicSegment::findOne($dynamicSegment->id);
    expect($dynamicSegment->deletedAt)->equals(null);

    $dynamicSegment->delete();
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

    $endpoint = new DynamicSegments($this->bulkAction, $this->listingHandler, $this->listingRepository, $this->responseBuilder, null, null, $loader);
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
      null,
      null,
      null
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
}
