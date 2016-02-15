<?php

use MailPoet\Listing;
use MailPoet\Models\Subscriber;

class ListingCest {
  function _before() {

  }

  function itShouldReturnListingData() {
    $listing = new Listing\Handler(
      '\MailPoet\Models\Subscriber',
      array()
    );

    $result = $listing->get();

    expect(array_key_exists('items', $result))->equals(true);
    expect(array_key_exists('count', $result))->equals(true);
    expect(array_key_exists('filters', $result))->equals(true);
    expect(array_key_exists('groups', $result))->equals(true);
  }

  function itShouldGroup(UnitTester $I) {
    $I->generateSubscribers(1);
    $I->generateSubscribers(2, array('status' => 'unsubscribed'));
    $I->generateSubscribers(3, array('status' => 'subscribed'));

    $listing = new Listing\Handler(
      '\MailPoet\Models\Subscriber',
      array('group' => 'subscribed')
    );
    $result = $listing->get();
    expect($result['groups'])->notEmpty();
    expect($result['count'])->equals(3);
  }

  function itShouldSearch(UnitTester $I) {
    $I->generateSubscriber(array(
      'email' => 'j.d@mailpoet.com'
    ));

    $listing = new Listing\Handler(
      '\MailPoet\Models\Subscriber',
      array(
        'search' => 'j.d'
      )
    );

    $result = $listing->get();
    expect($result['count'])->equals(1);
  }

  function _after() {
    Subscriber::deleteMany();
  }
}
