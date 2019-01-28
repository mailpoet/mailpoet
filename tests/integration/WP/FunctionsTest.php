<?php
namespace MailPoet\Test\WP;

use MailPoet\WP\Functions as WPFunctions;
use MailPoet\Config\Env;

class FunctionsTest extends \MailPoetTest {
  function _before() {
    global $content_width;
    $this->_content_width = $content_width;
    $content_width = 150;
    $this->action = 'mailpoet_test_action';
    $this->filter = 'mailpoet_test_filter';
    $this->wp = new WPFunctions;
  }

  function makeAttachment($upload, $parent_post_id = 0) {
    $type = '';
    if(!empty($upload['type'])) {
        $type = $upload['type'];
    } else {
        $mime = wp_check_filetype($upload['file']);
        if ($mime)
            $type = $mime['type'];
    }

    $attachment = array(
        'post_title' => basename($upload['file']),
        'post_content' => '',
        'post_type' => 'attachment',
        'post_parent' => $parent_post_id,
        'post_mime_type' => $type,
        'guid' => $upload['url'],
    );

    // Save the data
    $id = wp_insert_attachment($attachment, $upload['file'], $parent_post_id);
    $metadata = wp_generate_attachment_metadata($id, $upload['file']);
    wp_update_attachment_metadata($id, $metadata);

    return $this->ids[] = $id;
  }

  function testItCanGetImageInfo() {
    expect(
      function_exists('wp_generate_attachment_metadata')
    )->true();

    $filename = 'tests/_data/test-image.jpg';
    $contents = file_get_contents($filename);

    $upload = wp_upload_bits(basename($filename), null, $contents);
    $id = $this->makeAttachment($upload);
    expect($id)->notEmpty();

    $image = $this->wp->getImageInfo($id);
    expect($image[1])->equals(Env::NEWSLETTER_CONTENT_WIDTH);

    wp_delete_attachment($id, $force_delete = true);
  }


  function testItCanProcessActions() {
    $test_value = array('abc', 'def');
    $test_value2 = new \stdClass;
    $called = false;

    $callback = function ($value, $value2) use ($test_value, $test_value2, &$called) {
      $called = true;
      expect($value)->same($test_value);
      expect($value2)->same($test_value2);
    };

    $this->wp->addAction($this->action, $callback, 10, 2);
    $this->wp->doAction($this->action, $test_value, $test_value2);

    expect($called)->true();

    $called = false;
    $this->wp->removeAction($this->action, $callback);
    $this->wp->doAction($this->action);
    expect($called)->false();
  }

  function testItCanProcessFilters() {
    $test_value = array('abc', 'def');

    $called = false;

    $callback = function ($value) use ($test_value, &$called) {
      $called = true;
      return $test_value;
    };

    $this->wp->addFilter($this->filter, $callback);
    $result = $this->wp->applyFilters($this->filter, $test_value);

    expect($called)->true();
    expect($result)->equals($test_value);

    $called = false;
    $this->wp->removeFilter($this->filter, $callback);
    $this->wp->applyFilters($this->filter, $test_value);
    expect($called)->false();
  }

  function _after() {
    global $content_width;
    $content_width = $this->_content_width;
  }
}
