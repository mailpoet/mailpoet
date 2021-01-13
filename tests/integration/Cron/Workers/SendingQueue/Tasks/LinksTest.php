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
    $newsletterLink = NewsletterLink::where('hash', $links[0]['hash'])
      ->findOne();
    assert($newsletterLink instanceof NewsletterLink);
    expect($newsletterLink->newsletterId)->equals($newsletter->id);
    expect($newsletterLink->queueId)->equals($queue->id);
    expect($newsletterLink->url)->equals($links[0]['link']);
  }

  public function testItCanHashAndReplaceLinks() {
    $renderedNewsletter = [
      'html' => '<a href="http://example.com">Example Link</a>',
      'text' => '<a href="http://example.com">Example Link</a>',
    ];
    $result = Links::hashAndReplaceLinks($renderedNewsletter, 0, 0);
    $processedRenderedNewsletterBody = $result[0];
    $processedAndHashedLinks = $result[1];
    expect($processedRenderedNewsletterBody['html'])
      ->stringContainsString($processedAndHashedLinks[0]['hash']);
    expect($processedRenderedNewsletterBody['text'])
      ->stringContainsString($processedAndHashedLinks[0]['hash']);
    expect($processedAndHashedLinks[0]['link'])->equals('http://example.com');
  }

  public function testItCanProcessRenderedBody() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_STANDARD;
    $newsletter->save();
    $renderedNewsletter = [
      'html' => '<a href="http://example.com">Example Link</a>',
      'text' => '<a href="http://example.com">Example Link</a>',
    ];
    $queue = (object)['id' => 2];
    $result = Links::process($renderedNewsletter, $newsletter, $queue);
    $newsletterLink = NewsletterLink::where('newsletter_id', $newsletter->id)
      ->findOne();
    assert($newsletterLink instanceof NewsletterLink);
    expect($result['html'])->stringContainsString($newsletterLink->hash);
  }

  public function testItCanEnsureThatInstantUnsubscribeLinkIsAlwaysPresent() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_STANDARD;
    $newsletter->save();
    $renderedNewsletter = [
      'html' => '<a href="http://example.com">Example Link</a>',
      'text' => '<a href="http://example.com">Example Link</a>',
    ];
    $queue = (object)['id' => 2];
    Links::process($renderedNewsletter, $newsletter, $queue);
    $unsubscribeCount = NewsletterLink::where('newsletter_id', $newsletter->id)
      ->where('url', NewsletterLink::INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE)->count();
    expect($unsubscribeCount)->equals(1);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Newsletter::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
  }
}
