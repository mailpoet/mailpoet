<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter\Editor;

use MailPoet\Newsletter\Editor\PostContentManager;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;

class PostContentManagerTest extends \MailPoetTest {
  public $postContent;

  /** @var int[] */
  private $postIds = [];

  public function _before() {
    parent::_before();
    $this->postContent = new PostContentManager(
      $this->make(WooCommerceHelper::class, ['isWooCommerceActive' => false])
    );
  }

  public function testFilterContentRetainsStructuralTags() {
    $html = '<p>some paragraph text</p>';
    verify($this->postContent->filterContent($html, 'full'))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '">some paragraph text</p>'
    );

    $html = '<span>spanning</span>';
    verify($this->postContent->filterContent($html, 'full'))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '">' . $html . '</p>'
    );

    $html = '<blockquote>do not strip this</blockquote>';
    verify($this->postContent->filterContent($html, 'full'))->equals(
      '<blockquote><p class="' . PostContentManager::WP_POST_CLASS . '">do not strip this</p></blockquote>'
    );

    $html = '<ul><li>First item</li><li>Second item</li></ul>';
    verify($this->postContent->filterContent($html, 'full'))->equals(
      "<ul>\n<li>First item</li>\n<li>Second item</li>\n</ul>"
    );

    $html = '<ol><li>First item</li><li>Second item</li></ol>';
    verify($this->postContent->filterContent($html, 'full'))->equals(
      "<ol>\n<li>First item</li>\n<li>Second item</li>\n</ol>"
    );
  }

  public function testFilterContentRetainsHeadings() {
    $html = '<h1>heading 1</h1>';
    verify($this->postContent->filterContent($html, 'full'))->equals($html);

    $html = '<h2>heading 2</h2>';
    verify($this->postContent->filterContent($html, 'full'))->equals($html);

    $html = '<h3>heading 3</h3>';
    verify($this->postContent->filterContent($html, 'full'))->equals($html);

    $html = '<h1>heading 1</h1>';
    verify($this->postContent->filterContent($html, 'excerpt'))
      ->equals('<p class="' . PostContentManager::WP_POST_CLASS . '">heading 1</p>');

    $html = '<h2>heading 2</h2>';
    verify($this->postContent->filterContent($html, 'excerpt'))
      ->equals('<p class="' . PostContentManager::WP_POST_CLASS . '">heading 2</p>');

    $html = '<h3>heading 3</h3>';
    verify($this->postContent->filterContent($html, 'excerpt'))
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
      verify($this->postContent->filterContent($html, 'full'))->equals(
        '<p class="' . PostContentManager::WP_POST_CLASS . '">' . $html . '</p>'
      );
    }
  }

  public function testFilterContentRetainsImagesAndLinks() {
    $html = '<img src="#" alt="some alt" />';
    verify($this->postContent->filterContent($html, 'full'))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '"><img src="#" alt="some alt" /></p>'
    );

    $html = '<a href="#" title="link title">some link</a>';
    verify($this->postContent->filterContent($html, 'full'))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '">' . $html . '</p>'
    );
  }

  public function testFilterContentStripsUndesirableTags() {
    $undesirableTags = [
      '<embed src="#" />',
      '<iframe src="#" />',
      '<form></form>',
      '<input type="text" />',
      '<script></script>',
      '<style></style>',
      '<table></table>',
      '<tr></tr>',
      '<td></td>',
    ];

    foreach ($undesirableTags as $html) {
      verify($this->postContent->filterContent($html, 'full'))->equals('');
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
      verify($this->postContent->filterContent($html, 'excerpt'))->equals('');
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
    verify($excerpt)->equals('one two three four five six');
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
    verify($excerpt)->equals('one two &hellip;');
  }

  public function testItStripsShortcodesWhenGettingPostContent() {
    // shortcodes are stripped in excerpt
    $post = (object)[
      'post_excerpt' => '[shortcode]some text in excerpt[/shortcode]',
    ];
    verify($this->postContent->getContent($post, 'excerpt'))->equals('some text in excerpt');

    // shortcodes are stripped in post content when excerpt doesn't exist
    $post = (object)[
      'post_content' => '[shortcode]some text in content[/shortcode]',
    ];
    verify($this->postContent->getContent($post, 'excerpt'))->equals('some text in content');

    // shortcodes are stripped in post content
    $post = (object)[
      'post_content' => '[shortcode]some text in content[/shortcode]',
    ];
    verify($this->postContent->getContent($post, ''))->equals('some text in content');
  }

  public function testItRemovesImageCaptionsFromClassicEditorPosts() {
    $post = (object)[
      'post_content' => 'Text [caption id="attachment_23" align="alignnone" width="300"]<img class="size-medium wp-image-23" src="i.png" alt="Alt" width="300" height="300" />Caption[/caption] Text [caption id="attachment_23" align="alignnone" width="300"]<img class="size-medium wp-image-23" src="i.png" alt="Alt" width="300" height="300" />Caption[/caption] Text',
    ];
    verify($this->postContent->getContent($post, 'excerpt'))->equals('Text Text Text');
  }

  public function testItRemovesImageCaptionsFromGutenbergPosts() {
    $content = <<<'EOT'
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
    verify($this->postContent->getContent($post, 'excerpt'))->equals('Text Text Text');
  }

  public function testItReplaceParagraphClass(): void {
    $html = '<p class="has-text-align-left">some paragraph text</p>';
    verify($this->postContent->filterContent($html, 'full'))->equals(
      '<p class="' . PostContentManager::WP_POST_CLASS . '">some paragraph text</p>'
    );

    $html = '<p class="has-text-align-center"><span>some text</span></p>';
    verify($this->postContent->filterContent($html, 'excerpt'))
      ->equals('<p class="' . PostContentManager::WP_POST_CLASS . '"><span>some text</span></p>');
  }

  public function testItFixesAnchorOnlyLinks(): void {
    $post = (object)[
      'ID' => 123,
      'post_content' => '<p>Read more in <a href="#section1">Section 1</a> and <a href="#section-2">Section 2</a>.</p>',
    ];

    $wpFunctions = $this->make(WPFunctions::class, [
      'getPermalink' => 'https://example.com/test-post/',
    ]);

    $postContentManager = new PostContentManager(
      $this->make(WooCommerceHelper::class, ['isWooCommerceActive' => false]),
      $wpFunctions
    );

    $content = $postContentManager->getContent($post, 'full');

    // Verify that anchor-only links are converted to full URLs
    verify($content)->stringContainsString('https://example.com/test-post/#section1');
    verify($content)->stringContainsString('https://example.com/test-post/#section-2');
    verify($content)->stringNotContainsString('href="#section1"');
    verify($content)->stringNotContainsString('href="#section-2"');
  }

  public function testItReturnsNoContentForPasswordProtectedPosts(): void {
    $postId = wp_insert_post([
      'post_title' => 'Protected post',
      'post_content' => '<p>Protected body</p>',
      'post_status' => 'publish',
      'post_password' => 'secret',
    ]);
    $this->postIds[] = $postId;
    $post = get_post($postId);

    verify($this->postContent->getContent($post, 'full'))->equals('');
    verify($this->postContent->getContent($post, 'excerpt'))->equals('');

    wp_update_post(['ID' => $postId, 'post_excerpt' => 'Protected excerpt']);
    verify($this->postContent->getContent(get_post($postId), 'excerpt'))->equals('');
  }

  public function testItReturnsNoContentForPasswordProtectedPostsWhenPasswordCookieIsSet(): void {
    $postId = wp_insert_post([
      'post_title' => 'Protected post',
      'post_content' => '<p>Protected body</p>',
      'post_status' => 'publish',
      'post_password' => 'secret',
    ]);
    $this->postIds[] = $postId;

    require_once ABSPATH . WPINC . '/class-phpass.php';
    $cookieName = 'wp-postpass_' . constant('COOKIEHASH');
    $_COOKIE[$cookieName] = (new \PasswordHash(8, true))->HashPassword('secret');
    try {
      verify(post_password_required($postId))->false();
      verify($this->postContent->getContent(get_post($postId), 'full'))->equals('');
    } finally {
      unset($_COOKIE[$cookieName]);
    }
  }

  public function testItReturnsContentForPostsWithoutPassword(): void {
    $postId = wp_insert_post([
      'post_title' => 'Public post',
      'post_content' => '<p>Public body</p>',
      'post_status' => 'publish',
    ]);
    $this->postIds[] = $postId;

    verify($this->postContent->getContent(get_post($postId), 'full'))->equals('<p>Public body</p>');
  }

  /**
   * @group woo
   */
  public function testItReturnsNoContentForPasswordProtectedProducts(): void {
    $product = new \WC_Product_Simple();
    $product->set_name('Protected product');
    $product->set_description('Protected description');
    $product->set_short_description('Protected short description');
    $product->set_status('publish');
    $product->save();
    $this->postIds[] = $product->get_id();
    wp_update_post(['ID' => $product->get_id(), 'post_password' => 'secret']);
    $product = wc_get_product($product->get_id());
    $this->assertInstanceOf(\WC_Product::class, $product);

    $postContent = new PostContentManager(new WooCommerceHelper(new WPFunctions()));

    verify($postContent->getContent($product, 'full'))->equals('');
    verify($postContent->getContent($product, 'excerpt'))->equals('');
    verify($postContent->getContent(get_post($product->get_id()), 'full'))->equals('');
    verify($postContent->getContent(get_post($product->get_id()), 'excerpt'))->equals('');
  }

  public function testItReturnsContentForPasswordProtectedPostsWhenAllowedViaHook(): void {
    $postId = wp_insert_post([
      'post_title' => 'Protected post',
      'post_content' => '<p>Protected body</p>',
      'post_status' => 'publish',
      'post_password' => 'secret',
    ]);
    $this->postIds[] = $postId;

    $filteredPostIds = [];
    $filter = function ($show, $post) use (&$filteredPostIds) {
      $filteredPostIds[] = $post->ID;
      return true;
    };
    add_filter('mailpoet_newsletter_show_password_protected_post_content', $filter, 10, 2);
    try {
      verify($this->postContent->getContent(get_post($postId), 'full'))->equals('<p>Protected body</p>');
      verify($filteredPostIds)->equals([$postId]);
    } finally {
      remove_filter('mailpoet_newsletter_show_password_protected_post_content', $filter, 10);
    }
  }

  public function _after() {
    foreach ($this->postIds as $postId) {
      wp_delete_post($postId, true);
    }
    parent::_after();
  }
}
