<?php

use Codeception\Util\Fixtures;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Router\Router;

class LinksTest extends MailPoetTest {
  function testItOnlyExtractsLinksFromHrefs() {
    $template = '<a href="http://link1.com">some site</a> http://link2.com <img src="http://link3.com">';
    $result = Links::extract($template);

    expect($result[0])->equals(
      array(
        'html' => 'href="http://link1.com"',
        'link' => 'http://link1.com'
      )
    );
  }

  function testItCanExtactLinkShortcodes() {
    $template = '[notlink:shortcode] [link:some_link_shortcode]';
    $result = Links::extract($template);

    expect($result[0])->equals(
      array(
        'html' => '[link:some_link_shortcode]',
        'link' => '[link:some_link_shortcode]'
      )
    );
  }

  function testItHashesAndReplacesLinks() {
    $template = '<a href="http://link1.com">some site</a> [link:some_link_shortcode]';
    list($updated_content, $hashed_links) = Links::process($template);

    // 2 links were hashed
    expect(count($hashed_links))->equals(2);
    // links in returned content were replaced with hashes
    expect($updated_content)
      ->contains(Links::DATA_TAG_CLICK . '-' . $hashed_links[0]['hash']);
    expect($updated_content)
      ->contains(Links::DATA_TAG_CLICK . '-' . $hashed_links[1]['hash']);
    expect($updated_content)->notContains('link');
  }

  function testItReplacesHashedLinksWithSubscriberData() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $queue = SendingQueue::create();
    $queue->newsletter_id = 1;
    $queue->save();
    $template = '<a href="[mailpoet_click_data]-1234">some site</a> <img src="[mailpoet_open_data]"/>';
    $result = Links::replaceSubscriberData($subscriber->id, $queue->id, $template);

    // there are no click/open data tags
    expect($result)->notContains(Links::DATA_TAG_CLICK);
    expect($result)->notContains(Links::DATA_TAG_OPEN);

    // data tags were converted to URLs
    expect($result)
      ->regExp('/<a href="http.*?' . Router::NAME . '&endpoint=track&action=click&data=.*?>/');
    expect($result)
      ->regExp('/<img src="http.*?' . Router::NAME . '&endpoint=track&action=open&data=.*?>/');

    // data was properly encoded
    preg_match_all('/data=(?P<data>.*?)"/', $result, $result);
    foreach($result['data'] as $data) {
      $data = Router::decodeRequestData($data);
      expect($data['subscriber_id'])->equals($subscriber->id);
      expect($data['queue_id'])->equals($queue->id);
      expect(isset($data['subscriber_token']))->true();
    }
  }

  function testItCanSaveLinks() {
    $links = array(
      array(
        'url' => 'http://example.com',
        'hash' => 123
      )
    );
    Links::save(
      $links,
      $newsletter_id = 1,
      $queue_id = 1
    );

    // 1 database record was created
    $newsltter_link = NewsletterLink::where('newsletter_id', 1)
      ->where('queue_id', 1)
      ->findOne();
    expect($newsltter_link->hash)->equals(123);
    expect($newsltter_link->url)->equals('http://example.com');
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
  }
}