<?php

namespace MailPoet\Test\Newsletter\Links;

use Codeception\Util\Fixtures;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Router\Router;
use MailPoetVendor\Idiorm\ORM;

class LinksTest extends \MailPoetTest {
  public function testItOnlyExtractsLinksFromAnchorTags() {
    $template = '<a href="http://example.com">some site</a> http://link2.com <img src="http://link3.com">';
    $result = Links::extract($template);

    expect($result[0])->equals(
      [
        'type' => Links::LINK_TYPE_URL,
        'link' => 'http://example.com',
      ]
    );
  }

  public function testItOnlyHashesAndReplacesLinksInAnchorTags() {
    $template = '<a href="http://example.com"><img src="http://example.com" /></a>';
    $result = Links::process($template, 0, 0);
    expect($result[0])->equals(
      sprintf(
        '<a href="%s-%s"><img src="http://example.com" /></a>',
        Links::DATA_TAG_CLICK,
        $result[1][0]['hash']
      )
    );
  }

  public function testItDoesNotRehashExistingLinks() {
    $link = NewsletterLink::create();
    $link->newsletterId = 3;
    $link->queueId = 3;
    $link->hash = '123';
    $link->url = 'http://example.com';
    $link->save();

    $template = '<a href="http://example.com"><img src="http://example.com" /></a>';
    $result = Links::process($template, 3, 3);
    expect($result[0])->equals(
      sprintf(
        '<a href="%s-%s"><img src="http://example.com" /></a>',
        Links::DATA_TAG_CLICK,
        123
      )
    );
  }

  public function testItCanExtactLinkShortcodes() {
    $template = '[notlink:shortcode] [link:some_link_shortcode]';
    $result = Links::extract($template);

    expect($result[0])->equals(
      [
        'type' => Links::LINK_TYPE_SHORTCODE,
        'link' => '[link:some_link_shortcode]',
      ]
    );
  }

  public function testItHashesAndReplacesLinks() {
    $template = '<a href="http://example.com">some site</a> [link:some_link_shortcode]';
    list($updatedContent, $hashedLinks) = Links::process($template, 0, 0);

    // 2 links were hashed
    expect(count($hashedLinks))->equals(2);
    // links in returned content were replaced with hashes
    expect($updatedContent)
      ->stringContainsString(Links::DATA_TAG_CLICK . '-' . $hashedLinks[0]['hash']);
    expect($updatedContent)
      ->stringContainsString(Links::DATA_TAG_CLICK . '-' . $hashedLinks[1]['hash']);
    expect($updatedContent)->stringNotContainsString('link');
  }

  public function testItHashesAndReplacesLinksWithSpecialCharacters() {
    $template = '<a href="http://сайт.cóm/彌撒時間">some site</a>';
    $result = Links::process($template, 0, 0);
    expect($result[0])->equals(
      sprintf(
        '<a href="%s-%s">some site</a>',
        Links::DATA_TAG_CLICK,
        $result[1][0]['hash']
      )
    );
  }

  public function testItDoesNotReplaceUnprocessedLinks() {
    $template = '<a href="http://example.com">some site</a> [link:some_link_shortcode]';

    $processedLinks = [
      'http://example.com' => [
        'type' => Links::LINK_TYPE_URL,
        'link' => 'http://example.com',
        'processed_link' => 'replace by this',
      ],
    ];

    list($updatedContent, $replacedLinks) =
      Links::replace($template, $processedLinks);

    expect($replacedLinks)->count(1);
    // links in returned content were replaced with hashes
    expect($updatedContent)
      ->stringContainsString('replace by this');
    expect($updatedContent)
      ->stringContainsString('[link:some_link_shortcode]');
    expect($updatedContent)->stringNotContainsString('http://example.com');
  }

  public function testItCreatesAndTransformsUrlDataObject() {
    $subscriberEmail = 'test@example.com';
    $data = [
      'subscriber_id' => 1,
      'subscriber_token' => md5($subscriberEmail),
      'queue_id' => 2,
      'link_hash' => 'hash',
      'preview' => false,
    ];
    $urlDataObject = Links::createUrlDataObject(
      $data['subscriber_id'],
      $data['subscriber_token'],
      $data['queue_id'],
      $data['link_hash'],
      $data['preview']
    );
    // URL data object should be an indexed array
    expect($urlDataObject)->equals(array_values($data));
    // transformed URL object should be an associative array
    $transformedUrlDataObject = Links::transformUrlDataObject($urlDataObject);
    expect($transformedUrlDataObject)->equals($data);
  }

  public function testItReplacesHashedLinksWithSubscriberData() {
    $subscriber = Subscriber::create();
    $subscriber->hydrate(Fixtures::get('subscriber_template'));
    $subscriber->save();
    $queue = SendingQueue::create();
    $queue->newsletterId = 1;
    $queue->save();
    $template = '<a href="[mailpoet_click_data]-1234">some site</a> <img src="[mailpoet_open_data]"/>';
    $result = Links::replaceSubscriberData($subscriber->id, $queue->id, $template);

    // there are no click/open data tags
    expect($result)->stringNotContainsString(Links::DATA_TAG_CLICK);
    expect($result)->stringNotContainsString(Links::DATA_TAG_OPEN);

    // data tags were converted to URLs
    expect($result)
      ->regExp('/<a href="http.*?' . Router::NAME . '&endpoint=track&action=click&data=.*?>/');
    expect($result)
      ->regExp('/<img src="http.*?' . Router::NAME . '&endpoint=track&action=open&data=.*?>/');

    // data was properly encoded
    preg_match_all('/data=(?P<data>.*?)"/', $result, $result);
    foreach ($result['data'] as $data) {
      $data = Router::decodeRequestData($data);
      $data = Links::transformUrlDataObject($data);
      expect($data['subscriber_id'])->equals($subscriber->id);
      expect($data['queue_id'])->equals($queue->id);
      expect(isset($data['subscriber_token']))->true();
    }
  }

