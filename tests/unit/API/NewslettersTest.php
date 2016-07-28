<?php
use \MailPoet\API
    \Newsletters;
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

    $response = $router->get($this->newsletter->id());
    expect($response['id'])->equals($this->newsletter->id());

    $response = $router->get('not_an_id');
    expect($response)->false();

    $response = $router->get(/* missing argument */);
    expect($response)->false();
  }

  function testItCanSaveANewNewsletter() {
    $valid_data = array(
      'subject' => 'My First Newsletter',
      'type' => Newsletter::TYPE_STANDARD
    );

    $router = new Newsletters();
    $response = $router->save($valid_data);
    expect($response['result'])->true();
    expect($response)->hasntKey('errors');

    $invalid_data = array(
      'subject' => 'Missing newsletter type'
    );

    $response = $router->save($invalid_data);
    expect($response['result'])->false();
    expect($response['errors'][0])->equals('Please specify a type');
  }

  function testItCanSaveAnExistingNewsletter() {
    $router = new Newsletters();
    $newsletter_data = array(
      'id' => $this->newsletter->id,
      'subject' => 'My Updated Newsletter'
    );

    $response = $router->save($newsletter_data);
    expect($response['result'])->true();

    $updated_newsletter = Newsletter::findOne($this->newsletter->id());
    expect($updated_newsletter->subject)->equals('My Updated Newsletter');
  }

  function testItCanRestoreANewsletter() {
    $this->newsletter->trash();

    expect($this->newsletter->deleted_at)->notNull();

    $router = new Newsletters();
    $router->restore($this->newsletter->id());

    $restored_subscriber = Newsletter::findOne($this->newsletter->id());
    expect($restored_subscriber->deleted_at)->null();
  }

  function testItCanTrashANewsletter() {
    $router = new Newsletters();
    $response = $router->trash($this->newsletter->id());
    expect($response)->true();

    $trashed_subscriber = Newsletter::findOne($this->newsletter->id());
    expect($trashed_subscriber->deleted_at)->notNull();
  }

  function testItCanDeleteANewsletter() {
    $router = new Newsletters();
    $response = $router->delete($this->newsletter->id());
    expect($response)->equals(1);

    expect(Newsletter::findOne($this->newsletter->id()))->false();
  }

  function testItCanDuplicateANewsletter() {
    $router = new Newsletters();
    $response = $router->duplicate($this->newsletter->id());
    expect($response['subject'])->equals('Copy of My Standard Newsletter');
    expect($response['type'])->equals(Newsletter::TYPE_STANDARD);
    expect($response['body'])->equals($this->newsletter->body);

    $response = $router->duplicate($this->post_notification->id());
    expect($response['subject'])->equals('Copy of My Post Notification');
    expect($response['type'])->equals(Newsletter::TYPE_NOTIFICATION);
    expect($response['body'])->equals($this->post_notification->body);
  }

  function testItCanCreateANewsletter() {
    $data = array(
      'subject' => 'My New Newsletter',
      'type' => Newsletter::TYPE_STANDARD
    );
    $router = new Newsletters();
    $response = $router->create($data);
    expect($response['result'])->true();
    expect($response['newsletter']['id'] > 0)->true();
    expect($response['newsletter']['subject'])->equals('My New Newsletter');
    expect($response['newsletter']['type'])->equals(Newsletter::TYPE_STANDARD);
    expect($response['newsletter']['body'])->equals(array());
    expect($response)->hasntKey('errors');

    $response = $router->create();
    expect($response['result'])->false();
    expect($response['errors'][0])->equals('Please specify a type');
  }

  function testItCanGetListingData() {
    $segment_1 = Segment::createOrUpdate(array('name' => 'Segment 1'));
    $segment_2 = Segment::createOrUpdate(array('name' => 'Segment 2'));

    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(array(
      'newsletter_id' => $this->newsletter->id(),
      'segment_id' => $segment_1->id()
    ));
    $newsletter_segment->save();

    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(array(
      'newsletter_id' => $this->newsletter->id(),
      'segment_id' => $segment_2->id()
    ));
    $newsletter_segment->save();

    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(array(
      'newsletter_id' => $this->post_notification->id(),
      'segment_id' => $segment_2->id()
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
      'newsletter_id' => $this->newsletter->id(),
      'segment_id' => $segment_1->id()
    ));
    $newsletter_segment->save();

    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(array(
      'newsletter_id' => $this->newsletter->id(),
      'segment_id' => $segment_2->id()
    ));
    $newsletter_segment->save();

    // link post notification to the 2nd segment
    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(array(
      'newsletter_id' => $this->post_notification->id(),
      'segment_id' => $segment_2->id()
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
    $newsletter = Newsletter::createOrUpdate(array(
      'subject' => 'To be deleted',
      'type' => Newsletter::TYPE_STANDARD
    ));

    $selection_ids = array(
      $newsletter->id,
      $this->newsletter->id
    );

    $router = new Newsletters();
    $response = $router->bulkAction(array(
      'listing' => array(
        'selection' => $selection_ids
      ),
      'action' => 'delete'
    ));

    expect($response)->equals(count($selection_ids));
  }

  function testItCanBulkDeleteNewsletters() {
    expect(Newsletter::count())->equals(2);

    $newsletters = Newsletter::findMany();
    foreach($newsletters as $newsletter) {
      $newsletter->trash();
    }

    $router = new Newsletters();
    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response)->equals(2);

    $response = $router->bulkAction(array(
      'action' => 'delete',
      'listing' => array('group' => 'trash')
    ));
    expect($response)->equals(0);
  }

  function _after() {
    Newsletter::deleteMany();
    NewsletterSegment::deleteMany();
    Segment::deleteMany();
  }
}
