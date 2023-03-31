<?php declare(strict_types = 1);

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

  public function _before(): void {
    WPFunctions::set(Stub::make(new WPFunctions, [
      '__' => function ($value) {
        return $value;
      },
    ]));
  }

  public function testItCanGetAPIKey(): void {
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

  public function testItCanGetDatacenter(): void {
    $validApiKeyFormat = '12345678901234567890123456789012-ab1';
    $dataCenter = 'ab1';
    expect($this->mailchimp->getDataCenter($validApiKeyFormat))
      ->equals($dataCenter);
  }

  public function testItFailsWithIncorrectAPIKey(): void {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();

    try {
      $mailchimp = clone($this->mailchimp);
      $mailchimp->apiKey = false;
      $lists = $mailchimp->getLists();
      $this->fail('MailChimp getLists() did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())->stringContainsString('Invalid API Key');
    }
  }

  public function testItCanGetLists(): void {
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

  public function testItFailsWithIncorrectLists(): void {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();

    try {
      $this->mailchimp->getSubscribers();
      $this->fail('MailChimp getSubscribers() did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())->stringContainsString('Did not find any valid lists');
    }

    try {
      $this->mailchimp->getSubscribers([12]);
      $this->fail('MailChimp getSubscribers() did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())->stringContainsString('Did not find any valid lists');
    }
  }

  public function testItCanGetSubscribers(): void {
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

  public function testItFailsWhenSubscribersDataTooLarge(): void {
    if (getenv('WP_TEST_ENABLE_NETWORK_TESTS') !== 'true') $this->markTestSkipped();
    $mailchimp = clone($this->mailchimp);
    $mailchimp->maxPostSize = 10;

    try {
      $subscribers = $mailchimp->getSubscribers($this->lists);
      $this->fail('MailChimp getSubscribers() did not throw an exception');
    } catch (\Exception $e) {
      expect($e->getMessage())
        ->stringContainsString('The information received from MailChimp is too large for processing');
    }
  }

  public function testItDoesntAllowInconvenientSubscribers(): void {
    $unsubscribed = [
      'email_address' => 'test@user.com',
      'member_rating' => 2,
      'status' => 'unsubscribed',
      'stats' => [
        'avg_open_rate' => 0.1,
        'avg_click_rate' => 0.05,
      ],
    ];
    expect($this->mailchimp->isSubscriberAllowed($unsubscribed))->false();

    $badRate = [
      'email_address' => 'test@user.com',
      'member_rating' => 2,
      'status' => 'unsubscribed',
      'stats' => [
        'avg_open_rate' => 0.1,
        'avg_click_rate' => 0.002,
      ],
    ];
    expect($this->mailchimp->isSubscriberAllowed($badRate))->false();

    $badRating = [
      'email_address' => 'test@user.com',
      'member_rating' => 1,
      'status' => 'unsubscribed',
      'stats' => [
        'avg_open_rate' => 0.1,
        'avg_click_rate' => 0.1,
      ],
    ];
    expect($this->mailchimp->isSubscriberAllowed($badRating))->false();
  }

  public function testItAllowsConvenientSubscribers(): void {
    $subscribed = [
      'email_address' => 'test@user.com',
      'member_rating' => 2,
      'status' => 'subscribed',
      'stats' => [
        'avg_open_rate' => 0.1,
        'avg_click_rate' => 0.1,
      ],
    ];
    expect($this->mailchimp->isSubscriberAllowed($subscribed))->true();
  }

  public function _after(): void {
    parent::_after();
    WPFunctions::set(new WPFunctions);
  }
}
