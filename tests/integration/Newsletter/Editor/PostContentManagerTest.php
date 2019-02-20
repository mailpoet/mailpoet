<?php
namespace MailPoet\Test\Newsletter\Editor;

use MailPoet\Newsletter\Editor\PostContentManager;
use MailPoet\WP\Functions as WPFunctions;

class PostContentManagerTest extends \MailPoetTest {

  function _before() {
    parent::_before();
    $this->post_content = new PostContentManager();
  }

  function testFilterContentRetainsStructuralTags() {
    $html = '<p>some paragraph text</p>';
    expect($this->post_content->filterContent($html, 'full'))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '">some paragraph text</p>'
    );

    $html = '<span>spanning</span>';
    expect($this->post_content->filterContent($html, 'full'))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '">' . $html . '</p>'
    );

    $html = '<blockquote>do not strip this</blockquote>';
    expect($this->post_content->filterContent($html, 'full'))->equals(
      '<blockquote><p class="' . PostContentManager::WP_POST_CLASS . '">do not strip this</p></blockquote>'
    );

    $html = '<ul><li>First item</li><li>Second item</li></ul>';
    expect($this->post_content->filterContent($html, 'full'))->equals(
      "<ul>\n<li>First item</li>\n<li>Second item</li>\n</ul>"
    );

    $html = '<ol><li>First item</li><li>Second item</li></ol>';
    expect($this->post_content->filterContent($html, 'full'))->equals(
      "<ol>\n<li>First item</li>\n<li>Second item</li>\n</ol>"
    );
  }

  function testFilterContentRetainsHeadings() {
    $html = '<h1>heading 1</h1>';
    expect($this->post_content->filterContent($html, 'full'))->equals($html);

    $html = '<h2>heading 2</h2>';
    expect($this->post_content->filterContent($html, 'full'))->equals($html);

    $html = '<h3>heading 3</h3>';
    expect($this->post_content->filterContent($html, 'full'))->equals($html);

    $html = '<h1>heading 1</h1>';
    expect($this->post_content->filterContent($html, 'excerpt'))
      ->equals('<p class="' . PostContentManager::WP_POST_CLASS . '">heading 1</p>');

    $html = '<h2>heading 2</h2>';
    expect($this->post_content->filterContent($html, 'excerpt'))
      ->equals('<p class="' . PostContentManager::WP_POST_CLASS . '">heading 2</p>');

    $html = '<h3>heading 3</h3>';
    expect($this->post_content->filterContent($html, 'excerpt'))
      ->equals('<p class="' . PostContentManager::WP_POST_CLASS . '">heading 3</p>');
  }

  function testFilterContentRetainsTextStyling() {
    $text_tags = array(
      '<em>emphasized></em>',
      '<b>bold</b>',
      '<strong>strong</strong>',
      '<i>italic</i>',
      'Text<br />new line'
    );
    foreach ($text_tags as $html) {
      expect($this->post_content->filterContent($html, 'full'))->equals(
        '<p class="' . PostContentManager::WP_POST_CLASS . '">' . $html . '</p>'
      );
    }
  }

  function testFilterContentRetainsImagesAndLinks() {
    $html = '<img src="#" alt="some alt" />';
    expect($this->post_content->filterContent($html, 'full'))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '"><img src="#" alt="some alt" /></p>'
    );

    $html = '<a href="#" title="link title">some link</a>';
    expect($this->post_content->filterContent($html, 'full'))->equals(
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

    foreach ($undesirable_tags as $html) {
      expect($this->post_content->filterContent($html, 'full'))->equals('');
    }
  }

  function testFilterContentStripsUndesirableTagsForExcerpts() {
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
      '<img src="#" alt="some alt" />',
      '<h1></h1>',
      '<h2></h2>',
      '<h3></h3>',
    );

    foreach ($undesirable_tags as $html) {
      expect($this->post_content->filterContent($html, 'excerpt'))->equals('');
    }
  }


  function testItAppliesCustomMaxExcerptLenghViaHook() {
    $post_content_manager = new PostContentManager();
    $post = (object)array(
      'post_content' => '<p>one two three four five six</p>'
    );
    $excerpt = $post_content_manager->getContent($post, 'excerpt');
    expect($excerpt)->equals('one two three four five six');
    (new WPFunctions)->addFilter(
      'mailpoet_newsletter_post_excerpt_length',
      function() {
        return 2;
      }
    );
    $post_content_manager = new PostContentManager();
    $excerpt = $post_content_manager->getContent($post, 'excerpt');
    expect($excerpt)->equals('one two &hellip;');
  }

  function testItStripsShortcodesWhenGettingPostContent() {
    // shortcodes are stripped in excerpt
    $post = (object)array(
      'post_excerpt' => '[shortcode]some text in excerpt[/shortcode]'
    );
    expect($this->post_content->getContent($post, 'excerpt'))->equals('some text in excerpt');

    // shortcodes are stripped in post content when excerpt doesn't exist
    $post = (object)array(
      'post_content' => '[shortcode]some text in content[/shortcode]'
    );
    expect($this->post_content->getContent($post, 'excerpt'))->equals('some text in content');

    // shortcodes are stripped in post content
    $post = (object)array(
      'post_content' => '[shortcode]some text in content[/shortcode]'
    );
    expect($this->post_content->getContent($post, ''))->equals('some text in content');
  }

  function _after() {
  }
}
