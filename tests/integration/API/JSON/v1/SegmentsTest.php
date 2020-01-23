<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Segments;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;

class SegmentsTest extends \MailPoetTest {
  public $segment3;
  public $segment2;
  public $segment1;

  /** @var Segments */
  private $endpoint;

  public function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(Segments::class);
    $this->segment1 = Segment::createOrUpdate(['name' => 'Segment 1', 'type' => 'default']);
    $this->segment2 = Segment::createOrUpdate(['name' => 'Segment 2', 'type' => 'default']);
    $this->segment3 = Segment::createOrUpdate(['name' => 'Segment 3', 'type' => 'default']);
  }

  public function testItCanGetASegment() {
    $response = $this->endpoint->get(/* missing id */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This list does not exist.');

    $response = $this->endpoint->get(['id' => 'not_an_id']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This list does not exist.');

    $response = $this->endpoint->get(['id' => $this->segment1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Segment::findOne($this->segment1->id)->asArray()
    );
  }

  public function testItCanGetListingData() {
    $response = $this->endpoint->listing();

    expect($response->status)->equals(APIResponse::STATUS_OK);

    expect($response->meta)->hasKey('filters');
    expect($response->meta)->hasKey('groups');
    expect($response->meta['count'])->equals(3);

    expect($response->data)->count(3);
    expect($response->data[0]['name'])->equals($this->segment1->name);
    expect($response->data[1]['name'])->equals($this->segment2->name);
    expect($response->data[2]['name'])->equals($this->segment3->name);
  }

  public function testItCanSaveASegment() {
    $segmentData = [
      'name' => 'New Segment',
    ];

    $response = $this->endpoint->save(/* missing data */);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a name.');

    $response = $this->endpoint->save($segmentData);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Segment::where('name', 'New Segment')->findOne()->asArray()
    );
  }

  public function testItCannotSaveDuplicate() {
    $duplicateEntry = [
      'name' => 'Segment 1',
    ];

    $response = $this->endpoint->save($duplicateEntry);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Another record already exists. Please specify a different "name".');
  }

  public function testItCanRestoreASegment() {
    $this->segment1->trash();

    $trashedSegment = Segment::findOne($this->segment1->id);
    expect($trashedSegment->deletedAt)->notNull();

    $response = $this->endpoint->restore(['id' => $this->segment1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Segment::findOne($this->segment1->id)->asArray()
    );
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanTrashASegment() {
    $response = $this->endpoint->trash(['id' => $this->segment2->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Segment::findOne($this->segment2->id)->asArray()
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanDeleteASegment() {
    $response = $this->endpoint->delete(['id' => $this->segment3->id]);
    expect($response->data)->isEmpty();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanDuplicateASegment() {
    $response = $this->endpoint->duplicate(['id' => $this->segment1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Segment::where('name', 'Copy of Segment 1')->findOne()->asArray()
    );
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanBulkDeleteSegments() {
    $subscriberSegment = SubscriberSegment::createOrUpdate([
      'subscriber_id' => 1,
      'segment_id' => $this->segment1->id,
      'status' => Subscriber::STATUS_SUBSCRIBED,
    ]);

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

    expect(SubscriberSegment::findOne($subscriberSegment->id))->equals(false);
  }

  public function _after() {
    Segment::deleteMany();
  }
}
