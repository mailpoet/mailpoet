<?php
use MailPoet\API\JSON\v1\Segments;
use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\Models\Segment;

class SegmentsTest extends MailPoetTest {
  function _before() {
    $this->segment_1 = Segment::createOrUpdate(array('name' => 'Segment 1'));
    $this->segment_2 = Segment::createOrUpdate(array('name' => 'Segment 2'));
    $this->segment_3 = Segment::createOrUpdate(array('name' => 'Segment 3'));
  }

  function testItCanGetASegment() {
    $router = new Segments();

    $response = $router->get(/* missing id */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This list does not exist.');

    $response = $router->get(array('id' => 'not_an_id'));
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])->equals('This list does not exist.');

    $response = $router->get(array('id' => $this->segment_1->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Segment::findOne($this->segment_1->id)->asArray()
    );
  }

  function testItCanGetListingData() {
    $router = new Segments();
    $response = $router->listing();

    expect($response->status)->equals(APIResponse::STATUS_OK);

    expect($response->meta)->hasKey('filters');
    expect($response->meta)->hasKey('groups');
    expect($response->meta['count'])->equals(3);

    expect($response->data)->count(3);
    expect($response->data[0]['name'])->equals($this->segment_1->name);
    expect($response->data[1]['name'])->equals($this->segment_2->name);
    expect($response->data[2]['name'])->equals($this->segment_3->name);
  }

  function testItCanSaveASegment() {
    $segment_data = array(
      'name' => 'New Segment'
    );

    $router = new Segments();
    $response = $router->save(/* missing data */);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a name.');

    $response = $router->save($segment_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Segment::where('name', 'New Segment')->findOne()->asArray()
    );
  }

  function testItCannotSaveDuplicate() {
    $duplicate_entry = array(
      'name' => 'Segment 1'
    );

    $router = new Segments();
    $response = $router->save($duplicate_entry);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals(
      'Another record already exists. Please specify a different "name".'
    );
  }

  function testItCanRestoreASegment() {
    $this->segment_1->trash();

    $trashed_segment = Segment::findOne($this->segment_1->id);
    expect($trashed_segment->deleted_at)->notNull();

    $router = new Segments();
    $response = $router->restore(array('id' => $this->segment_1->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Segment::findOne($this->segment_1->id)->asArray()
    );
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanTrashASegment() {
    $router = new Segments();
    $response = $router->trash(array('id' => $this->segment_2->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Segment::findOne($this->segment_2->id)->asArray()
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanDeleteASegment() {
    $router = new Segments();
    $response = $router->delete(array('id' => $this->segment_3->id));
    expect($response->data)->isEmpty();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(1);
  }

  function testItCanDuplicateASegment() {
    $router = new Segments();
    $response = $router->duplicate(array('id' => $this->segment_1->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Segment::where('name', 'Copy of Segment 1')->findOne()->asArray()
    );
    expect($response->meta['count'])->equals(1);
  }

  function testItCanBulkDeleteSegments() {
    $router = new Segments();
    $response = $router->bulkAction(array(
      'action' => 'trash',
      'listing' => array('group' => 'all')
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(3);

    $router = new Segments();
    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(3);

    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(0);
  }

  function _after() {
    Segment::deleteMany();
  }
}
