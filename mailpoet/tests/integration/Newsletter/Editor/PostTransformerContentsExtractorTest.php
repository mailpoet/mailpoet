<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter\Editor;

use MailPoet\Newsletter\Editor\PostTransformerContentsExtractor;
use MailPoet\WP\Functions as WPFunctions;

class PostTransformerContentsExtractorTest extends \MailPoetTest {
  /** @var WPFunctions */
  private $wp;

  /** @var int */
  private $postId;

  public function _before() {
    parent::_before();
    $this->wp = new WPFunctions();
    $this->postId = (int)$this->wp->wpInsertPost([
      'post_title' => 'Post with a read more link',
      'post_content' => 'Body',
      'post_status' => 'publish',
      'post_type' => 'post',
    ]);
  }

  public function testItEscapesTheReadMoreLinkText(): void {
    $extractor = new PostTransformerContentsExtractor([
      'displayType' => 'excerpt',
      'imageFullWidth' => false,
      'readMoreType' => 'link',
      'readMoreText' => 'Read <b>more</b>',
    ]);

    $content = $extractor->getContent($this->getPost(), false, 'excerpt');
    $html = implode('', array_column($content, 'text'));

    verify($html)->stringContainsString('Read &lt;b&gt;more&lt;/b&gt;</a>');
    verify($html)->stringNotContainsString('<b>more</b>');
  }

  public function testItKeepsTheAuthorLabelEscapedThroughThePostPipeline(): void {
    $extractor = new PostTransformerContentsExtractor([
      'displayType' => 'full',
      'imageFullWidth' => false,
      'readMoreType' => 'none',
      'showAuthor' => 'aboveText',
      'authorPrecededBy' => '<b>By</b>',
    ]);

    $content = $extractor->getContent($this->getPost(), false, 'full');
    $html = implode('', array_column($content, 'text'));

    verify($html)->stringContainsString('&lt;b&gt;By&lt;/b&gt;');
    verify($html)->stringNotContainsString('<b>By</b>');
  }

  public function _after() {
    $this->wp->wpDeletePost($this->postId, true);
    parent::_after();
  }

  private function getPost(): \WP_Post {
    $post = $this->wp->getPost($this->postId);
    $this->assertInstanceOf(\WP_Post::class, $post);
    return $post;
  }
}
