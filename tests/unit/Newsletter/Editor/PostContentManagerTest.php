<?php
namespace MailPoet\Test\Newsletter\Editor;

use Helper\WordPressHooks as WPHooksHelper;
use MailPoet\Newsletter\Editor\PostContentManager;
use MailPoet\WP\Hooks;

class PostContentManagerTest extends \MailPoetTest {

  function _before() {
    $this->post_content = new PostContentManager();
  }

  function testFilterContentRetainsStructuralTags() {
    $html = '<p>some paragraph text</p>';
    expect($this->post_content->filterContent($html))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '">some paragraph text</p>'
    );

    $html = '<span>spanning</span>';
    expect($this->post_content->filterContent($html))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '">' . $html . '</p>'
    );

    $html = '<blockquote>do not strip this</blockquote>';
    expect($this->post_content->filterContent($html))->equals(
      '<blockquote><p class="' . PostContentManager::WP_POST_CLASS . '">do not strip this</p></blockquote>'
    );

    $html = '<ul><li>First item</li><li>Second item</li></ul>';
    expect($this->post_content->filterContent($html))->equals(
      "<ul>\n<li>First item</li>\n<li>Second item</li>\n</ul>"
    );

    $html = '<ol><li>First item</li><li>Second item</li></ol>';
    expect($this->post_content->filterContent($html))->equals(
      "<ol>\n<li>First item</li>\n<li>Second item</li>\n</ol>"
    );
  }

  function testFilterContentRetainsHeadings() {
    $html = '<h1>heading 1</h1>';
    expect($this->post_content->filterContent($html))->equals($html);

    $html = '<h2>heading 2</h2>';
    expect($this->post_content->filterContent($html))->equals($html);

    $html = '<h3>heading 3</h3>';
    expect($this->post_content->filterContent($html))->equals($html);
  }

  function testFilterContentRetainsTextStyling() {
    $text_tags = array(
      '<em>emphasized></em>',
      '<b>bold</b>',
      '<strong>strong</strong>',
      '<i>italic</i>',
      'Text<br />new line'
    );
    foreach($text_tags as $html) {
      expect($this->post_content->filterContent($html))->equals(
        '<p class="' . PostContentManager::WP_POST_CLASS . '">' . $html . '</p>'
      );
    }
  }

  function testFilterContentRetainsImagesAndLinks() {
    $html = '<img src="#" alt="some alt" />';
    expect($this->post_content->filterContent($html))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '"><img src="#" alt="some alt" /></p>'
    );

    $html = '<a href="#" title="link title">some link</a>';
    expect($this->post_content->filterContent($html))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '">' . $html . '</p>'
    );
  }

  function testFilterContentStripsUndesirableTags() {
    $undesirable_tags = array(
      '<embed src="#" />',
      '<iframe src="#" />',
      '<form></form>',
      '<input type="text" />',
      '<hr />',
      '<script></script>',
      '<style></style>',
      '<table></table>',
      '<tr></tr>',
      '<td></td>',
    );

    foreach($undesirable_tags as $html) {
      expect($this->post_content->filterContent($html))->equals('');
    }
  }

  function testItAppliesCustomMaxExcerptLenghViaHook() {
    $post_content_manager = new PostContentManager();
    $post = (object)array(
      'post_content' => '<p>one two three four five six</p>'
    );
    $excerpt = $post_content_manager->getContent($post, 'excerpt');
    expect($excerpt)->equals('one two three four five six');
    Hooks::addFilter(
      'mailpoet_newsletter_post_excerpt_length',
      function() {
        return 2;
      }
    );
    $post_content_manager = new PostContentManager();
    $excerpt = $post_content_manager->getContent($post, 'excerpt');
    expect($excerpt)->equals('one two &hellip;');
  }

  function _after() {
    WPHooksHelper::releaseAllHooks();
  }
}