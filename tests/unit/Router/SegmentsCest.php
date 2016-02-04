<?php
use \MailPoet\Router\Segments;
use \MailPoet\Models\Segment;

class SegmentsCest {
  function _before() {
    Segment::createOrUpdate(array('name' => 'Segment 1'));
    Segment::createOrUpdate(array('name' => 'Segment 2'));
    Segment::createOrUpdate(array('name' => 'Segment 3'));
  }

  function itCanGetASegment() {
    $segment = Segment::where('name', 'Segment 1')->findOne();

    $router = new Segments();

    $response = $router->get(/* missing id */);
    expect($response)->false();

    $response = $router->get('not_an_id');
    expect($response)->false();

    $response = $router->get($segment->id());
    expect($response['name'])->equals($segment->name);
  }

  function itCanGetListingData() {
    $router = new Segments();
    $response = $router->listing();
    expect($response)->hasKey('filters');
    expect($response)->hasKey('groups');

    expect($response['count'])->equals(3);
    expect($response['items'])->count(3);

    expect($response['items'][0]['name'])->equals('Segment 1');
    expect($response['items'][1]['name'])->equals('Segment 2');
    expect($response['items'][2]['name'])->equals('Segment 3');
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
    $segment = Segment::where('name', 'Segment 1')->findOne();
    $segment->trash();

    $trashed_segment = Segment::findOne($segment->id());
    expect($trashed_segment->deleted_at)->notNull();

    $router = new Segments();
    $response = $router->restore($segment->id());
    expect($response)->true();

    $restored_segment = Segment::findOne($segment->id());
    expect($restored_segment->deleted_at)->null();
  }

  function itCanTrashASegment() {
    $segment = Segment::where('name', 'Segment 1')->findOne();
    expect($segment->deleted_at)->null();

    $router = new Segments();
    $response = $router->trash($segment->id());
    expect($response)->true();

    $trashed_segment = Segment::findOne($segment->id());
    expect($trashed_segment->deleted_at)->notNull();
  }

  function itCanDeleteASegment() {
    $segment = Segment::where('name', 'Segment 2')->findOne();
    expect($segment->deleted_at)->null();

    $router = new Segments();
    $response = $router->delete($segment->id());
    expect($response)->equals(1);

    $deleted_segment = Segment::findOne($segment->id());
    expect($deleted_segment)->false();
  }

  function itCanDuplicateASegment() {
    $segment = Segment::where('name', 'Segment 3')->findOne();

    $router = new Segments();
    $response = $router->duplicate($segment->id());
    expect($response['name'])->equals('Copy of '.$segment->name);

    $duplicated_segment = Segment::findOne($response['id']);
    expect($duplicated_segment->name)->equals('Copy of '.$segment->name);
  }

  function _after() {
    ORM::forTable(Segment::$_table)->deleteMany();
  }
}