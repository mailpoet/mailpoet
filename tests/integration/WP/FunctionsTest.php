<?php

namespace MailPoet\Test\WP;

use MailPoet\WP\Functions as WPFunctions;

class FunctionsTest extends \MailPoetTest {
  public $ids;
  public $wp;
  public $filter;
  public $action;
  public $_content_width;
  public function _before() {
    parent::_before();
    global $contentWidth;
    $this->contentWidth = $contentWidth;
    $contentWidth = 150;
    $this->action = 'mailpoet_test_action';
    $this->filter = 'mailpoet_test_filter';
    $this->wp = new WPFunctions;
  }

  public function makeAttachment($upload, $parentPostId = 0) {
    $type = '';
    if (!empty($upload['type'])) {
        $type = $upload['type'];
    } else {
        $mime = wp_check_filetype($upload['file']);
        if ($mime)
            $type = $mime['type'];
    }

    $attachment = [
        'post_title' => basename($upload['file']),
        'post_content' => '',
        'post_type' => 'attachment',
        'post_parent' => $parentPostId,
        'post_mime_type' => $type,
        'guid' => $upload['url'],
    ];

    // Save the data
    /** @var int $id */
    $id = wp_insert_attachment($attachment, $upload['file'], $parentPostId);
    $metadata = wp_generate_attachment_metadata($id, $upload['file']);
    wp_update_attachment_metadata($id, $metadata);

    return $this->ids[] = $id;
  }

  public function testItCanProcessActions() {
    $testValue = ['abc', 'def'];
    $testValue2 = new \stdClass;
    $called = false;

    $callback = function ($value, $value2) use ($testValue, $testValue2, &$called) {
      $called = true;
      expect($value)->same($testValue);
      expect($value2)->same($testValue2);
    };

    $this->wp->addAction($this->action, $callback, 10, 2);
    $this->wp->doAction($this->action, $testValue, $testValue2);

    expect($called)->true();

    $called = false;
    $this->wp->removeAction($this->action, $callback);
    $this->wp->doAction($this->action);
    expect($called)->false();
  }

  public function testItCanProcessFilters() {
    $testValue = ['abc', 'def'];

    $called = false;

    $callback = function ($value) use ($testValue, &$called) {
      $called = true;
      return $testValue;
    };

    $this->wp->addFilter($this->filter, $callback);
    $result = $this->wp->applyFilters($this->filter, $testValue);

    expect($called)->true();
    expect($result)->equals($testValue);

    $called = false;
    $this->wp->removeFilter($this->filter, $callback);
    $this->wp->applyFilters($this->filter, $testValue);
    expect($called)->false();
  }

  public function _after() {
    global $contentWidth;
    $contentWidth = $this->contentWidth;
  }
}
