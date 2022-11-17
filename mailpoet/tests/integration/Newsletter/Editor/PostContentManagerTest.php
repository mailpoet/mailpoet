<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter\Editor;

use MailPoet\Newsletter\Editor\PostContentManager;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class PostContentManagerTest extends \MailPoetTest {
  public $postContent;

  public function _before() {
    parent::_before();
    $this->postContent = new PostContentManager(
      $this->make(WooCommerceHelper::class, ['isWooCommerceActive' => false])
    );
  }

  public function testFilterContentRetainsStructuralTags() {
    $html = '<p>some paragraph text</p>';
    expect($this->postContent->filterContent($html, 'full'))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '">some paragraph text</p>'
    );

    $html = '<span>spanning</span>';
    expect($this->postContent->filterContent($html, 'full'))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '">' . $html . '</p>'
    );

    $html = '<blockquote>do not strip this</blockquote>';
    expect($this->postContent->filterContent($html, 'full'))->equals(
      '<blockquote><p class="' . PostContentManager::WP_POST_CLASS . '">do not strip this</p></blockquote>'
    );

    $html = '<ul><li>First item</li><li>Second item</li></ul>';
    expect($this->postContent->filterContent($html, 'full'))->equals(
      "<ul>\n<li>First item</li>\n<li>Second item</li>\n</ul>"
    );

    $html = '<ol><li>First item</li><li>Second item</li></ol>';
    expect($this->postContent->filterContent($html, 'full'))->equals(
      "<ol>\n<li>First item</li>\n<li>Second item</li>\n</ol>"
    );
  }

  public function testFilterContentRetainsHeadings() {
    $html = '<h1>heading 1</h1>';
    expect($this->postContent->filterContent($html, 'full'))->equals($html);

    $html = '<h2>heading 2</h2>';
    expect($this->postContent->filterContent($html, 'full'))->equals($html);

    $html = '<h3>heading 3</h3>';
    expect($this->postContent->filterContent($html, 'full'))->equals($html);

    $html = '<h1>heading 1</h1>';
    expect($this->postContent->filterContent($html, 'excerpt'))
      ->equals('<p class="' . PostContentManager::WP_POST_CLASS . '">heading 1</p>');

    $html = '<h2>heading 2</h2>';
    expect($this->postContent->filterContent($html, 'excerpt'))
      ->equals('<p class="' . PostContentManager::WP_POST_CLASS . '">heading 2</p>');

    $html = '<h3>heading 3</h3>';
    expect($this->postContent->filterContent($html, 'excerpt'))
      ->equals('<p class="' . PostContentManager::WP_POST_CLASS . '">heading 3</p>');
  }

  public function testFilterContentRetainsTextStyling() {
    $textTags = [
      '<em>emphasized></em>',
      '<b>bold</b>',
      '<strong>strong</strong>',
      '<i>italic</i>',
      'Text<br />new line',
    ];
    foreach ($textTags as $html) {
      expect($this->postContent->filterContent($html, 'full'))->equals(
        '<p class="' . PostContentManager::WP_POST_CLASS . '">' . $html . '</p>'
      );
    }
  }

  public function testFilterContentRetainsImagesAndLinks() {
    $html = '<img src="#" alt="some alt" />';
    expect($this->postContent->filterContent($html, 'full'))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '"><img src="#" alt="some alt" /></p>'
    );

    $html = '<a href="#" title="link title">some link</a>';
    expect($this->postContent->filterContent($html, 'full'))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '">' . $html . '</p>'
    );
  }

  public function testFilterContentStripsUndesirableTags() {
    $undesirableTags = [
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
    ];

    foreach ($undesirableTags as $html) {
      expect($this->postContent->filterContent($html, 'full'))->equals('');
    }
  }

  public function testFilterContentStripsUndesirableTagsForExcerpts() {
    $undesirableTags = [
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
    ];

    foreach ($undesirableTags as $html) {
      expect($this->postContent->filterContent($html, 'excerpt'))->equals('');
    }
  }

  public function testItAppliesCustomMaxExcerptLenghViaHook() {
    $postContentManager = new PostContentManager(
      $this->make(WooCommerceHelper::class, ['isWooCommerceActive' => false])
    );
    $post = (object)[
      'post_content' => '<p>one two three four five six</p>',
    ];
    $excerpt = $postContentManager->getContent($post, 'excerpt');
    expect($excerpt)->equals('one two three four five six');
    (new WPFunctions)->addFilter(
      'mailpoet_newsletter_post_excerpt_length',
      function() {
        return 2;
      }
    );
    $postContentManager = new PostContentManager(
      $this->make(WooCommerceHelper::class, ['isWooCommerceActive' => false])
    );
    $excerpt = $postContentManager->getContent($post, 'excerpt');
    expect($excerpt)->equals('one two &hellip;');
  }

  public function testItStripsShortcodesWhenGettingPostContent() {
    // shortcodes are stripped in excerpt
    $post = (object)[
      'post_excerpt' => '[shortcode]some text in excerpt[/shortcode]',
    ];
    expect($this->postContent->getContent($post, 'excerpt'))->equals('some text in excerpt');

    // shortcodes are stripped in post content when excerpt doesn't exist
    $post = (object)[
      'post_content' => '[shortcode]some text in content[/shortcode]',
    ];
    expect($this->postContent->getContent($post, 'excerpt'))->equals('some text in content');

    // shortcodes are stripped in post content
    $post = (object)[
      'post_content' => '[shortcode]some text in content[/shortcode]',
    ];
    expect($this->postContent->getContent($post, ''))->equals('some text in content');
  }

  public function testItRemovesImageCaptionsFromClassicEditorPosts() {
    $post = (object)[
      'post_content' => 'Text [caption id="attachment_23" align="alignnone" width="300"]<img class="size-medium wp-image-23" src="i.png" alt="Alt" width="300" height="300" />Caption[/caption] Text [caption id="attachment_23" align="alignnone" width="300"]<img class="size-medium wp-image-23" src="i.png" alt="Alt" width="300" height="300" />Caption[/caption] Text',
    ];
    expect($this->postContent->getContent($post, 'excerpt'))->equals('Text Text Text');
  }

  public function testItRemovesImageCaptionsFromGutenbergPosts() {
    $content = <<<EOT
      <!-- wp:paragraph -->
      <p>Text</p>
      <!-- /wp:paragraph -->
      <!-- wp:image {"id":25,"align":"center"} -->
      <div class="wp-block-image"><figure class="aligncenter"><img src="i.png" alt="Alt" class="wp-image-25"/><figcaption>Caption</figcaption></figure></div>
      <!-- /wp:image -->
      <!-- wp:paragraph -->
      <p>Text</p>
      <!-- /wp:paragraph -->
      <!-- wp:image {"id":25,"align":"center"} -->
      <div class="wp-block-image"><figure class="aligncenter"><img src="i.png" alt="Alt" class="wp-image-25"/><figcaption>Caption</figcaption></figure></div>
      <!-- /wp:image -->
      <!-- wp:paragraph -->
      <p>Text</p>
      <!-- /wp:paragraph -->
EOT;

    $post = (object)[
      'post_content' => $content,
    ];
    expect($this->postContent->getContent($post, 'excerpt'))->equals('Text Text Text');
  }

  public function testItReplaceParagraphClass(): void {
    $html = '<p class="has-text-align-left">some paragraph text</p>';
    expect($this->postContent->filterContent($html, 'full'))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '">some paragraph text</p>'
    );

    $html = '<p class="has-text-align-center"><span>some text</span></p>';
    expect($this->postContent->filterContent($html, 'excerpt'))
      ->equals('<p class="' . PostContentManager::WP_POST_CLASS . '"><span>some text</span></p>');
  }
}
