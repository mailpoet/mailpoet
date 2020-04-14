<?php

namespace MailPoet\Test\Subscribers\ImportExport\Import;

use Codeception\Stub;
use MailPoet\Subscribers\ImportExport\Import\MailChimp;
use MailPoet\WP\Functions as WPFunctions;

class MailChimpTest extends \MailPoetTest {
  /** @var string */
  private $apiKey;

  /** @var MailChimp */
  private $mailchimp;

  /** @var array */
  private $lists;

  public function __construct() {
    parent::__construct();
    $this->apiKey = (string)getenv('WP_TEST_IMPORT_MAILCHIMP_API');
    $this->mailchimp = new MailChimp($this->apiKey);
    $this->lists = explode(",", (string)getenv('WP_TEST_IMPORT_MAILCHIMP_LISTS'));
  }

  public function _before() {
    WPFunctions::set(Stub::make(new WPFunctions, [
      '__' => function ($value) {
        return $value;
      },
    ]));
  }

  public function testItCanGetAPIKey() {
    $validApiKeyFormat = '12345678901234567890123456789012-ab1';
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
    expect($this->mailchimp->getAPIKey($validApiKeyFormat))
      ->equals($validApiKeyFormat);
  }

  public function testItCanGetDatacenter() {
    $validApiKeyFormat = '12345678901234567890123456789012-ab1';
    $dataCenter = 'ab1';
    expect($this->mailchimp->getDataCenter($validApiKeyFormat))
      ->equals($dataCenter);
  }

  public function testItFailsWithIncorrectAPIKey() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();

    try {
      $mailchimp = clone($this->mailchimp);
      $mailchimp->apiKey = false;
      $lists = $mailchimp->getLists();
      $this->fail('MailChimp getLists() did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())->contains('Invalid API Key');
    }
  }

  public function testItCanGetLists() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();
    try {
      $lists = $this->mailchimp->getLists();
    } catch (\Exception $e) {
      $this->fail('MailChimp getLists() threw an exception');
    }
    expect($lists)->count(2);
    expect($lists[0]['id'])->notEmpty();
    expect($lists[0]['name'])->notEmpty();
  }

  public function testItFailsWithIncorrectLists() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();

    try {
      $this->mailchimp->getSubscribers();
      $this->fail('MailChimp getSubscribers() did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())->contains('Did not find any valid lists');
    }

    try {
      $this->mailchimp->getSubscribers([12]);
      $this->fail('MailChimp getSubscribers() did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())->contains('Did not find any valid lists');
    }
  }

  public function testItCanGetSubscribers() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();

    try {
      $subscribers = $this->mailchimp->getSubscribers([$this->lists[0]]);
    } catch (\Exception $e) {
      $this->fail('MailChimp getSubscribers() threw an exception');
    }

    expect($subscribers)->hasKey('invalid');
    expect($subscribers)->hasKey('duplicate');
    expect($subscribers['header'])->notEmpty();
    expect($subscribers['subscribers'])->count(1);
    expect($subscribers['subscribersCount'])->equals(1);
  }

  public function testItFailsWhenSubscribersDataTooLarge() {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();
    $mailchimp = clone($this->mailchimp);
    $mailchimp->maxPostSize = 10;

    try {
      $subscribers = $mailchimp->getSubscribers($this->lists);
      $this->fail('MailChimp getSubscribers() did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())
        ->contains('The information received from MailChimp is too large for processing');
    }
  }

  public function _after() {
    WPFunctions::set(new WPFunctions);
  }
}
