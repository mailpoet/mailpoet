<?php

use MailPoet\Subscribers\ImportExport\Import\MailChimp;

class MailChimpCest {
  function __construct() {
    $this->APIKey = getenv('WP_TEST_IMPORT_MAILCHIMP_API') ?
      getenv('WP_TEST_IMPORT_MAILCHIMP_API') :
      '1234567890';
    $this->mailChimp = new MailChimp($this->APIKey);
    $this->lists = getenv('WP_TEST_IMPORT_MAILCHIMP_LISTS') ?
      explode(",", getenv('WP_TEST_IMPORT_MAILCHIMP_LISTS')) :
      array(
        'one',
        'two'
      );
  }

  function itCanGetAPIKey() {
    expect($this->mailChimp->getAPIKey($this->APIKey))->equals($this->APIKey);
    expect($this->mailChimp->getAPIKey('somekey'))->false();
  }

  function itCanGetDatacenter() {
    expect($this->mailChimp->getDataCenter($this->APIKey))->equals(
      explode('-', $this->APIKey)[1]
    );
  }

  function itFailsWithIncorrectAPIKey() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $mailChimp = clone($this->mailChimp);
    $mailChimp->APIKey = false;
    $lists = $mailChimp->getLists();
    expect($lists['result'])->false();
    expect($lists['error'])->contains('API');
    $subscribers = $mailChimp->getLists();
    expect($subscribers['result'])->false();
    expect($subscribers['error'])->contains('API');
  }

  function itCanGetLists() {
    $lists = $this->mailChimp->getLists();
    expect($lists['result'])->true();
    expect(count($lists['data']))->equals(2);
    expect(isset($lists['data'][0]['id']))->true();
    expect(isset($lists['data'][0]['name']))->true();
  }

  function itFailsWithIncorrectLists() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $subscribers = $this->mailChimp->getSubscribers();
    expect($subscribers['result'])->false();
    expect($subscribers['error'])->contains('lists');
    $subscribers = $this->mailChimp->getSubscribers(array(12));
    expect($subscribers['result'])->false();
    expect($subscribers['error'])->contains('lists');
  }

  function itCanGetSubscribers() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $subscribers = $this->mailChimp->getSubscribers(array($this->lists[0]));
    expect($subscribers['result'])->true();
    expect(isset($subscribers['data']['invalid']))->true();
    expect(isset($subscribers['data']['duplicate']))->true();
    expect(isset($subscribers['data']['header']))->true();
    expect(count($subscribers['data']['subscribers']))->equals(1);
    expect($subscribers['data']['subscribersCount'])->equals(1);
  }

  function itFailsWhenListHeadersDontMatch() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $subscribers = $this->mailChimp->getSubscribers($this->lists);
    expect($subscribers['result'])->false();
    expect($subscribers['error'])->contains('header');
  }

  function itFailsWhenSubscribersDataTooLarge() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $mailChimp = clone($this->mailChimp);
    $mailChimp->maxPostSize = 10;
    $subscribers = $mailChimp->getSubscribers($this->lists);
    expect($subscribers['result'])->false();
    expect($subscribers['error'])->contains('large');
  }
}