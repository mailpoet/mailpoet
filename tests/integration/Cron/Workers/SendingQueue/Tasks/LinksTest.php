<?php

namespace MailPoet\Test\Cron\Workers\SendingQueue\Tasks;

use MailPoet\Cron\Workers\SendingQueue\Tasks\Links;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoetVendor\Idiorm\ORM;

class LinksTest extends \MailPoetTest {
  public function testItCanSaveLinks() {
    $links = [
      [
        'link' => 'http://example.com',
        'hash' => 'some_hash',
      ],
    ];
    $newsletter = (object)['id' => 1];
    $queue = (object)['id' => 2];
    $result = Links::saveLinks($links, $newsletter, $queue);
    $newsletter_link = NewsletterLink::where('hash', $links[0]['hash'])
      ->findOne();
    expect($newsletter_link->newsletter_id)->equals($newsletter->id);
    expect($newsletter_link->queue_id)->equals($queue->id);
    expect($newsletter_link->url)->equals($links[0]['link']);
  }

  public function testItCanHashAndReplaceLinks() {
    $rendered_newsletter = [
      'html' => '<a href="http://example.com">Example Link</a>',
      'text' => '<a href="http://example.com">Example Link</a>',
    ];
    $result = Links::hashAndReplaceLinks($rendered_newsletter, 0, 0);
    $processed_rendered_newsletter_body = $result[0];
    $processed_and_hashed_links = $result[1];
    expect($processed_rendered_newsletter_body['html'])
      ->contains($processed_and_hashed_links[0]['hash']);
    expect($processed_rendered_newsletter_body['text'])
      ->contains($processed_and_hashed_links[0]['hash']);
    expect($processed_and_hashed_links[0]['link'])->equals('http://example.com');
  }

  public function testItCanProcessRenderedBody() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_STANDARD;
    $newsletter->save();
    $rendered_newsletter = [
      'html' => '<a href="http://example.com">Example Link</a>',
      'text' => '<a href="http://example.com">Example Link</a>',
    ];
    $queue = (object)['id' => 2];
    $result = Links::process($rendered_newsletter, $newsletter, $queue);
    $newsletter_link = NewsletterLink::where('newsletter_id', $newsletter->id)
      ->findOne();
    expect($result['html'])->contains($newsletter_link->hash);
  }

  public function testItCanEnsureThatUnsubscribeLinkIsAlwaysPresent() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_STANDARD;
    $newsletter->save();
    $rendered_newsletter = [
      'html' => '<a href="http://example.com">Example Link</a>',
      'text' => '<a href="http://example.com">Example Link</a>',
    ];
    $queue = (object)['id' => 2];
    Links::process($rendered_newsletter, $newsletter, $queue);
    $unsubscribe_count = NewsletterLink::where('newsletter_id', $newsletter->id)
      ->where('url', NewsletterLink::UNSUBSCRIBE_LINK_SHORT_CODE)->count();
    expect($unsubscribe_count)->equals(1);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
  }
}
