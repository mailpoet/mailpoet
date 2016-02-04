<?php
use \MailPoet\Router\Newsletters;
use \MailPoet\Models\Newsletter;
use \MailPoet\Models\NewsletterSegment;
use \MailPoet\Models\NewsletterTemplate;
use \MailPoet\Models\Segment;

class NewslettersCest {
  function _before() {
  }

  function itCanGetANewsletter() {
    $newsletter = Newsletter::createOrUpdate(array(
      'subject' => 'My First Newsletter',
      'type' => 'standard'
    ));
    expect($newsletter->id() > 0)->true();

    $router = new Newsletters();

    $response = $router->get($newsletter->id());

    expect($response['id'])->equals($newsletter->id());

    $response = $router->get('not_an_id');
    expect($response)->false();

    $response = $router->get(/* missing argument */);
    expect($response)->false();
  }

  function itCanSaveANewNewsletter() {
    $valid_data = array(
      'subject' => 'My First Newsletter',
      'type' => 'standard'
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
    expect($response['errors'][0])->equals('You need to specify a type.');
  }

  function itCanSaveAnExistingNewsletter() {
    $newsletter = Newsletter::createOrUpdate(array(
      'subject' => 'My First Newsletter',
      'type' => 'standard'
    ));
    expect($newsletter->id() > 0)->true();

    $router = new Newsletters();
    $newsletter_data = $newsletter->asArray();
    $newsletter_data['subject'] = 'My Updated Newsletter';

    $response = $router->save($newsletter_data);
    expect($response['result'])->true();

    $updated_newsletter = Newsletter::findOne($newsletter->id());
    expect($updated_newsletter->subject)->equals('My Updated Newsletter');
  }

  function itCanRestoreANewsletter() {
    $newsletter = Newsletter::createOrUpdate(array(
      'subject' => 'My First Newsletter',
      'type' => 'standard'
    ));
    expect($newsletter->id() > 0)->true();

    $newsletter->trash();

    expect($newsletter->deleted_at)->notNull();

    $router = new Newsletters();
    $router->restore($newsletter->id());

    $restored_subscriber = Newsletter::findOne($newsletter->id());
    expect($restored_subscriber->deleted_at)->null();
  }

  function itCanTrashANewsletter() {
    $newsletter = Newsletter::createOrUpdate(array(
      'subject' => 'My First Newsletter',
      'type' => 'standard'
    ));
    expect($newsletter->id() > 0)->true();

    $router = new Newsletters();
    $response = $router->trash($newsletter->id());
    expect($response)->true();

    $trashed_subscriber = Newsletter::findOne($newsletter->id());
    expect($trashed_subscriber->deleted_at)->notNull();
  }

  function itCanDeleteANewsletter() {
    $newsletter = Newsletter::createOrUpdate(array(
      'subject' => 'My First Newsletter',
      'type' => 'standard'
    ));
    expect($newsletter->id() > 0)->true();

    $router = new Newsletters();
    $response = $router->delete($newsletter->id());
    expect($response)->equals(1);

    expect(Newsletter::findOne($newsletter->id()))->false();
  }

  function itCanDuplicateANewsletter() {
    $newsletter = Newsletter::createOrUpdate(array(
      'subject' => 'My First Newsletter',
      'type' => 'standard'
    ));
    expect($newsletter->id() > 0)->true();

    $router = new Newsletters();
    $response = $router->duplicate($newsletter->id());
    expect($response['subject'])->equals('Copy of My First Newsletter');
    expect($response['type'])->equals('standard');
    expect($response['body'])->equals($newsletter->body);
  }

  function itCanCreateANewsletter() {
    $data = array(
      'subject' => 'My New Newsletter',
      'type' => 'standard'
    );
    $router = new Newsletters();
    $response = $router->create($data);
    expect($response['result'])->true();
    expect($response['newsletter']['id'] > 0)->true();
    expect($response['newsletter']['subject'])->equals('My New Newsletter');
    expect($response['newsletter']['type'])->equals('standard');
    expect($response['newsletter']['body'])->equals(array());
    expect($response)->hasntKey('errors');

    $response = $router->create();
    expect($response['result'])->false();
    expect($response['errors'][0])->equals('You need to specify a type.');
  }

  function itCanGetListingData() {
    $segment_1 = Segment::createOrUpdate(array('name' => 'Segment 1'));
    $segment_2 = Segment::createOrUpdate(array('name' => 'Segment 2'));

    $newsletter_1 = Newsletter::createOrUpdate(array(
      'subject' => 'My First Newsletter',
      'type' => 'standard'
    ));
    $newsletter_2 = Newsletter::createOrUpdate(array(
      'subject' => 'My Second Newsletter',
      'type' => 'standard'
    ));

    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(array(
      'newsletter_id' => $newsletter_1->id(),
      'segment_id' => $segment_1->id()
    ));
    $newsletter_segment->save();

    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(array(
      'newsletter_id' => $newsletter_1->id(),
      'segment_id' => $segment_2->id()
    ));
    $newsletter_segment->save();

    $newsletter_segment = NewsletterSegment::create();
    $newsletter_segment->hydrate(array(
      'newsletter_id' => $newsletter_2->id(),
      'segment_id' => $segment_2->id()
    ));
    $newsletter_segment->save();

    $router = new Newsletters();
    $response = $router->listing(array('sort'));

    expect($response)->hasKey('filters');
    expect($response)->hasKey('groups');

    expect($response['count'])->equals(2);
    expect($response['items'])->count(2);

    expect($response['items'][0]['subject'])->equals('My First Newsletter');
    expect($response['items'][1]['subject'])->equals('My Second Newsletter');
    expect($response['items'][0]['segments'])->equals(array(
      $segment_1->id(),
      $segment_2->id()
    ));
    expect($response['items'][1]['segments'])->equals(array(
      $segment_2->id()
    ));
  }

  function _after() {
    Newsletter::deleteMany();
    Segment::deleteMany();
  }
}