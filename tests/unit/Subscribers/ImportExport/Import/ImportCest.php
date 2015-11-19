<?php

use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberCustomField;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\ImportExport\Import\Import;
use MailPoet\Util\Helpers;

class ImportCest {
  function __construct() {
    $this->JSONdata = json_decode(file_get_contents(dirname(__FILE__) . '/ImportTestData.json'), true);
    $this->subscribersData = array(
      'first_name' => array(
        'Adam',
        'Mary'
      ),
      'last_name' => array(
        'Smith',
        'Jane'
      ),
      'email' => array(
        'adam@smith.com',
        'mary@jane.com'
      ),
      777 => array(
        'France',
        'Brazil'
      )
    );
    $this->subscriberFields = array(
      'first_name',
      'last_name',
      'email'
    );
    $this->segments = range(0, 1);
    $this->subscriberCustomFields = array(777);
    $this->import = new Import($this->JSONdata);
  }

  function itCanConstruct() {
    expect($this->import->subscribersData)->equals($this->JSONdata['subscribers']);
    expect($this->import->segments)->equals($this->JSONdata['segments']);
    expect(is_array($this->import->subscriberFields))->true();
    expect(is_array($this->import->subscriberCustomFields))->true();
    expect($this->import->subscribersCount)->equals(
      count($this->JSONdata['subscribers']['email'])
    );
    expect(
      preg_match(
        '/\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}/',
        $this->import->currentTime)
    )->equals(1);
  }

  function itCanFilterExistingAndNewSubscribers() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(
      array(
        'first_name' => 'Adam',
        'last_name' => 'Smith',
        'email' => 'adam@smith.com'
      ));
    $subscriber->save();
    list($existing, $new) = $this->import->filterExistingAndNewSubscribers(
      $this->subscribersData
    );
    expect($existing['email'][0])->equals($this->subscribersData['email'][0]);
    expect($new['email'][0])->equals($this->subscribersData['email'][1]);
  }

  function itCanExtendSubscribersAndFields() {
    expect(in_array('created_at', $this->import->subscriberFields))->false();
    expect(isset($this->import->subscriberFields['created_at']))->false();
    list($subscribers, $fields) = $this->import->extendSubscribersAndFields(
      $this->import->subscribersData,
      $this->import->subscriberFields
    );
    expect(in_array('created_at', $fields))->true();
    expect(isset($this->import->subscriberFields['created_at']))->false();
    expect(count($subscribers['created_at']))
      ->equals($this->import->subscribersCount);
  }

  function itCanGetSubscriberFields() {
    $data = array(
      'one',
      'two',
      39
    );
    $fields = $this->import->getSubscriberFields($data);
    expect($fields)->equals(
      array(
        'one',
        'two'
      ));
  }

  function itCanGetCustomSubscriberFields() {
    $data = array(
      'one',
      'two',
      39
    );
    $fields = $this->import->getCustomSubscriberFields($data);
    expect($fields)->equals(array(39));
  }

  function itCanFilterSubscriberState() {
    $data = array(
      'status' => array(
        //subscribed
        'subscribed',
        'confirmed',
        1,
        '1',
        'true',
        //unconfirmed
        'unconfirmed',
        0,
        "0",
        //unsubscribed
        'unsubscribed',
        -1,
        '-1',
        'false'
      ),
    );
    $statuses = $this->import->filterSubscriberStatus($data);
    expect($statuses)->equals(
      array(
        'status' => array(
          'subscribed',
          'subscribed',
          'subscribed',
          'subscribed',
          'subscribed',
          'unconfirmed',
          'unconfirmed',
          'unconfirmed',
          'unsubscribed',
          'unsubscribed',
          'unsubscribed',
          'unsubscribed'
        )
      )
    );
  }

  function itCanAddOrUpdateSubscribers() {
    $subscribersData = $this->subscribersData;
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribersData,
      $this->subscriberFields,
      false
    );
    $subscribers = Subscriber::findArray();
    expect(count($subscribers))->equals(2);
    expect($subscribers[0]['email'])
      ->equals($subscribersData['email'][0]);
    $data['first_name'][1] = 'MaryJane';
    $this->import->createOrUpdateSubscribers(
      'update',
      $subscribersData,
      $this->subscriberFields,
      false
    );
    $subscribers = Subscriber::findArray();
    expect($subscribers[1]['first_name'])
      ->equals($subscribersData['first_name'][1]);
  }

  function itCanCreateOrUpdateCustomFields() {
    $subscribersData = $this->subscribersData;
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribersData,
      $this->subscriberFields,
      false
    );
    $dbSubscribers = Helpers::arrayColumn(
      Subscriber::selectMany(
        array(
          'id',
          'email'
        ))
        ->findArray(),
      'email', 'id'
    );
    $this->import->createOrUpdateCustomFields(
      'create',
      $dbSubscribers,
      $subscribersData,
      $this->subscriberCustomFields
    );
    $subscriberCustomFields = SubscriberCustomField::findArray();
    expect(count($subscriberCustomFields))->equals(2);
    expect($subscriberCustomFields[0]['value'])
      ->equals($subscribersData[777][0]);
    $subscribersData[777][1] = 'Rio';
    $this->import->createOrUpdateCustomFields(
      'update',
      $dbSubscribers,
      $subscribersData,
      $this->subscriberCustomFields
    );
    $subscriberCustomFields = SubscriberCustomField::findArray();
    expect($subscriberCustomFields[1]['value'])
      ->equals($subscribersData[777][1]);
  }

  function itCanaddSubscribersToSegments() {
    $subscribersData = $this->subscribersData;
    $this->import->createOrUpdateSubscribers(
      'create',
      $subscribersData,
      $this->subscriberFields,
      false
    );
    $dbSubscribers = Helpers::arrayColumn(
      Subscriber::select('id')
        ->findArray(),
      'id'
    );
    $this->import->addSubscribersToSegments(
      $dbSubscribers,
      $this->segments
    );
    $subscribersSegments = SubscriberSegment::findArray();
    // 2 subscribers * 2 segments
    expect(count($subscribersSegments))->equals(4);
  }

  function itCanProcess() {
    $import = clone($this->import);
    $result = $import->process();
    expect($result['data']['created'])->equals(997);
    expect($result['data']['updated'])->equals(0);
    $result = $import->process();
    expect($result['data']['created'])->equals(0);
    expect($result['data']['updated'])->equals(997);
    Subscriber::where('email', 'mbanks4@blinklist.com')
      ->findOne()
      ->delete();
    $import->currentTime = date('Y-m-d 12:i:s');
    $result = $import->process();
    expect($result['data']['created'])->equals(1);
    expect($result['data']['updated'])->equals(996);
    $import->updateSubscribers = false;
    $result = $import->process();
    expect($result['data']['created'])->equals(0);
    expect($result['data']['updated'])->equals(0);
  }

  function _after() {
    ORM::forTable(Subscriber::$_table)
      ->deleteMany();
    ORM::forTable(SubscriberCustomField::$_table)
      ->deleteMany();
    ORM::forTable(SubscriberSegment::$_table)
      ->deleteMany();
  }
}