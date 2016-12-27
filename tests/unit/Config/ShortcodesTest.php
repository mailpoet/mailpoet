<?php

use MailPoet\Config\Shortcodes;
use MailPoet\Models\Newsletter;
use MailPoet\Models\SendingQueue;
use MailPoet\Newsletter\Url;
use MailPoet\Router\Router;

class ConfigShortcodesTest extends MailPoetTest {
  function _before() {
    $newsletter = Newsletter::create();
    $newsletter->type = Newsletter::TYPE_STANDARD;
    $newsletter->status = Newsletter::STATUS_SENT;
    $this->newsletter = $newsletter->save();
    $queue = SendingQueue::create();
    $queue->newsletter_id = $newsletter->id;
    $queue->status = SendingQueue::STATUS_COMPLETED;
    $this->queue = $queue->save();
  }

  function testItGetsArchives() {
    $shortcodes = new Shortcodes();
    // result contains a link pointing to the "view in browser" router endpoint
    $result = $shortcodes->getArchive($params = false);
    $dom = pQuery::parseStr($result);
    $link = $dom->query('a');
    $link = $link->attr('href');
    expect($link)->contains('endpoint=view_in_browser');
    // request data object contains newsletter hash but not newsletter id
    $parsed_link = parse_url($link);
    parse_str(html_entity_decode($parsed_link['query']), $data);
    $request_data = Url::transformUrlDataObject(
      Router::decodeRequestData($data['data'])
    );
    expect($request_data['newsletter_id'])->isEmpty();
    expect($request_data['newsletter_hash'])->equals($this->newsletter->hash);
  }
}