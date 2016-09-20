<?php
use MailPoet\Cron\Workers\SendingQueue\Tasks\Links;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;

if(!defined('ABSPATH')) exit;

class LinkTaskTest extends MailPoetTest {
  function testItCanSaveLinks() {
    $links = array(
      array(
        'url' => 'http://example.com',
        'hash' => 'some_hash'
      )
    );
    $newsletter = (object)array('id' => 1);
    $queue = (object)array('id' => 2);
    $result = Links::saveLinks($links, $newsletter, $queue);
    $newsletter_link = NewsletterLink::where('hash', $links[0]['hash'])
      ->findOne();
    expect($newsletter_link->newsletter_id)->equals($newsletter->id);
    expect($newsletter_link->queue_id)->equals($queue->id);
    expect($newsletter_link->url)->equals($links[0]['url']);
  }

  function testItCanHashAndReplaceLinks() {
    $rendered_newsletter = array(
      'html' => '<a href="http://example.com">Example Link</a>',
      'text' => '<a href="http://example.com">Example Link</a>'
    );
    $result = Links::hashAndReplaceLinks($rendered_newsletter);
    $processed_rendered_newsletter_body = $result[0];
    $processed_and_hashed_links = $result[1];
    expect($processed_rendered_newsletter_body['html'])
      ->contains($processed_and_hashed_links[0]['hash']);
    expect($processed_rendered_newsletter_body['text'])
      ->contains($processed_and_hashed_links[0]['hash']);
    expect($processed_and_hashed_links[0]['url'])->equals('http://example.com');
  }

  function testItCanProcessRenderedBody() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_STANDARD;
    $newsletter->save();
    $rendered_newsletter = array(
      'html' => '<a href="http://example.com">Example Link</a>',
      'text' => '<a href="http://example.com">Example Link</a>'
    );
    $queue = (object)array('id' => 2);
    $result = Links::process($rendered_newsletter, $newsletter, $queue);
    $newsletter_link = NewsletterLink::where('newsletter_id', $newsletter->id)
      ->findOne();
    expect($result['html'])->contains($newsletter_link->hash);
  }

  function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
  }
}