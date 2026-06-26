<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet\Blocks;

use MailPoet\EmailEditor\Integrations\MailPoet\Blocks\BlockTypes\LatestPosts;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Newsletter\NewsletterPostsRepository;
use MailPoet\Newsletter\Renderer\Renderer;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoet\WP\Functions as WPFunctions;

class LatestPostsTest extends \MailPoetTest {
  private LatestPosts $block;
  private WPFunctions $wp;
  private NewsletterPostsRepository $newsletterPostsRepository;

  /** @var int[] */
  private $postIds = [];

  /** @var \WP_Post|null */
  private $previousGlobalPost;

  public function _before(): void {
    parent::_before();
    $this->block = $this->diContainer->get(LatestPosts::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
    $this->newsletterPostsRepository = $this->diContainer->get(NewsletterPostsRepository::class);
    $globalPost = $GLOBALS['post'] ?? null;
    $this->previousGlobalPost = $globalPost instanceof \WP_Post ? $globalPost : null;

    foreach ($this->wp->getPosts(['post_type' => ['post', 'page', 'product'], 'post_status' => 'any', 'numberposts' => -1]) as $post) {
      $this->wp->wpDeletePost((int)$post->ID, true);
    }

    $this->postIds[] = $this->createPost('Latest posts block A', '2020-01-01 01:01:01');
    $this->postIds[] = $this->createPost('Latest posts block B', '2020-02-01 01:01:01');
    $this->postIds[] = $this->createPost('Latest posts block C', '2020-03-01 01:01:01');
  }

  public function _after(): void {
    foreach ($this->postIds as $postId) {
      $this->wp->wpDeletePost($postId, true);
    }
    $GLOBALS['post'] = $this->previousGlobalPost;
    parent::_after();
  }

  public function testItRendersLatestPosts(): void {
    $this->setCurrentEmailPostForNewsletter($this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD));

    $html = $this->render(['perPage' => 2]);

    verify($html)->stringContainsString('Latest posts block C');
    verify($html)->stringContainsString('Latest posts block B');
    verify($html)->stringNotContainsString('Latest posts block A');
  }

  public function testItRendersOnlyTheComposedInnerBlocks(): void {
    // Each render uses its own newsletter so cross-block deduplication does not
    // carry the selected post over between the two independent renders.
    $this->setCurrentEmailPostForNewsletter($this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD));
    $titleOnly = $this->render(['perPage' => 1], [], [$this->block('core/post-title')]);
    verify($titleOnly)->stringContainsString('Latest posts block C');
    verify($titleOnly)->stringNotContainsString('excerpt content');

