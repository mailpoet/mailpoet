<?php
use \MailPoet\Router\Segments;
use \MailPoet\Models\Segment;

class SegmentsCest {
  function _before() {
    $this->segment_1 = Segment::createOrUpdate(array('name' => 'Segment 1'));
    $this->segment_2 = Segment::createOrUpdate(array('name' => 'Segment 2'));
    $this->segment_3 = Segment::createOrUpdate(array('name' => 'Segment 3'));
  }

  function itCanGetASegment() {
    $router = new Segments();

    $response = $router->get(/* missing id */);
    expect($response)->false();

    $response = $router->get('not_an_id');
    expect($response)->false();

    $response = $router->get($this->segment_1->id());
    expect($response['name'])->equals($this->segment_1->name);
  }

  function itCanGetListingData() {
    $router = new Segments();
    $response = $router->listing();
    expect($response)->hasKey('filters');
    expect($response)->hasKey('groups');

    expect($response['count'])->equals(3);
    expect($response['items'])->count(3);

    expect($response['items'][0]['name'])->equals($this->segment_1->name);
    expect($response['items'][1]['name'])->equals($this->segment_2->name);
    expect($response['items'][2]['name'])->equals($this->segment_3->name);
  }

  function itCanSaveASegment() {
    $segment_data = array(
      'name' => 'New Segment'
    );

    $router = new Segments();
    $response = $router->save(/* missing data */);
    expect($response['result'])->false();
    expect($response['errors'][0])->equals('You need to specify a name.');

    $response = $router->save($segment_data);
    expect($response['result'])->true();

    $segment = Segment::where('name', 'New Segment')->findOne();
    expect($segment->name)->equals($segment_data['name']);
  }

  function itCanRestoreASegment() {
    $this->segment_1->trash();

    $trashed_segment = Segment::findOne($this->segment_1->id());
    expect($trashed_segment->deleted_at)->notNull();

    $router = new Segments();
    $response = $router->restore($this->segment_1->id());
    expect($response)->true();

    $restored_segment = Segment::findOne($this->segment_1->id());
    expect($restored_segment->deleted_at)->null();
  }

  function itCanTrashASegment() {
    $router = new Segments();
    $response = $router->trash($this->segment_2->id());
    expect($response)->true();

    $trashed_segment = Segment::findOne($this->segment_2->id());
    expect($trashed_segment->deleted_at)->notNull();
  }

  function itCanDeleteASegment() {
    $router = new Segments();
    $response = $router->delete($this->segment_3->id());
    expect($response)->equals(1);

    $deleted_segment = Segment::findOne($this->segment_3->id());
    expect($deleted_segment)->false();
  }

  function itCanDuplicateASegment() {
    $router = new Segments();
    $response = $router->duplicate($this->segment_1->id());
    expect($response['name'])->equals('Copy of '.$this->segment_1->name);

    $duplicated_segment = Segment::findOne($response['id']);
    expect($duplicated_segment->name)->equals('Copy of '.$this->segment_1->name);
  }

  function itCanBulkDeleteSegments() {
    expect(Segment::count())->equals(3);

    $segments = Segment::findMany();
    foreach($segments as $segment) {
      $segment->trash();
    }

    $router = new Segments();
    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response)->equals(3);

    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response)->equals(0);
  }

  function _after() {
    Segment::deleteMany();
  }
}