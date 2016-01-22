<?php

use MailPoet\Subscribers\ImportExport\Import\MailChimp;

class MailChimpCest {
  function __construct() {
    $this->APIKey = 'd91ae3861c4829c40bd469e40d6c0e7e-us6';
    $this->mailChimp = new MailChimp($this->APIKey);
    $this->lists = array(
      'edf74586e9',
      '8b66f7fac8'
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
    $subscribers = $this->mailChimp->getSubscribers();
    expect($subscribers['result'])->false();
    expect($subscribers['error'])->contains('lists');
    $subscribers = $this->mailChimp->getSubscribers(array(12));
    expect($subscribers['result'])->false();
    expect($subscribers['error'])->contains('lists');
  }

  function itCanGetSubscribers() {
    $subscribers = $this->mailChimp->getSubscribers(array($this->lists[0]));
    expect($subscribers['result'])->true();
    expect(isset($subscribers['data']['invalid']))->true();
    expect(isset($subscribers['data']['duplicate']))->true();
    expect(isset($subscribers['data']['header']))->true();
    expect(count($subscribers['data']['subscribers']))->equals(1);
    expect($subscribers['data']['subscribersCount'])->equals(1);
  }

  function itFailsWhenListHeadersDontMatch() {
    $subscribers = $this->mailChimp->getSubscribers($this->lists);
    expect($subscribers['result'])->false();
    expect($subscribers['error'])->contains('header');
  }

  function itFailWhenSubscribersDataTooLarge() {
    $mailChimp = clone($this->mailChimp);
    $mailChimp->maxPostSize = 10;
    $subscribers = $mailChimp->getSubscribers(array('8b66f7fac8'));
    expect($subscribers['result'])->false();
    expect($subscribers['error'])->contains('large');
  }
}