    $this->setCurrentEmailPostForNewsletter($this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD));
    $withExcerpt = $this->render(['perPage' => 1], [], [
      $this->block('core/post-title'),
      $this->block('core/post-excerpt'),
    ]);
    verify($withExcerpt)->stringContainsString('Latest posts block C');
    verify($withExcerpt)->stringContainsString('excerpt content');
  }

  public function testItRendersDefaultLayoutWhenInsertedWithoutInnerBlocks(): void {
    $this->setCurrentEmailPostForNewsletter($this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD));

    // Patterns and templates insert the block as self-closing (no inner blocks).
    $html = $this->block->renderEmail('', [
      'blockName' => 'mailpoet/latest-posts',
      'attrs' => ['query' => ['perPage' => 2]],
      'innerBlocks' => [],
    ], null);

    verify($html)->stringContainsString('Latest posts block C');
    verify($html)->stringContainsString('Latest posts block B');
    verify($html)->stringContainsString('excerpt content');
  }

  public function testItDoesNotRepeatPostsAcrossBlocksInTheSameNewsletter(): void {
    $this->setCurrentEmailPostForNewsletter($this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD));

    $firstBlockHtml = $this->render(['perPage' => 1]);
    $secondBlockHtml = $this->render(['perPage' => 1]);

    verify($firstBlockHtml)->stringContainsString('Latest posts block C');
    verify($firstBlockHtml)->stringNotContainsString('Latest posts block B');
    verify($secondBlockHtml)->stringNotContainsString('Latest posts block C');
    verify($secondBlockHtml)->stringContainsString('Latest posts block B');
  }

  public function testItRendersOnlyPostsNewerThanLastSentForNotificationHistory(): void {
    $notification = $this->createBlockEmailNewsletter(NewsletterEntity::TYPE_NOTIFICATION);
    $history = $this->createBlockEmailNewsletter(NewsletterEntity::TYPE_NOTIFICATION_HISTORY, $notification);
    $this->setCurrentEmailPostForNewsletter($history);

    $newsletterPost = new NewsletterPostEntity($notification, $this->postIds[1]);
    $this->newsletterPostsRepository->persist($newsletterPost);
    $this->newsletterPostsRepository->flush();
    $newsletterPost->setCreatedAt(new \DateTime('2020-02-15 01:01:01'));
    $this->newsletterPostsRepository->flush();

    $html = $this->render(['perPage' => 3]);

    verify($html)->stringContainsString('Latest posts block C');
    verify($html)->stringNotContainsString('Latest posts block B');
    verify($html)->stringNotContainsString('Latest posts block A');
  }

  public function testItSupportsPages(): void {
    $this->postIds[] = $this->createPost('Latest pages block A', '2020-04-01 01:01:01', 'page');
    $this->setCurrentEmailPostForNewsletter($this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD));

    $html = $this->render(['postType' => 'page']);

    verify($html)->stringContainsString('Latest pages block A');
    verify($html)->stringNotContainsString('Latest posts block C');
  }

  public function testItSupportsCustomPostTypes(): void {
    $this->postIds[] = $this->createPost('Latest products block A', '2020-04-01 01:01:01', 'product');
    $this->setCurrentEmailPostForNewsletter($this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD));

    $html = $this->render(['postType' => 'product']);

    verify($html)->stringContainsString('Latest products block A');
    verify($html)->stringNotContainsString('Latest posts block C');
  }

  public function testItRespectsOldestOrder(): void {
    $this->setCurrentEmailPostForNewsletter($this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD));

    $html = $this->render(['perPage' => 1, 'order' => 'oldest']);

    verify($html)->stringContainsString('Latest posts block A');
    verify($html)->stringNotContainsString('Latest posts block C');
  }

  public function testItRendersPostsInColumns(): void {
    $this->setCurrentEmailPostForNewsletter($this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD));

    $html = $this->render(['perPage' => 2], ['columns' => 2]);

    // Columns are rendered with the core email Columns/Column renderers, so the
    // output matches how the editor renders a real core/columns block.
    verify($html)->stringContainsString('email-block-columns');
    verify(substr_count($html, 'email-block-column-content'))->equals(2);
    verify($html)->stringContainsString('Latest posts block C');
    verify($html)->stringContainsString('Latest posts block B');
  }

  public function testItIncludesPostsFromSelectedCategory(): void {
    $categoryId = $this->createTerm('Latest posts category', 'category');
    wp_set_object_terms($this->postIds[0], [$categoryId], 'category');
    $this->setCurrentEmailPostForNewsletter($this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD));

    $html = $this->render([
      'perPage' => 10,
      'terms' => [['taxonomy' => 'category', 'id' => $categoryId]],
      'inclusionType' => 'include',
    ]);

    verify($html)->stringContainsString('Latest posts block A');
    verify($html)->stringNotContainsString('Latest posts block B');
    verify($html)->stringNotContainsString('Latest posts block C');
  }

  public function testItExcludesPostsFromSelectedCategory(): void {
    $categoryId = $this->createTerm('Latest posts category', 'category');
    wp_set_object_terms($this->postIds[0], [$categoryId], 'category');
    $this->setCurrentEmailPostForNewsletter($this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD));

    $html = $this->render([
      'perPage' => 10,
      'terms' => [['taxonomy' => 'category', 'id' => $categoryId]],
      'inclusionType' => 'exclude',
    ]);

    verify($html)->stringNotContainsString('Latest posts block A');
    verify($html)->stringContainsString('Latest posts block B');
    verify($html)->stringContainsString('Latest posts block C');
  }

  public function testItRendersManuallySelectedPosts(): void {
    $this->setCurrentEmailPostForNewsletter($this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD));

    $html = $this->render([
      'selectionMode' => 'manual',
      'posts' => [$this->postIds[0]],
    ]);

    verify($html)->stringContainsString('Latest posts block A');
    verify($html)->stringNotContainsString('Latest posts block B');
    verify($html)->stringNotContainsString('Latest posts block C');
  }

  public function testItRendersNoPostsMessageForEmptyManualSelection(): void {
    $this->setCurrentEmailPostForNewsletter($this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD));

    $html = $this->render([
      'selectionMode' => 'manual',
      'posts' => [],
    ]);

    verify($html)->stringContainsString('No posts found.');
    verify($html)->stringNotContainsString('Latest posts block');
  }

  public function testItRendersFeaturedImageAsRegularImageBlock(): void {
    // core/post-featured-image renders as a bare <figure> with a fixed height
    // and object-fit:cover. It must be routed through the email image renderer
    // so it is table-wrapped and scales by width like a regular image block.
    [$attachmentId, $url] = $this->createAttachment();
    $figure = sprintf(
      '<figure class="wp-block-post-featured-image"><img width="600" height="400" src="%s" class="wp-post-image" alt="" style="object-fit:cover;" srcset="%s 600w" sizes="100vw" /></figure>',
      $url,
      $url
    );

    $html = $this->block->renderImageBlockForEmail($figure, [
      'blockName' => 'core/post-featured-image',
      'attrs' => ['id' => $attachmentId, 'sizeSlug' => 'full'],
      'email_attrs' => ['width' => '320px'],
    ], $this->renderingContext());

    verify($html)->stringContainsString('<table');
    verify($html)->stringNotContainsString('<figure');
    verify($html)->stringContainsString($url);

    $image = $this->getImageAttributes($html);
    // Filling the available width (capped by the 600px intrinsic size).
    verify($image['width'])->equals(320);
    // No fixed height + no object-fit means it keeps its natural aspect ratio.
    verify($image['height'])->null();
    verify((string)$image['style'])->stringNotContainsString('object-fit');

    wp_delete_post($attachmentId, true);
  }

  public function testItRendersAvatarAsRegularImageBlockKeepingItsSize(): void {
    // core/avatar renders as a <div><img> at a fixed pixel size. It must be
    // table-wrapped like an image while keeping that size (not stretched).
    $avatar = '<div class="wp-block-avatar">'
      . '<img src="http://example.com/avatar.jpg" alt="" class="avatar" height="96" width="96" />'
      . '</div>';

    $html = $this->block->renderImageBlockForEmail($avatar, [
      'blockName' => 'core/avatar',
      'attrs' => [],
      'email_attrs' => ['width' => '600px'],
    ], $this->renderingContext());

    verify($html)->stringContainsString('<table');
    verify($html)->stringNotContainsString('<div class="wp-block-avatar"');

    $image = $this->getImageAttributes($html);
    verify($image['width'])->equals(96);
    verify($image['height'])->null();
  }

  public function testItAppliesTheBlocksOwnTopMarginToTheFirstRow(): void {
    // The block itself is a render_email_callback, so the engine never wraps it
    // in add_spacer. We carry the block's engine-assigned margin-top onto the
    // first row: present when a block precedes us, absent when we are first.
    $template = '<!-- wp:mailpoet/latest-posts {"query":{"perPage":1},"displayLayout":{"columns":1}} -->'
      . '<!-- wp:mailpoet/latest-posts-template --><!-- wp:post-title /--><!-- /wp:mailpoet/latest-posts-template -->'
      . '<!-- /wp:mailpoet/latest-posts -->';

    $whenFirst = $this->getRenderedBlockOutput($template);
    $whenPreceded = $this->getRenderedBlockOutput('<!-- wp:paragraph --><p>Intro</p><!-- /wp:paragraph -->' . $template);

    verify($this->firstLayoutStyle($whenFirst))->stringNotContainsString('margin-top');
    verify($this->firstLayoutStyle($whenPreceded))->stringContainsString('margin-top');
  }

  /**
   * Renders an email and returns the raw HTML our latest-posts block produced
   * (captured before CSS inlining via the engine's render_block filter).
   */
  private function getRenderedBlockOutput(string $content): string {
    $captured = '';
    $capture = static function ($blockContent, $parsedBlock) use (&$captured) {
      if (($parsedBlock['blockName'] ?? '') === 'mailpoet/latest-posts') {
        $captured = (string)$blockContent;
      }
      return $blockContent;
    };
    add_filter('render_block', $capture, 11, 2);
    try {
      $newsletter = $this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD, null, $content);
      $this->diContainer->get(Renderer::class)->renderAsPreview($newsletter);
    } finally {
      remove_filter('render_block', $capture, 11);
    }
    return $captured;
  }

  private function firstLayoutStyle(string $html): string {
    $processor = new \WP_HTML_Tag_Processor($html);
    while ($processor->next_tag('div')) {
      $class = (string)$processor->get_attribute('class');
      if (strpos($class, 'email-block-layout') !== false) {
        return (string)$processor->get_attribute('style');
      }
    }
    return '';
  }

  public function testItSpacesRowsUsingTheEngineLayoutSpacerNotABespokeMargin(): void {
    // Posts/rows must be separated with the engine's own add_spacer output
    // (`email-block-layout` margin-top), like every other block, not a custom
    // margin wrapper.
    $content = '<!-- wp:mailpoet/latest-posts {"query":{"perPage":3},"displayLayout":{"columns":1}} -->'
      . '<!-- wp:mailpoet/latest-posts-template --><!-- wp:post-title /--><!-- /wp:mailpoet/latest-posts-template -->'
      . '<!-- /wp:mailpoet/latest-posts -->';
    $newsletter = $this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD, null, $content);

    $rendered = $this->diContainer->get(Renderer::class)->renderAsPreview($newsletter);
    $this->assertIsArray($rendered);
    $html = $rendered['html'] ?? '';
    $this->assertIsString($html);

    verify($html)->stringNotContainsString('margin:0 0');
    verify($html)->stringContainsString('email-block-layout');
    verify($html)->stringContainsString('margin-top');
  }

  public function testItFitsColumnsAndGapWithinTheAvailableWidth(): void {
    // The engine applies the column gap as padding on top of the column widths,
    // which would overflow the row. We pre-shrink the split so the columns plus
    // the gap add up to the available width: the single-column image width.
    [$attachmentId] = $this->createAttachment();
    set_post_thumbnail($this->postIds[1], $attachmentId);
    set_post_thumbnail($this->postIds[2], $attachmentId);
    $this->setCurrentEmailPostForNewsletter($this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD));

    $singleColumn = $this->render(['perPage' => 1], ['columns' => 1], [$this->block('core/post-featured-image', ['sizeSlug' => 'full'])]);
    $available = $this->getImageAttributes($singleColumn)['width'];
    $this->assertIsInt($available);

    $twoColumns = $this->render(['perPage' => 2], ['columns' => 2], [$this->block('core/post-featured-image', ['sizeSlug' => 'full'])]);
    $layout = $this->getColumnLayout($twoColumns);

    verify(count($layout['widths']))->equals(2);
    verify($layout['widths'][0])->equals($layout['widths'][1]);
    verify($layout['gap'])->greaterThan(0);
    // Columns + the inter-column gap must not exceed the available width.
    verify($layout['widths'][0] + $layout['widths'][1] + $layout['gap'])->lessThanOrEqual($available);

    wp_delete_post($attachmentId, true);
  }

  public function testItRendersFeaturedImageAtFullWidthInSingleColumn(): void {
    // In a single-column layout the featured image fills the whole content
    // width (capped only by its 600px intrinsic size).
    [$attachmentId] = $this->createAttachment();
    set_post_thumbnail($this->postIds[2], $attachmentId);

    $content = '<!-- wp:mailpoet/latest-posts {"query":{"perPage":1},"displayLayout":{"columns":1}} -->'
      . '<!-- wp:mailpoet/latest-posts-template -->'
      . '<!-- wp:post-featured-image {"sizeSlug":"full"} /-->'
      . '<!-- wp:post-title /-->'
      . '<!-- /wp:mailpoet/latest-posts-template -->'
      . '<!-- /wp:mailpoet/latest-posts -->';
    $newsletter = $this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD, null, $content);

    $rendered = $this->diContainer->get(Renderer::class)->renderAsPreview($newsletter);
    $this->assertIsArray($rendered);
    $html = $rendered['html'] ?? '';
    $this->assertIsString($html);

    $src = wp_get_attachment_image_url($attachmentId, 'full');
    $this->assertIsString($src);
    $image = $this->getImageAttributesBySrc($html, basename($src));
    $this->assertNotNull($image);
    $this->assertNotNull($image['width']);
    // Single column: image spans the full content width, well above a column.
    verify($image['width'])->greaterThan(400);
    verify($image['height'])->null();

    wp_delete_post($attachmentId, true);
  }

  public function testItConstrainsFeaturedImageToColumnWidthInRenderedEmail(): void {
    // In a multi-column layout the featured image must be capped to the column
    // width, not the full email width, or it overflows the email container.
    [$attachmentId] = $this->createAttachment();
    set_post_thumbnail($this->postIds[2], $attachmentId);

    $content = '<!-- wp:mailpoet/latest-posts {"query":{"perPage":1},"displayLayout":{"columns":2}} -->'
      . '<!-- wp:mailpoet/latest-posts-template -->'
      . '<!-- wp:post-featured-image {"sizeSlug":"full"} /-->'
      . '<!-- wp:post-title /-->'
      . '<!-- /wp:mailpoet/latest-posts-template -->'
      . '<!-- /wp:mailpoet/latest-posts -->';
    $newsletter = $this->createBlockEmailNewsletter(NewsletterEntity::TYPE_STANDARD, null, $content);

    $rendered = $this->diContainer->get(Renderer::class)->renderAsPreview($newsletter);
    $this->assertIsArray($rendered);
    $html = $rendered['html'] ?? '';
    $this->assertIsString($html);

    $src = wp_get_attachment_image_url($attachmentId, 'full');
    $this->assertIsString($src);
    $image = $this->getImageAttributesBySrc($html, basename($src));
    $this->assertNotNull($image);
    $this->assertNotNull($image['width']);
    // Content width 600px in two columns leaves ~290px per column.
    verify($image['width'])->lessThanOrEqual(320);
    // No fixed height keeps the natural aspect ratio.
    verify($image['height'])->null();

    wp_delete_post($attachmentId, true);
  }

  public function testItRewritesLinkColorSelectorSoItCanBeInlined(): void {
    // WordPress styles link colors with a `:where()` selector the email CSS
    // inliner cannot parse; it must be rewritten to a `:not()` equivalent.
    $styles = '.wp-elements-abc a:where(:not(.wp-element-button)){color:#ff0000;}';

    $result = $this->block->makeLinkColorStylesInlineable($styles);

    verify($result)->stringContainsString('a:not(.wp-element-button)');
    verify($result)->stringNotContainsString(':where(');
  }

  /**
   * @param array<string, mixed> $query
   * @param array<string, mixed> $displayLayout
   * @param array<int, array<string, mixed>> $templateBlocks
   */
  private function render(array $query = [], array $displayLayout = [], array $templateBlocks = []): string {
    if (!$templateBlocks) {
      $templateBlocks = [$this->block('core/post-title')];
    }

    $parsedBlock = [
      'blockName' => 'mailpoet/latest-posts',
      'attrs' => [
        'query' => $query,
        'displayLayout' => $displayLayout,
      ],
      'innerHTML' => '',
      'innerContent' => [],
      'innerBlocks' => [
        [
          'blockName' => 'mailpoet/latest-posts-template',
          'attrs' => [],
          'innerHTML' => '',
          'innerContent' => [],
          'innerBlocks' => $templateBlocks,
        ],
      ],
    ];

    return $this->block->renderEmail('', $parsedBlock, null);
  }

  /**
   * @param array<string, mixed> $attrs
   * @return array<string, mixed>
   */
  private function block(string $blockName, array $attrs = []): array {
    return [
      'blockName' => $blockName,
      'attrs' => $attrs,
      'innerHTML' => '',
      'innerContent' => [],
      'innerBlocks' => [],
    ];
  }

  private function createTerm(string $name, string $taxonomy): int {
    $term = wp_insert_term($name, $taxonomy);
    if ($term instanceof \WP_Error) {
      $existingId = $term->get_error_data('term_exists');
      $this->assertIsNumeric($existingId);
      return (int)$existingId;
    }
    $this->assertIsArray($term);
    return (int)$term['term_id'];
  }

  private function createPost(string $title, string $publishDate, string $postType = 'post'): int {
    return $this->wp->wpInsertPost([
      'post_title' => $title,
      'post_content' => $title . ' excerpt content',
      'post_status' => 'publish',
      'post_date' => $publishDate,
      'post_date_gmt' => $this->wp->getGmtFromDate($publishDate),
      'post_type' => $postType,
    ]);
  }

  private function createBlockEmailNewsletter(string $type, ?NewsletterEntity $parent = null, string $content = '<!-- wp:mailpoet/latest-posts /-->'): NewsletterEntity {
    $postId = $this->wp->wpInsertPost([
      'post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE,
      'post_status' => 'publish',
      'post_title' => 'Latest posts email',
      'post_content' => $content,
    ]);
    $this->assertGreaterThan(0, $postId);
    $this->postIds[] = $postId;

    $factory = (new NewsletterFactory())
      ->withType($type)
      ->withStatus(NewsletterEntity::STATUS_ACTIVE)
      ->withWpPostId($postId);

    if ($parent instanceof NewsletterEntity) {
      $factory->withParent($parent);
    }

    return $factory->create();
  }

  private function setCurrentEmailPostForNewsletter(NewsletterEntity $newsletter): void {
    $wpPostId = $newsletter->getWpPostId();
    $this->assertIsInt($wpPostId);
    $post = $this->wp->getPost($wpPostId);
    $this->assertInstanceOf(\WP_Post::class, $post);
    $GLOBALS['post'] = $post;
  }

  /**
   * @return array{0: int, 1: string}
   */
  private function createAttachment(): array {
    $filename = dirname(__DIR__, 5) . '/_data/600x400.jpg';
    $contents = file_get_contents($filename);
    $this->assertIsString($contents);
    $upload = wp_upload_bits(basename($filename), null, $contents);
    $this->assertEmpty($upload['error']);

    $attachmentId = wp_insert_attachment([
      'post_title' => basename($upload['file']),
      'post_content' => '',
      'post_type' => 'attachment',
      'post_mime_type' => 'image/jpeg',
      'guid' => $upload['url'],
    ], $upload['file']);
    $this->assertIsInt($attachmentId);
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $metadata = wp_generate_attachment_metadata($attachmentId, $upload['file']);
    wp_update_attachment_metadata($attachmentId, $metadata);

    return [$attachmentId, $upload['url']];
  }

  private function renderingContext(): \Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context {
    return new \Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context(new \WP_Theme_JSON());
  }

  /**
   * Returns the numeric width/height of the first <img> whose src contains the
   * given needle. A null value means the attribute is absent.
   *
   * @return array{width: ?int, height: ?int}|null
   */
  private function getImageAttributesBySrc(string $html, string $srcNeedle): ?array {
    $processor = new \WP_HTML_Tag_Processor($html);
    while ($processor->next_tag('img')) {
      $src = $processor->get_attribute('src');
      if (!is_string($src) || strpos($src, $srcNeedle) === false) {
        continue;
      }
      $width = $processor->get_attribute('width');
      $height = $processor->get_attribute('height');
      return [
        'width' => (is_string($width) && is_numeric($width)) ? (int)$width : null,
        'height' => (is_string($height) && is_numeric($height)) ? (int)$height : null,
      ];
    }
    return null;
  }

  /**
   * Reads the per-column cell widths and the inter-column gap (padding-left of
   * the non-first columns) from a rendered core/columns row.
   *
   * @return array{widths: int[], gap: int}
   */
  private function getColumnLayout(string $html): array {
    $widths = [];
    $gap = 0;
    $processor = new \WP_HTML_Tag_Processor($html);
    while ($processor->next_tag('td')) {
      $class = (string)$processor->get_attribute('class');
      if (strpos($class, 'email-block-column') === false || strpos($class, 'content') !== false) {
        continue;
      }
      $width = $processor->get_attribute('width');
      $widths[] = (is_string($width) && is_numeric($width)) ? (int)$width : 0;

      $style = (string)$processor->get_attribute('style');
      foreach (explode(';', $style) as $declaration) {
        [$property, $value] = array_pad(explode(':', $declaration, 2), 2, '');
        if (trim($property) === 'padding-left') {
          $gap = max($gap, (int)round((float)str_replace('px', '', trim($value))));
        }
      }
    }
    return ['widths' => $widths, 'gap' => $gap];
  }

  /**
   * Returns the numeric width/height attributes and style of the first <img>.
   * A null width/height means the attribute is absent.
   *
   * @return array{width: ?int, height: ?int, style: ?string}
   */
  private function getImageAttributes(string $html): array {
    $processor = new \WP_HTML_Tag_Processor($html);
    if (!$processor->next_tag('img')) {
      return ['width' => null, 'height' => null, 'style' => null];
    }
    $width = $processor->get_attribute('width');
    $height = $processor->get_attribute('height');
    $style = $processor->get_attribute('style');
    return [
      'width' => (is_string($width) && is_numeric($width)) ? (int)$width : null,
      'height' => (is_string($height) && is_numeric($height)) ? (int)$height : null,
      'style' => is_string($style) ? $style : null,
    ];
  }
}
