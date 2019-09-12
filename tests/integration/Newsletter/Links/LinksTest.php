<?php
namespace MailPoet\Test\Newsletter\Links;

use Codeception\Util\Fixtures;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\SendingQueue;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Newsletter\Shortcodes\Categories\Link;
use MailPoet\Router\Router;

class LinksTest extends \MailPoetTest {
  function testItOnlyExtractsLinksFromAnchorTags() {
    $template = '<a href="http://example.com">some site</a> http://link2.com <img src="http://link3.com">';
    $result = Links::extract($template);

    expect($result[0])->equals(
      [
        'type' => Links::LINK_TYPE_URL,
        'link' => 'http://example.com',
      ]
    );
  }

  function testItOnlyHashesAndReplacesLinksInAnchorTags() {
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

  function testItDoesNotRehashExistingLinks() {
    $link = NewsletterLink::create();
    $link->newsletter_id = 3;
    $link->queue_id = 3;
    $link->hash = 123;
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

  function testItCanExtactLinkShortcodes() {
    $template = '[notlink:shortcode] [link:some_link_shortcode]';
    $result = Links::extract($template);

    expect($result[0])->equals(
      [
        'type' => Links::LINK_TYPE_SHORTCODE,
        'link' => '[link:some_link_shortcode]',
      ]
    );
  }

  function testItHashesAndReplacesLinks() {
    $template = '<a href="http://example.com">some site</a> [link:some_link_shortcode]';
    list($updated_content, $hashed_links) = Links::process($template, 0, 0);

    // 2 links were hashed
    expect(count($hashed_links))->equals(2);
    // links in returned content were replaced with hashes
    expect($updated_content)
      ->contains(Links::DATA_TAG_CLICK . '-' . $hashed_links[0]['hash']);
    expect($updated_content)
      ->contains(Links::DATA_TAG_CLICK . '-' . $hashed_links[1]['hash']);
    expect($updated_content)->notContains('link');
  }


  function testItHashesAndReplacesLinksWithSpecialCharacters() {
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

  function testItDoesNotReplaceUnprocessedLinks() {
    $template = '<a href="http://example.com">some site</a> [link:some_link_shortcode]';

    $processed_links = [
      'http://example.com' => [
        'type' => Links::LINK_TYPE_URL,
        'link' => 'http://example.com',
        'processed_link' => 'replace by this',
      ],
    ];

    list($updated_content, $replaced_links) =
      Links::replace($template, $processed_links);

    expect($replaced_links)->count(1);
    // links in returned content were replaced with hashes
    expect($updated_content)
      ->contains('replace by this');
    expect($updated_content)
      ->contains('[link:some_link_shortcode]');
    expect($updated_content)->notContains('http://example.com');
  }

  function testItCreatesAndTransformsUrlDataObject() {
    $subscriber_email = 'test@example.com';
    $data = [
      'subscriber_id' => 1,
      'subscriber_token' => Subscriber::generateToken($subscriber_email),
      'queue_id' => 2,
      'link_hash' => 'hash',
      'preview' => false,
    ];
    $url_data_object = Links::createUrlDataObject(
      $data['subscriber_id'],
      $data['subscriber_token'],
      $data['queue_id'],
      $data['link_hash'],
      $data['preview']
    );
    // URL data object should be an indexed array
    expect($url_data_object)->equals(array_values($data));
    // transformed URL object should be an associative array
    $transformed_url_data_object = Links::transformUrlDataObject($url_data_object);
    expect($transformed_url_data_object)->equals($data);
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
    foreach ($result['data'] as $data) {
      $data = Router::decodeRequestData($data);
      $data = Links::transformUrlDataObject($data);
      expect($data['subscriber_id'])->equals($subscriber->id);
      expect($data['queue_id'])->equals($queue->id);
      expect(isset($data['subscriber_token']))->true();
    }
  }

  function testItCanSaveLinks() {
    $links = [
      [
        'link' => 'http://example.com',
        'hash' => 123,
      ],
    ];
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

  function testItCanLoadLinks() {
    $link = NewsletterLink::create();
    $link->newsletter_id = 1;
    $link->queue_id = 2;
    $link->hash = 123;
    $link->url = 'http://example.com';
    $link->save();

    $link = NewsletterLink::create();
    $link->newsletter_id = 1;
    $link->queue_id = 3;
    $link->hash = 456;
    $link->url = 'http://demo.com';
    $link->save();

    $links = Links::load(1, 2);
    expect(is_array($links))->true();
    expect(count($links))->equals(1);
    expect($links['http://example.com']['hash'])->equals(123);
    expect($links['http://example.com']['url'])->equals('http://example.com');
  }

  function testItMatchesHashedLinks() {
    $regex = Links::getLinkRegex();
    expect((boolean)preg_match($regex, '[some_tag]-123'))->false();
    expect((boolean)preg_match($regex, '[some_tag]'))->false();
    expect((boolean)preg_match($regex, '[mailpoet_click_data]-123'))->true();
    expect((boolean)preg_match($regex, '[mailpoet_open_data]'))->true();
  }

  function testItCanConvertOnlyHashedLinkShortcodes() {
    // create newsletter link association
    $queue_id = 1;
    $newsletter_link = NewsletterLink::create();
    $newsletter_link->newsletter_id = 1;
    $newsletter_link->queue_id = $queue_id;
    $newsletter_link->hash = '90e56';
    $newsletter_link->url = '[link:newsletter_view_in_browser_url]';
    $newsletter_link = $newsletter_link->save();
    $content = '
      <a href="[mailpoet_click_data]-90e56">View in browser</a>
      <a href="[mailpoet_click_data]-123">Some link</a>';
    $result = Links::convertHashedLinksToShortcodesAndUrls($content, $queue_id);
    expect($result)->contains($newsletter_link->url);
    expect($result)->contains('[mailpoet_click_data]-123');
  }

  function testItCanConvertAllHashedLinksToUrls() {
    // create newsletter link associations
    $queue_id = 1;
    $newsletter_link_1 = NewsletterLink::create();
    $newsletter_link_1->newsletter_id = 1;
    $newsletter_link_1->queue_id = $queue_id;
    $newsletter_link_1->hash = '90e56';
    $newsletter_link_1->url = '[link:newsletter_view_in_browser_url]';
    $newsletter_link_1 = $newsletter_link_1->save();
    $newsletter_link_2 = NewsletterLink::create();
    $newsletter_link_2->newsletter_id = 1;
    $newsletter_link_2->queue_id = $queue_id;
    $newsletter_link_2->hash = '123';
    $newsletter_link_2->url = 'http://google.com';
    $newsletter_link_2 = $newsletter_link_2->save();
    $content = '
      <a href="[mailpoet_click_data]-90e56">View in browser</a>
      <a href="[mailpoet_click_data]-123">Some link</a>';
    $result = Links::convertHashedLinksToShortcodesAndUrls($content, $queue_id, $convert_all = true);
    expect($result)->contains($newsletter_link_1->url);
    expect($result)->contains($newsletter_link_2->url);
  }

  function _after() {
    \ORM::raw_execute('TRUNCATE ' . Subscriber::$_table);
    \ORM::raw_execute('TRUNCATE ' . SendingQueue::$_table);
    \ORM::raw_execute('TRUNCATE ' . NewsletterLink::$_table);
  }
}
