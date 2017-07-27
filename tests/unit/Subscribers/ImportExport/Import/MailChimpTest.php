<?php
namespace MailPoet\Test\Subscribers\ImportExport\Import;

use MailPoet\Subscribers\ImportExport\Import\MailChimp;

class MailChimpTest extends \MailPoetTest {
  function __construct() {
    parent::__construct();
    $this->api_key = getenv('WP_TEST_IMPORT_MAILCHIMP_API');
    $this->mailchimp = new MailChimp($this->api_key);
    $this->lists = explode(",", getenv('WP_TEST_IMPORT_MAILCHIMP_LISTS'));
  }

  function testItCanGetAPIKey() {
    $valid_api_key_format = '12345678901234567890123456789012-ab1';
    // key must consist of two parts separated by hyphen
    expect($this->mailchimp->getAPIKey('invalid_api_key_format'))->false();
    // key must only contain numerals and letters
    expect($this->mailchimp->getAPIKey('12345678901234567890123456789012-@?1'))->false();
    // the first part of the key must contain 32 characters,
    expect($this->mailchimp->getAPIKey('1234567890123456789012345678901-123'))
      ->false();
    // the second part must contain 2-4 characters
    expect($this->mailchimp->getAPIKey('12345678901234567890123456789012-12345'))
      ->false();
    expect($this->mailchimp->getAPIKey('12345678901234567890123456789012-1'))
      ->false();
    expect($this->mailchimp->getAPIKey($valid_api_key_format))
      ->equals($valid_api_key_format);
  }

  function testItCanGetDatacenter() {
    $valid_api_key_format = '12345678901234567890123456789012-ab1';
    $data_center = 'ab1';
    expect($this->mailchimp->getDataCenter($valid_api_key_format))
      ->equals($data_center);
  }

  function testItFailsWithIncorrectAPIKey() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;

    try {
      $mailchimp = clone($this->mailchimp);
      $mailchimp->api_key = false;
      $lists = $mailchimp->getLists();
      $this->fail('MailChimp getLists() did not throw an exception');
    } catch(\Exception $e) {
      expect($e->getMessage())->contains('Invalid API Key');
    }
  }

  function testItCanGetLists() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    try {
      $lists = $this->mailchimp->getLists();
    } catch(\Exception $e) {
      $this->fail('MailChimp getLists() threw an exception');
    }
    expect($lists)->count(2);
    expect($lists[0]['id'])->notEmpty();
    expect($lists[0]['name'])->notEmpty();
  }

  function testItFailsWithIncorrectLists() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;

    try {
      $subscribers = $this->mailchimp->getSubscribers();
      $this->fail('MailChimp getSubscribers() did not throw an exception');
    } catch(\Exception $e) {
      expect($e->getMessage())->contains('Did not find any valid lists');
    }

    try {
      $subscribers = $this->mailchimp->getSubscribers(array(12));
      $this->fail('MailChimp getSubscribers() did not throw an exception');
    } catch(\Exception $e) {
      expect($e->getMessage())->contains('Did not find any valid lists');
    }
  }

  function testItCanGetSubscribers() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;

    try {
      $subscribers = $this->mailchimp->getSubscribers(array($this->lists[0]));
    } catch(\Exception $e) {
      $this->fail('MailChimp getSubscribers() threw an exception');
    }

    expect($subscribers)->hasKey('invalid');
    expect($subscribers)->hasKey('duplicate');
    expect($subscribers['header'])->notEmpty();
    expect($subscribers['subscribers'])->count(1);
    expect($subscribers['subscribersCount'])->equals(1);
  }

  function testItFailsWhenListHeadersDontMatch() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;

    try {
      $subscribers = $this->mailchimp->getSubscribers($this->lists);
      $this->fail('MailChimp getSubscribers() did not throw an exception');
    } catch(\Exception $e) {
      expect($e->getMessage())
        ->contains('The selected lists do not have matching columns (headers)');
    }
  }

  function testItFailsWhenSubscribersDataTooLarge() {
    if(getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') return;
    $mailchimp = clone($this->mailchimp);
    $mailchimp->max_post_size = 10;

    try {
      $subscribers = $mailchimp->getSubscribers($this->lists);
      $this->fail('MailChimp getSubscribers() did not throw an exception');
    } catch(\Exception $e) {
      expect($e->getMessage())
        ->contains('The information received from MailChimp is too large for processing');
    }
  }
}