  public function testItCanSaveLinks() {
    $links = [
      [
        'link' => 'http://example.com',
        'hash' => '123',
      ],
    ];
    Links::save(
      $links,
      $newsletterId = 1,
      $queueId = 1
    );

    // 1 database record was created
    $newsltterLink = NewsletterLink::where('newsletter_id', 1)
      ->where('queue_id', 1)
      ->findOne();
    assert($newsltterLink instanceof NewsletterLink);
    expect($newsltterLink->hash)->equals('123');
    expect($newsltterLink->url)->equals('http://example.com');
  }

  public function testItCanReuseAlreadySavedLinks() {
    $link = NewsletterLink::create();
    $link->newsletterId = 1;
    $link->queueId = 2;
    $link->hash = '123';
    $link->url = 'http://example.com';
    $link->save();

    $link = NewsletterLink::create();
    $link->newsletterId = 1;
    $link->queueId = 3;
    $link->hash = '456';
    $link->url = 'http://demo.com';
    $link->save();

    list($content, $links) = Links::process('<a href="http://example.com">x</a>', 1, 2);
    expect(is_array($links))->true();
    expect(count($links))->equals(1);
    expect($links[0]['hash'])->equals('123');
    expect($links[0]['url'])->equals('http://example.com');
  }

  public function testItMatchesHashedLinks() {
    $regex = Links::getLinkRegex();
    expect((boolean)preg_match($regex, '[some_tag]-123'))->false();
    expect((boolean)preg_match($regex, '[some_tag]'))->false();
    expect((boolean)preg_match($regex, '[mailpoet_click_data]-123'))->true();
    expect((boolean)preg_match($regex, '[mailpoet_open_data]'))->true();
  }

  public function testItCanConvertOnlyHashedLinkShortcodes() {
    // create newsletter link association
    $queueId = 1;
    $newsletterLink = NewsletterLink::create();
    $newsletterLink->newsletterId = 1;
    $newsletterLink->queueId = $queueId;
    $newsletterLink->hash = '90e56';
    $newsletterLink->url = '[link:newsletter_view_in_browser_url]';
    $newsletterLink = $newsletterLink->save();
    $content = '
      <a href="[mailpoet_click_data]-90e56">View in browser</a>
      <a href="[mailpoet_click_data]-123">Some link</a>';
    $result = Links::convertHashedLinksToShortcodesAndUrls($content, $queueId);
    expect($result)->stringContainsString($newsletterLink->url);
    expect($result)->stringContainsString('[mailpoet_click_data]-123');
  }

  public function testItCanEnsureThatUnsubscribeLinkIsAmongProcessedLinks() {
    $links = [
      [
        'link' => 'http://example.com',
        'type' => Links::LINK_TYPE_URL,
        'processed_link' => '[mailpoet_click_data]-123',
        'hash' => 'abcdfgh',
      ],
    ];
    $links = Links::ensureInstantUnsubscribeLink($links);
    expect(count($links))->equals(2);
    expect($links[1]['link'])->equals(NewsletterLink::INSTANT_UNSUBSCRIBE_LINK_SHORT_CODE);
    expect($links[1]['type'])->equals(Links::LINK_TYPE_SHORTCODE);
    expect($links[1])->hasKey('processed_link');
    expect($links[1])->hasKey('hash');
    $links = Links::ensureInstantUnsubscribeLink($links);
    expect(count($links))->equals(2);
  }

  public function testItCanConvertAllHashedLinksToUrls() {
    // create newsletter link associations
    $queueId = 1;
    $newsletterLink1 = NewsletterLink::create();
    $newsletterLink1->newsletterId = 1;
    $newsletterLink1->queueId = $queueId;
    $newsletterLink1->hash = '90e56';
    $newsletterLink1->url = '[link:newsletter_view_in_browser_url]';
    $newsletterLink1 = $newsletterLink1->save();
    $newsletterLink2 = NewsletterLink::create();
    $newsletterLink2->newsletterId = 1;
    $newsletterLink2->queueId = $queueId;
    $newsletterLink2->hash = '123';
    $newsletterLink2->url = 'http://google.com';
    $newsletterLink2 = $newsletterLink2->save();
    $content = '
      <a href="[mailpoet_click_data]-90e56">View in browser</a>
      <a href="[mailpoet_click_data]-123">Some link</a>';
    $result = Links::convertHashedLinksToShortcodesAndUrls($content, $queueId, $convertAll = true);
    expect($result)->stringContainsString($newsletterLink1->url);
    expect($result)->stringContainsString($newsletterLink2->url);
  }

  public function _after() {
    ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
  }
}
