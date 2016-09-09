<?php
use \MailPoet\API\Response as APIResponse;
use \MailPoet\API\Error as APIError;
use \MailPoet\API\Endpoints\Newsletters;
use \MailPoet\Models\Newsletter;
use \MailPoet\Models\NewsletterSegment;
use \MailPoet\Models\NewsletterTemplate;
use \MailPoet\Models\Segment;

class NewslettersTest extends MailPoetTest {
  function _before() {
    $this->newsletter = Newsletter::createOrUpdate(array(
      'subject' => 'My Standard Newsletter',
      'type' => Newsletter::TYPE_STANDARD
    ));

    $this->post_notification = Newsletter::createOrUpdate(array(
      'subject' => 'My Post Notification',
      'type' => Newsletter::TYPE_NOTIFICATION
    ));
  }

  function testItCanGetANewsletter() {
    $router = new Newsletters();

    $response = $router->get(/* missing id */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])
      ->equals('This newsletter does not exist.');

    $response = $router->get(array('id' => 'not_an_id'));
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])
      ->equals('This newsletter does not exist.');

    $response = $router->get(array('id' => $this->newsletter->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::findOne($this->newsletter->id)
        ->withSegments()
        ->withOptions()
        ->asArray()
    );
  }

  function testItCanSaveANewNewsletter() {
    $valid_data = array(
      'subject' => 'My First Newsletter',
      'type' => Newsletter::TYPE_STANDARD
    );

    $router = new Newsletters();
    $response = $router->save($valid_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::findOne($response->data['id'])->asArray()
    );

    $invalid_data = array(
      'subject' => 'Missing newsletter type'
    );

    $response = $router->save($invalid_data);
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a type');
  }

  function testItCanSaveAnExistingNewsletter() {
    $router = new Newsletters();
    $newsletter_data = array(
      'id' => $this->newsletter->id,
      'subject' => 'My Updated Newsletter'
    );

    $response = $router->save($newsletter_data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::findOne($this->newsletter->id)->asArray()
    );

    $updated_newsletter = Newsletter::findOne($this->newsletter->id);
    expect($updated_newsletter->subject)->equals('My Updated Newsletter');
  }

  function testItCanSetANewsletterStatus() {
    $router = new Newsletters();
    // set status to sending
    $response = $router->setStatus(array(
      'id' => $this->newsletter->id,
      'status' => Newsletter::STATUS_SENDING
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['status'])->equals(Newsletter::STATUS_SENDING);

    // set status to draft
    $response = $router->setStatus(array(
      'id' => $this->newsletter->id,
      'status' => Newsletter::STATUS_DRAFT
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['status'])->equals(Newsletter::STATUS_DRAFT);

    // no status specified throws an error
    $response = $router->setStatus(array(
      'id' => $this->newsletter->id,
    ));
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])
      ->equals('You need to specify a status.');

    // invalid newsletter id throws an error
    $response = $router->setStatus(array(
      'status' => Newsletter::STATUS_DRAFT
    ));
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])
      ->equals('This newsletter does not exist.');
  }

  function testItCanRestoreANewsletter() {
    $this->newsletter->trash();

    $trashed_newsletter = Newsletter::findOne($this->newsletter->id);
    expect($trashed_newsletter->deleted_at)->notNull();

    $router = new Newsletters();
    $response = $router->restore(array('id' => $this->newsletter->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::findOne($this->newsletter->id)->asArray()
    );
    expect($response->data['deleted_at'])->null();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanTrashANewsletter() {
    $router = new Newsletters();
    $response = $router->trash(array('id' => $this->newsletter->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::findOne($this->newsletter->id)->asArray()
    );
    expect($response->data['deleted_at'])->notNull();
    expect($response->meta['count'])->equals(1);
  }

  function testItCanDeleteANewsletter() {
    $router = new Newsletters();
    $response = $router->delete(array('id' => $this->newsletter->id));
    expect($response->data)->isEmpty();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(1);
  }

  function testItCanDuplicateANewsletter() {
    $router = new Newsletters();
    $response = $router->duplicate(array('id' => $this->newsletter->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::where('subject', 'Copy of My Standard Newsletter')
        ->findOne()
        ->asArray()
    );
    expect($response->meta['count'])->equals(1);

    $response = $router->duplicate(array('id' => $this->post_notification->id));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::where('subject', 'Copy of My Post Notification')
        ->findOne()
        ->asArray()
    );
    expect($response->meta['count'])->equals(1);
  }

  function testItCanCreateANewsletter() {
    $data = array(
      'subject' => 'My New Newsletter',
      'type' => Newsletter::TYPE_STANDARD
    );
    $router = new Newsletters();
    $response = $router->create($data);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->equals(
      Newsletter::where('subject', 'My New Newsletter')
        ->findOne()
        ->asArray()
    );

    $response = $router->create();
    expect($response->status)->equals(APIResponse::STATUS_BAD_REQUEST);
    expect($response->errors[0]['message'])->equals('Please specify a type');
  }

  function testItCanGetListingData() {
    $segment_1 = Segment::createOrUpdate(array('name' => 'Segment 1'));
    $segment_2 = Segment::createOrUpdate(array('name' => 'Segment 2'));

    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(array(
      'newsletter_id' => $this->newsletter->id,
      'segment_id' => $segment_1->id
    ));
    $newsletter_segment->save();

    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(array(
      'newsletter_id' => $this->newsletter->id,
      'segment_id' => $segment_2->id
    ));
    $newsletter_segment->save();

    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(array(
      'newsletter_id' => $this->post_notification->id,
      'segment_id' => $segment_2->id
    ));
    $newsletter_segment->save();

    $router = new Newsletters();
    $response = $router->listing();

    expect($response)->hasKey('filters');
    expect($response)->hasKey('groups');

    expect($response['count'])->equals(2);
    expect($response['items'])->count(2);

    expect($response['items'][0]['subject'])->equals('My Standard Newsletter');
    expect($response['items'][1]['subject'])->equals('My Post Notification');

    // 1st subscriber has 2 segments
    expect($response['items'][0]['segments'])->count(2);
    expect($response['items'][0]['segments'][0]['id'])
      ->equals($segment_1->id);
    expect($response['items'][0]['segments'][1]['id'])
      ->equals($segment_2->id);

    // 2nd subscriber has 1 segment
    expect($response['items'][1]['segments'])->count(1);
    expect($response['items'][1]['segments'][0]['id'])
      ->equals($segment_2->id);
  }

  function testItCanFilterListing() {
    // create 2 segments
    $segment_1 = Segment::createOrUpdate(array('name' => 'Segment 1'));
    $segment_2 = Segment::createOrUpdate(array('name' => 'Segment 2'));

    // link standard newsletter to the 2 segments
    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(array(
      'newsletter_id' => $this->newsletter->id,
      'segment_id' => $segment_1->id
    ));
    $newsletter_segment->save();

    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(array(
      'newsletter_id' => $this->newsletter->id,
      'segment_id' => $segment_2->id
    ));
    $newsletter_segment->save();

    // link post notification to the 2nd segment
    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(array(
      'newsletter_id' => $this->post_notification->id,
      'segment_id' => $segment_2->id
    ));
    $newsletter_segment->save();

    $router = new Newsletters();

    // filter by 1st segment
    $response = $router->listing(array(
      'filter' => array(
        'segment' => $segment_1->id
      )
    ));

    // we should only get the standard newsletter
    expect($response['count'])->equals(1);
    expect($response['items'][0]['subject'])->equals($this->newsletter->subject);

    // filter by 2nd segment
    $response = $router->listing(array(
      'filter' => array(
        'segment' => $segment_2->id
      )
    ));

    // we should have the 2 newsletters
    expect($response['count'])->equals(2);
  }

  function testItCanLimitListing() {
    $router = new Newsletters();
    // get 1st page (limit items per page to 1)
    $response = $router->listing(array(
      'limit' => 1,
      'sort_by' => 'subject',
      'sort_order' => 'asc'
    ));

    expect($response['count'])->equals(2);
    expect($response['items'])->count(1);
    expect($response['items'][0]['subject'])->equals(
      $this->post_notification->subject
    );

    // get 1st page (limit items per page to 1)
    $response = $router->listing(array(
      'limit' => 1,
      'offset' => 1,
      'sort_by' => 'subject',
      'sort_order' => 'asc'
    ));

    expect($response['count'])->equals(2);
    expect($response['items'])->count(1);
    expect($response['items'][0]['subject'])->equals(
      $this->newsletter->subject
    );
  }

  function testItCanBulkDeleteSelectionOfNewsletters() {
    $selection_ids = array(
      $this->newsletter->id,
      $this->post_notification->id
    );

    $router = new Newsletters();
    $response = $router->bulkAction(array(
      'listing' => array(
        'selection' => $selection_ids
      ),
      'action' => 'delete'
    ));

    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(count($selection_ids));
  }

  function testItCanBulkDeleteNewsletters() {
    $router = new Newsletters();
    $response = $router->bulkAction(array(
      'action' => 'trash',
      'listing' => array('group' => 'all')
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(2);

    $router = new Newsletters();
    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(2);

    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->meta['count'])->equals(0);
  }

  function _after() {
    Newsletter::deleteMany();
    NewsletterSegment::deleteMany();
    Segment::deleteMany();
  }
}
