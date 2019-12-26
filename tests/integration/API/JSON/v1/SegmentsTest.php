<?php

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\Segments;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;

class SegmentsTest extends \MailPoetTest {

  /** @var Segments */
  private $endpoint;

  public function _before() {
    parent::_before();
    $this->endpoint = ContainerWrapper::getInstance()->get(Segments::class);
    $this->segment_1 = Segment::createOrUpdate(['name' => 'Segment 1', 'type' => 'default']);
    $this->segment_2 = Segment::createOrUpdate(['name' => 'Segment 2', 'type' => 'default']);
    $this->segment_3 = Segment::createOrUpdate(['name' => 'Segment 3', 'type' => 'default']);
  }

  public function testItCanGetASegment() {
    $response = $this->endpoint->get(/* missing id */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This list does not exist.');

    $response = $this->endpoint->get(['id' => 'not_an_id']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This list does not exist.');

    $response = $this->endpoint->get(['id' => $this->segment_1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Segment::findOne($this->segment_1->id)->asArray()
    );
  }

  public function testItCanGetListingData() {
    $response = $this->endpoint->listing();

    expect($response->status)->equals(APIResponse::STATUS_OK);

    expect($response->meta)->hasKey('filters');
    expect($response->meta)->hasKey('groups');
    expect($response->meta['count'])->equals(3);

    expect($response->data)->count(3);
    expect($response->data[0]['name'])->equals($this->segment_1->name);
    expect($response->data[1]['name'])->equals($this->segment_2->name);
    expect($response->data[2]['name'])->equals($this->segment_3->name);
  }

  public function testItCanSaveASegment() {
    $segment_data = [
      'name' => 'New Segment',
    ];

    $response = $this->endpoint->save(/* missing data */);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a name.');

    $response = $this->endpoint->save($segment_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Segment::where('name', 'New Segment')->findOne()->asArray()
    );
  }

  public function testItCannotSaveDuplicate() {
    $duplicate_entry = [
      'name' => 'Segment 1',
    ];

    $response = $this->endpoint->save($duplicate_entry);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals(
      'Another record already exists. Please specify a different "name".'
    );
  }

  public function testItCanRestoreASegment() {
    $this->segment_1->trash();

    $trashed_segment = Segment::findOne($this->segment_1->id);
    expect($trashed_segment->deleted_at)->notNull();

    $response = $this->endpoint->restore(['id' => $this->segment_1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Segment::findOne($this->segment_1->id)->asArray()
    );
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanTrashASegment() {
    $response = $this->endpoint->trash(['id' => $this->segment_2->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Segment::findOne($this->segment_2->id)->asArray()
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanDeleteASegment() {
    $response = $this->endpoint->delete(['id' => $this->segment_3->id]);
    expect($response->data)->isEmpty();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanDuplicateASegment() {
    $response = $this->endpoint->duplicate(['id' => $this->segment_1->id]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Segment::where('name', 'Copy of Segment 1')->findOne()->asArray()
    );
    expect($response->meta['count'])->equals(1);
  }

  public function testItCanBulkDeleteSegments() {
    $subscriber_segment = SubscriberSegment::createOrUpdate([
      'subscriber_id' => 1,
      'segment_id' => $this->segment_1->id,
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

    expect(SubscriberSegment::findOne($subscriber_segment->id))->equals(false);
  }

  public function _after() {
    Segment::deleteMany();
  }
}
