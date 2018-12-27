<?php
namespace MailPoet\Test\WP;

use MailPoet\WP\Functions as WPFunctions;
use MailPoet\Config\Env;

class FunctionsTest extends \MailPoetTest {
  function _before() {
    global $content_width;
    $this->_content_width = $content_width;
    $content_width = 150;
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

    $wp = new WPFunctions();
    $image = $wp->getImageInfo($id);
    expect($image[1])->equals(Env::NEWSLETTER_CONTENT_WIDTH);

    wp_delete_attachment($id, $force_delete = true);
  }

  function _after() {
    global $content_width;
    $content_width = $this->_content_width;
  }
}
