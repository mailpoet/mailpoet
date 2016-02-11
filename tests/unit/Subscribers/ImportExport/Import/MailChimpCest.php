<?php

use MailPoet\Subscribers\ImportExport\Import\MailChimp;

class MailChimpCest {
  function __construct() {
    $this->api_key = getenv('WP_TEST_IMPORT_MAILCHIMP_API');
    $this->mailchimp = new MailChimp($this->api_key);
    $this->lists = explode(",", getenv('WP_TEST_IMPORT_MAILCHIMP_LISTS'));
  }

  function itValidatesAPIKey() {
    $valid_api_key_format = '12345678901234567890123456789012-ab1';
    expect($this->mailchimp->getAPIKey($valid_api_key_format))
      ->equals($valid_api_key_format);
    expect($this->mailchimp->getAPIKey('invalid_api_key_format'))->false();
  }

  function itCanGetDatacenter() {
    $valid_api_key_format = '12345678901234567890123456789012-ab1';
    $data_center = 'ab1';
    expect($this->mailchimp->getDataCenter($valid_api_key_format))
      ->equals($data_center);
  }

  function itFailsWithIncorrectAPIKey() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $mailchimp = clone($this->mailchimp);
    $mailchimp->api_key = false;
    $lists = $mailchimp->getLists();
    expect($lists['result'])->false();
    expect($lists['errors'][0])->contains('API');
    $subscribers = $mailchimp->getLists();
    expect($subscribers['result'])->false();
    expect($subscribers['errors'][0])->contains('API');
  }

  function itCanGetLists() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $lists = $this->mailchimp->getLists();
    expect($lists['result'])->true();
    expect(count($lists['data']))->equals(2);
    expect(isset($lists['data'][0]['id']))->true();
    expect(isset($lists['data'][0]['name']))->true();
  }

  function itFailsWithIncorrectLists() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $subscribers = $this->mailchimp->getSubscribers();
    expect($subscribers['result'])->false();
    expect($subscribers['errors'][0])->contains('lists');
    $subscribers = $this->mailchimp->getSubscribers(array(12));
    expect($subscribers['result'])->false();
    expect($subscribers['errors'][0])->contains('lists');
  }

  function itCanGetSubscribers() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $subscribers = $this->mailchimp->getSubscribers(array($this->lists[0]));
    expect($subscribers['result'])->true();
    expect(isset($subscribers['data']['invalid']))->true();
    expect(isset($subscribers['data']['duplicate']))->true();
    expect(isset($subscribers['data']['header']))->true();
    expect(count($subscribers['data']['subscribers']))->equals(1);
    expect($subscribers['data']['subscribersCount'])->equals(1);
  }

  function itFailsWhenListHeadersDontMatch() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $subscribers = $this->mailchimp->getSubscribers($this->lists);
    expect($subscribers['result'])->false();
    expect($subscribers['errors'][0])->contains('header');
  }

  function itFailsWhenSubscribersDataTooLarge() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $mailchimp = clone($this->mailchimp);
    $mailchimp->max_post_size = 10;
    $subscribers = $mailchimp->getSubscribers($this->lists);
    expect($subscribers['result'])->false();
    expect($subscribers['errors'][0])->contains('large');
  }
}