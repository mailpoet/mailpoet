<?php

use MailPoet\Listing;

class ListingCest {
  function _before() {

  }

  function itShouldReturnListingData() {
    $listing = new Listing\Handler(
      \Model::factory('\MailPoet\Models\Subscriber'),
      array()
    );

    $result = $listing->get();

    expect(array_key_exists('items', $result))->equals(true);
    expect(array_key_exists('count', $result))->equals(true);
    expect(array_key_exists('filters', $result))->equals(true);
    expect(array_key_exists('groups', $result))->equals(true);
  }

  function itShouldGroup(UnitTester $I) {
    $I->generateSubscribers(10);
    $I->generateSubscribers(20, array('status' => 'unsubscribed'));
    $I->generateSubscribers(30, array('status' => 'subscribed'));

    $listing = new Listing\Handler(
      \Model::factory('\MailPoet\Models\Subscriber'),
      array('group' => 'subscribed')
    );
    $result = $listing->get();

    expect($result['groups'])->notEmpty();
    expect($result['count'])->equals(30);
  }

  function itShouldSearch(UnitTester $I) {
    $I->generateSubscriber(array(
      'email' => 'j.d@mailpoet.com'
    ));

    $listing = new Listing\Handler(
      \Model::factory('\MailPoet\Models\Subscriber'),
      array(
        'search' => 'j.d'
      )
    );

    $result = $listing->get();
    expect($result['count'])->equals(1);
  }

  function itShouldPaginate(UnitTester $I) {

  }

  function _after() {

  }
}
