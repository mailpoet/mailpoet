<?php

namespace MailPoet\API\JSON\v1;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\DI\ContainerWrapper;
use MailPoet\DynamicSegments\Exceptions\ErrorSavingException;
use MailPoet\DynamicSegments\Exceptions\InvalidSegmentTypeException;
use MailPoet\DynamicSegments\Filters\UserRole;
use MailPoet\Listing\BulkActionController;
use MailPoet\Listing\Handler;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\DynamicSegmentFilter;
use MailPoet\Models\Model;

class DynamicSegmentsTest extends \MailPoetTest {

  const SUCCESS_RESPONSE_CODE = 200;
  const SEGMENT_NOT_FOUND_RESPONSE_CODE = 404;
  const INVALID_DATA_RESPONSE_CODE = 400;
  const SERVER_ERROR_RESPONSE_CODE = 409;

  /** @var BulkActionController */
  private $bulk_action;

  /** @var Handler */
  private $listing_handler;

  function _before() {
    $this->bulk_action = ContainerWrapper::getInstance()->get(BulkActionController::class);
    $this->listing_handler = ContainerWrapper::getInstance()->get(Handler::class);
  }

  function testGetReturnsResponse() {
    $loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader', [
      'load' => function () {
        $dynamic_segment = DynamicSegment::create();
        $dynamic_segment->hydrate([
          'name' => 's1',
          'description' => '',
        ]);
        $dynamic_segment->setFilters([new UserRole('Editor', 'or')]);
        return $dynamic_segment;
      },
    ]);
    $endpoint = new DynamicSegments($this->bulk_action, $this->listing_handler, null, null, $loader);
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

  function testGetReturnsError() {
    $loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader', [
      'load' => function () {
        throw new \InvalidArgumentException('segment not found');
      },
    ]);
    $endpoint = new DynamicSegments($this->bulk_action, $this->listing_handler, null, null, $loader);
    $response = $endpoint->get(['id' => 5]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\ErrorResponse');
    expect($response->status)->equals(self::SEGMENT_NOT_FOUND_RESPONSE_CODE);
  }

  function testSaverSavesData() {
    $mapper = Stub::makeEmpty('\MailPoet\DynamicSegments\Mappers\FormDataMapper', ['mapDataToDB' => Expected::once(function () {
      $dynamic_segment = DynamicSegment::create();
      $dynamic_segment->hydrate([
        'name' => 'name',
        'description' => 'description',
      ]);
      return $dynamic_segment;
    })]);
    $saver = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Saver', ['save' => Expected::once()]);

    $endpoint = new DynamicSegments($this->bulk_action, $this->listing_handler, $mapper, $saver);
    $response = $endpoint->save([]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\SuccessResponse');
    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
  }

  function testSaverReturnsErrorOnInvalidData() {
    $mapper = Stub::makeEmpty('\MailPoet\DynamicSegments\Mappers\FormDataMapper', ['mapDataToDB' => Expected::once(function () {
      throw new InvalidSegmentTypeException();
    })]);
    $saver = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Saver', ['save' => Expected::never()]);

    $endpoint = new DynamicSegments($this->bulk_action, $this->listing_handler, $mapper, $saver);
    $response = $endpoint->save([]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\ErrorResponse');
    expect($response->status)->equals(self::INVALID_DATA_RESPONSE_CODE);
  }

  function testSaverReturnsErrorOnSave() {
    $mapper = Stub::makeEmpty('\MailPoet\DynamicSegments\Mappers\FormDataMapper', ['mapDataToDB' => Expected::once(function () {
      $dynamic_segment = DynamicSegment::create();
      $dynamic_segment->hydrate([
        'name' => 'name',
        'description' => 'description',
      ]);
      return $dynamic_segment;
    })]);
    $saver = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Saver', ['save' => Expected::once(function () {
      throw new ErrorSavingException('Error saving data', Model::DUPLICATE_RECORD);
    })]);

    $endpoint = new DynamicSegments($this->bulk_action, $this->listing_handler, $mapper, $saver);
    $response = $endpoint->save([]);
    expect($response)->isInstanceOf('\MailPoet\API\JSON\ErrorResponse');
    expect($response->status)->equals(self::SERVER_ERROR_RESPONSE_CODE);
    expect($response->errors[0]['message'])->equals('Error saving data');
  }

  function testItCanTrashASegment() {
    DynamicSegment::deleteMany();
    $dynamic_segment = DynamicSegment::createOrUpdate([
      'name' => 'Trash test',
      'description' => 'description',
    ]);
    $loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader', [
      'load' => function () use($dynamic_segment) {
        return $dynamic_segment;
      },
    ]);

    $endpoint = new DynamicSegments($this->bulk_action, $this->listing_handler, null, null, $loader);
    $response = $endpoint->trash(['id' => $dynamic_segment->id]);

    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data)->equals($dynamic_segment->asArray());
    expect($response->meta['count'])->equals(1);

    $dynamic_segment = DynamicSegment::findOne($dynamic_segment->id);
    expect($dynamic_segment->deleted_at)->notNull();

    $dynamic_segment->delete();
  }

  function testItCanRestoreASegment() {
    DynamicSegment::deleteMany();
    $dynamic_segment = DynamicSegment::createOrUpdate([
      'name' => 'Restore test',
      'description' => 'description',
    ]);
    $loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader', [
      'load' => function () use($dynamic_segment) {
        return $dynamic_segment;
      },
    ]);

    $endpoint = new DynamicSegments($this->bulk_action, $this->listing_handler, null, null, $loader);
    $response = $endpoint->restore(['id' => $dynamic_segment->id]);

    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data)->equals($dynamic_segment->asArray());
    expect($response->meta['count'])->equals(1);

    $dynamic_segment = DynamicSegment::findOne($dynamic_segment->id);
    expect($dynamic_segment->deleted_at)->equals(null);

    $dynamic_segment->delete();
  }

  function testItCanDeleteASegment() {
    DynamicSegment::deleteMany();
    $dynamic_segment = DynamicSegment::createOrUpdate([
      'name' => 'Delete test',
      'description' => 'description',
    ]);
    $filter = DynamicSegmentFilter::createOrUpdate([
      'segment_id' => $dynamic_segment->id,
    ]);
    $loader = Stub::makeEmpty('\MailPoet\DynamicSegments\Persistence\Loading\SingleSegmentLoader', [
      'load' => function () use($dynamic_segment) {
        return $dynamic_segment;
      },
    ]);

    $endpoint = new DynamicSegments($this->bulk_action, $this->listing_handler, null, null, $loader);
    $response = $endpoint->delete(['id' => $dynamic_segment->id]);

    expect($response->status)->equals(self::SUCCESS_RESPONSE_CODE);
    expect($response->data)->equals(null);
    expect($response->meta['count'])->equals(1);

    expect(DynamicSegment::findOne($dynamic_segment->id))->equals(false);
    expect(DynamicSegmentFilter::findOne($filter->id))->equals(false);
  }

  function testItCanBulkDeleteSegments() {
    DynamicSegment::deleteMany();
    $dynamic_segment_1 = DynamicSegment::createOrUpdate([
      'name' => 'Test 1',
      'description' => 'description',
    ]);
    $dynamic_segment_2 = DynamicSegment::createOrUpdate([
      'name' => 'Test 2',
      'description' => 'description',
    ]);
    $filter = DynamicSegmentFilter::createOrUpdate([
      'segment_id' => $dynamic_segment_1->id,
    ]);

    $endpoint = new DynamicSegments($this->bulk_action, $this->listing_handler, null, null, null);
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
