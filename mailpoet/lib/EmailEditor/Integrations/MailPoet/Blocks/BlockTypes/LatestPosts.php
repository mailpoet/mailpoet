<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Blocks\BlockTypes;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Process_Manager;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Column as ColumnRenderer;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Columns as ColumnsRenderer;
use Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks\Image as ImageRenderer;
use MailPoet\Config\Env;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Newsletter\AutomatedLatestContent;
use MailPoet\Newsletter\BlockPostQuery;
use MailPoet\Newsletter\NewsletterPostsRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Renders the MailPoet "Latest posts" block for the email editor.
 *
 * There are two blocks working together here: the container (mailpoet/latest-posts)
 * is where you pick which posts to show, and it wraps a template block
 * (mailpoet/latest-posts-template) that defines how a single post looks. When we
 * render, we fetch the posts and repeat the template's inner blocks for each one.
 */
class LatestPosts extends AbstractBlock {
  protected $blockName = 'latest-posts';

  private const TEMPLATE_BLOCK = 'mailpoet/latest-posts-template';
  private const POST_CONTENT_BLOCK = 'mailpoet/post-content';
  private const DEFAULT_COLUMNS = 1;
  private const MAX_COLUMNS = 2;
  private const DEFAULT_POSTS = 3;
  private const MAX_POSTS = 100;
  private const DEFAULT_BLOCK_GAP = '20px';
  private const DEFAULT_CONTENT_WIDTH_PX = 600;

  /** Template blocks. Not email-enabled by default, so we opt them in. */
  private const TEMPLATE_CORE_BLOCKS = [
    'core/post-featured-image',
    'core/post-title',
    'core/post-excerpt',
    'core/post-date',
    'core/post-author',
    'core/post-author-name',
    'core/post-author-biography',
    'core/post-terms',
    'core/avatar',
    'core/read-more',
  ];

  /**
   * Image blocks core renders as a bare <figure>/<img> that email clients
   * handle poorly. We route them through the core/image renderer for
   * table-wrapped, properly sized output.
   */
  private const IMAGE_CORE_BLOCKS = [
    'core/post-featured-image',
    'core/avatar',
  ];

  /**
   * HTML kept when rendering classic (non-block) post content.
   * Text-level markup email clients handle well.
   * Everything else (scripts, embeds, forms, ...) is stripped.
   */
  private const CLASSIC_CONTENT_ALLOWED_HTML = [
    'a' => ['href' => true, 'title' => true],
    'b' => [],
    'blockquote' => ['cite' => true],
    'br' => [],
    'cite' => [],
    'code' => [],
    'em' => [],
    'figcaption' => [],
    'figure' => [],
    'h1' => [],
    'h2' => [],
    'h3' => [],
    'h4' => [],
    'h5' => [],
    'h6' => [],
    'hr' => [],
    'i' => [],
    'img' => ['src' => true, 'alt' => true, 'width' => true, 'height' => true],
    'li' => [],
    'ol' => ['start' => true],
    'p' => [],
    'pre' => [],
    's' => [],
    'span' => [],
    'strong' => [],
    'table' => [],
    'tbody' => [],
    'td' => ['colspan' => true, 'rowspan' => true],
    'tfoot' => [],
    'th' => ['colspan' => true, 'rowspan' => true],
    'thead' => [],
    'tr' => [],
    'u' => [],
    'ul' => [],
  ];

  /** @var array<int, int[]> */
  private $renderedPostsByRequest = [];

  private bool $isRenderingPostContent = false;

  private AutomatedLatestContent $automatedLatestContent;
  private NewsletterPostsRepository $newsletterPostsRepository;
  private NewslettersRepository $newslettersRepository;
  private WPFunctions $wp;

  public function __construct(
    AutomatedLatestContent $automatedLatestContent,
    NewsletterPostsRepository $newsletterPostsRepository,
    NewslettersRepository $newslettersRepository,
    WPFunctions $wp
  ) {
    $this->automatedLatestContent = $automatedLatestContent;
    $this->newsletterPostsRepository = $newsletterPostsRepository;
    $this->newslettersRepository = $newslettersRepository;
    $this->wp = $wp;
  }

  public function initialize() {
    parent::initialize();
    $this->registerTemplateBlock();
    $this->registerPostContentBlock();
    $this->wp->addFilter('block_categories_all', [$this, 'addBlockCategory']);
    $this->wp->addFilter('allowed_block_types_all', [$this, 'restrictBlocksToEmailEditor'], 10, 2);
    $this->wp->addFilter('block_type_metadata_settings', [$this, 'enableEmailSupportForCoreBlocks']);
    $this->wp->addFilter('woocommerce_email_content_renderer_styles', [$this, 'makeLinkColorStylesInlineable']);
    $this->enableEmailSupportForRegisteredCoreBlocks();
    $this->registerEmailRenderCallback();
  }

  /**
   * Email-only block: renders nothing outside the email pipeline, where
   * renderEmail() takes over.
   *
   * @param array<string, mixed>|\WP_Block $attributes
   */
  public function render($attributes, $content, $block) {
    return '';
  }

  /**
   * @param array<int, array<string, mixed>> $categories
   * @return array<int, array<string, mixed>>
   */
  public function addBlockCategory(array $categories): array {
    foreach ($categories as $category) {
      if (($category['slug'] ?? null) === 'mailpoet') {
        return $categories;
      }
    }
    $categories[] = [
      'slug' => 'mailpoet',
      'title' => __('MailPoet', 'mailpoet'),
    ];
    return $categories;
  }

  /**
   * The block is registered globally so it can render when emails are sent, but
   * it is only meant to be used inside the MailPoet email editor. Hide it (and
   * its template block) from the inserter of every other editor.
   *
   * @param bool|string[] $allowedBlocks
   * @param \WP_Block_Editor_Context $editorContext
   * @return bool|string[]
   */
  public function restrictBlocksToEmailEditor($allowedBlocks, $editorContext) {
    $post = $editorContext->post ?? null;
    $postType = $post instanceof \WP_Post ? $post->post_type : null; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    if ($postType === EmailEditor::MAILPOET_EMAIL_POST_TYPE) {
      return $allowedBlocks;
    }

    // When every block is allowed the value is `true` rather than a list. Build
    // the full list of block names first so we have something to remove ours from.
    if (!is_array($allowedBlocks)) {
      $allowedBlocks = array_keys(\WP_Block_Type_Registry::get_instance()->get_all_registered());
    }

    $ownBlocks = [$this->getBlockType(), self::TEMPLATE_BLOCK, self::POST_CONTENT_BLOCK];
    return array_values(array_filter($allowedBlocks, static function ($blockName) use ($ownBlocks) {
      return !in_array($blockName, $ownBlocks, true);
    }));
  }

  /**
   * The email CSS inliner (Emogrifier) cannot parse the `:where()` selector
   * WordPress uses for element link colors (e.g. the "Read more" link), so the
   * colour is dropped from the sent email. Rewriting it to the equivalent
   * `:not()` keeps the same meaning while being inlineable.
   */
  public function makeLinkColorStylesInlineable(string $styles): string {
    return str_replace(':where(:not(.wp-element-button))', ':not(.wp-element-button)', $styles);
  }

  /**
   * @param array<string, mixed> $settings
   * @return array<string, mixed>
   */
  public function enableEmailSupportForCoreBlocks(array $settings): array {
    $name = isset($settings['name']) && is_string($settings['name']) ? $settings['name'] : '';
    if (in_array($name, self::TEMPLATE_CORE_BLOCKS, true)) {
      if (!isset($settings['supports']) || !is_array($settings['supports'])) {
        $settings['supports'] = [];
      }
      $settings['supports']['email'] = true;
    }
    if (in_array($name, self::IMAGE_CORE_BLOCKS, true)) {
      $settings['render_email_callback'] = [$this, 'renderImageBlockForEmail'];
    }
    return $settings;
  }

  /**
   * @param array<string, mixed> $parsedBlock
   */
  public function renderEmail(string $blockContent, array $parsedBlock, ?Rendering_Context $renderingContext = null): string {
    $attrs = isset($parsedBlock['attrs']) && is_array($parsedBlock['attrs']) ? $parsedBlock['attrs'] : [];
    $query = isset($attrs['query']) && is_array($attrs['query']) ? $attrs['query'] : [];

    $innerBlocks = $this->getTemplateInnerBlocks($parsedBlock);

    $posts = $this->getPosts($query);
    if (!$posts) {
      return $this->renderNoPostsMessage();
    }

    $availableWidth = $this->getAvailableWidth($parsedBlock, $renderingContext);

    // render_email_callback blocks skip add_spacer, so the margin-top the engine
    // assigned us (spacing from the block above) is never rendered. We apply it
    // to the first row ourselves.
    $blockMarginTop = $this->getBlockMarginTop($parsedBlock);

    return $this->renderPosts($posts, $innerBlocks, $this->getColumns($attrs), $renderingContext, $availableWidth, $blockMarginTop);
  }

  /**
   * Renders an image block (featured image, avatar) like core/image for email:
   * a clean <figure><img> passed through the email image renderer.
   *
   * @param array<string, mixed> $parsedBlock
   */
  public function renderImageBlockForEmail(string $blockContent, array $parsedBlock, Rendering_Context $renderingContext): string {
    if (trim($blockContent) === '') {
      return '';
    }

    $blockName = isset($parsedBlock['blockName']) && is_string($parsedBlock['blockName']) ? $parsedBlock['blockName'] : '';
    $attrs = isset($parsedBlock['attrs']) && is_array($parsedBlock['attrs']) ? $parsedBlock['attrs'] : [];

    // The avatar has a fixed pixel size; preserve it so it does not stretch to
    // the full container width (the featured image fills the width like a normal
    // image).
    if ($blockName === 'core/avatar' && !isset($attrs['width'])) {
      $width = $this->getRenderedImageWidth($blockContent);
      if ($width !== null) {
        $attrs['width'] = $width . 'px';
        $parsedBlock['attrs'] = $attrs;
      }
    }

    $figure = $this->normalizeToImageFigure($blockContent);
    if ($figure === null) {
      return $blockContent;
    }

    return (new ImageRenderer())->render($figure, $parsedBlock, $renderingContext);
  }

  /**
   * Normalizes image markup into a clean core/image-style <figure><img>,
   * dropping email-hostile attributes (height, object-fit, srcset, ...) so the
   * image scales by width and keeps its aspect ratio.
   */
  private function normalizeToImageFigure(string $html): ?string {
    $processor = new \WP_HTML_Tag_Processor($html);
    if (!$processor->next_tag('img')) {
      return null;
    }
    foreach (['height', 'width', 'style', 'srcset', 'sizes', 'loading', 'decoding'] as $attribute) {
      $processor->remove_attribute($attribute);
    }
    $html = $processor->get_updated_html();

    if (stripos($html, '<figure') === false) {
      $html = '<figure class="wp-block-image">' . $html . '</figure>';
    }

    return $html;
  }

  private function getRenderedImageWidth(string $html): ?int {
    $processor = new \WP_HTML_Tag_Processor($html);
    if (!$processor->next_tag('img')) {
      return null;
    }
    $width = $processor->get_attribute('width');
    if (!is_string($width) || !is_numeric($width)) {
      return null;
    }
    $width = (int)$width;
    return $width > 0 ? $width : null;
  }

  /**
   * Renders the post content for the current post in the template loop.
   * Block posts keep only the blocks the email engine can render, and
   * classic posts are reduced to email-safe HTML. Password-protected
   * posts render nothing here, because an email would expose the
   * protected content to anyone who receives it.
   *
   * @param array<string, mixed>|\WP_Block $attributes
   * @param string $content
   * @param \WP_Block|null $block
   */
  public function renderPostContent($attributes, $content, $block): string {
    if ($this->isRenderingPostContent || !$block instanceof \WP_Block) {
      return '';
    }
    $postId = (int)($block->context['postId'] ?? 0);
    if ($postId <= 0) {
      return '';
    }
    $post = $this->wp->getPost($postId);
    if (!$post instanceof \WP_Post || $this->wp->postPasswordRequired($post)) {
      return '';
    }

    $this->isRenderingPostContent = true;
    try {
      if ($this->wp->hasBlocks($post->post_content)) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        return $this->renderPostContentBlocks($post, $block);
      }
      return $this->renderClassicPostContent($post);
    } finally {
      $this->isRenderingPostContent = false;
    }
  }

  private function renderPostContentBlocks(\WP_Post $post, \WP_Block $block): string {
    // The plain core parser, so the engine's email parser does not preprocess
    // these blocks with the email root padding.
    $blocks = (new \WP_Block_Parser())->parse($post->post_content); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $blocks = $this->filterEmailRenderableBlocks($blocks);
    if (!$blocks) {
      return '';
    }

    $renderingContext = $this->resolveRenderingContext(null);
    $availableWidth = $this->getAvailableWidth($block->parsed_block, $renderingContext); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $blocks = $this->preprocessBlocks($this->normalizeInnerBlocks($blocks), $availableWidth, $renderingContext);

    $html = '';
    foreach ($blocks as $contentBlock) {
      if (is_array($contentBlock)) {
        $html .= render_block($this->normalizeBlock($contentBlock));
      }
    }
    return $html;
  }

  private function renderClassicPostContent(\WP_Post $post): string {
    $content = $this->wp->stripShortcodes($post->post_content); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $content = $this->wp->wpautop($content);
    $content = trim($this->wp->wpKses($content, self::CLASSIC_CONTENT_ALLOWED_HTML));
    if ($content === '') {
      return '';
    }
    return '<div class="mailpoet-post-content">' . $content . '</div>';
  }

  /**
   * Keeps only blocks the email engine can render: those opted into email
   * support or given an email render callback (render-only blocks such as
   * core/embed). Our own blocks and core/post-content are excluded so post
   * content cannot render recursively.
   *
   * @param array<mixed> $blocks
   * @return array<int, array<string, mixed>>
   */
  private function filterEmailRenderableBlocks(array $blocks): array {
    $kept = [];
    foreach ($blocks as $block) {
      if ($this->isEmailRenderableBlock($block)) {
        $kept[] = $this->filterInnerEmailRenderableBlocks($block);
      }
    }
    return $kept;
  }

  /**
   * @param mixed $block
   * @phpstan-assert-if-true array<string, mixed> $block
   */
  private function isEmailRenderableBlock($block): bool {
    if (!is_array($block)) {
      return false;
    }
    $name = $block['blockName'] ?? null;
    if (!is_string($name) || $name === '') {
      return false;
    }
    if (in_array($name, [$this->getBlockType(), self::TEMPLATE_BLOCK, self::POST_CONTENT_BLOCK, 'core/post-content'], true)) {
      return false;
    }
    $blockType = \WP_Block_Type_Registry::get_instance()->get_registered($name);
    if (!$blockType instanceof \WP_Block_Type) {
      return false;
    }
    $supports = is_array($blockType->supports) ? $blockType->supports : [];
    if (($supports['email'] ?? false) === true) {
      return true;
    }
    return isset($blockType->render_email_callback) && is_callable($blockType->render_email_callback); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  /**
   * @param array<string, mixed> $block
   * @return array<string, mixed>
   */
  private function filterInnerEmailRenderableBlocks(array $block): array {
    $innerBlocks = isset($block['innerBlocks']) && is_array($block['innerBlocks']) ? $block['innerBlocks'] : [];
    if (!$innerBlocks) {
      return $block;
    }

    $keptFlags = [];
    $kept = [];
    foreach ($innerBlocks as $innerBlock) {
      $keep = $this->isEmailRenderableBlock($innerBlock);
      $keptFlags[] = $keep;
      if ($keep) {
        $kept[] = $this->filterInnerEmailRenderableBlocks($innerBlock);
      }
    }

    // innerContent holds one null placeholder per inner block; drop the
    // placeholders of removed blocks so markers and blocks stay aligned.
    $rawInnerContent = isset($block['innerContent']) && is_array($block['innerContent']) ? $block['innerContent'] : [];
    $innerContent = [];
    $innerBlockIndex = 0;
    foreach ($rawInnerContent as $chunk) {
      if ($chunk !== null) {
        $innerContent[] = $chunk;
        continue;
      }
      if ($keptFlags[$innerBlockIndex] ?? false) {
        $innerContent[] = null;
      }
      $innerBlockIndex++;
    }

    $block['innerBlocks'] = $kept;
    $block['innerContent'] = $innerContent;
    return $block;
  }

  private function registerTemplateBlock(): void {
    if (\WP_Block_Type_Registry::get_instance()->is_registered(self::TEMPLATE_BLOCK)) {
      return;
    }
    $metadataPath = Env::$assetsPath . '/dist/js/email-editor-blocks/latest-posts-template/block.json';
    if (!file_exists($metadataPath)) {
      return;
    }
    register_block_type_from_metadata($metadataPath, [
      'render_callback' => static function (): string {
        return '';
      },
      'editor_script' => $this->getEditorScript('handle'),
      'editor_style' => $this->getEditorStyle('handle'),
    ]);
  }

  private function registerPostContentBlock(): void {
    if (\WP_Block_Type_Registry::get_instance()->is_registered(self::POST_CONTENT_BLOCK)) {
      return;
    }
    $metadataPath = Env::$assetsPath . '/dist/js/email-editor-blocks/latest-posts-post-content/block.json';
    if (!file_exists($metadataPath)) {
      return;
    }
    $blockType = register_block_type_from_metadata($metadataPath, [
      'render_callback' => [$this, 'renderPostContent'],
      'editor_script' => $this->getEditorScript('handle'),
      'editor_style' => $this->getEditorStyle('handle'),
    ]);
    if ($blockType instanceof \WP_Block_Type) {
      // The render callback already produces email-ready markup; without an
      // identity callback the engine would wrap it in the fallback text renderer.
      // @phpstan-ignore-next-line -- WooCommerce email editor reads this dynamic block setting.
      $blockType->render_email_callback = static function (string $blockContent): string { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        return $blockContent;
      };
    }
  }

  private function registerEmailRenderCallback(): void {
    $blockType = \WP_Block_Type_Registry::get_instance()->get_registered($this->getBlockType());
    if (!$blockType instanceof \WP_Block_Type) {
      return;
    }
    // @phpstan-ignore-next-line -- WooCommerce email editor reads this dynamic block setting.
    $blockType->render_email_callback = [$this, 'renderEmail']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  private function enableEmailSupportForRegisteredCoreBlocks(): void {
    foreach (self::TEMPLATE_CORE_BLOCKS as $blockName) {
      $blockType = \WP_Block_Type_Registry::get_instance()->get_registered($blockName);
      if (!$blockType instanceof \WP_Block_Type) {
        continue;
      }
      $supports = is_array($blockType->supports) ? $blockType->supports : [];
      $supports['email'] = true;
      $blockType->supports = $supports;

      if (in_array($blockName, self::IMAGE_CORE_BLOCKS, true)) {
        // @phpstan-ignore-next-line -- WooCommerce email editor reads this dynamic block setting.
        $blockType->render_email_callback = [$this, 'renderImageBlockForEmail']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      }
    }
  }

  /**
   * @param array<string, mixed> $query
   * @return \WP_Post[]
   */
  private function getPosts(array $query): array {
    $isManual = ($query['selectionMode'] ?? 'latest') === 'manual';
    $manualPosts = $this->getManualPostIds($query);

    // Manual mode shows exactly the picked posts; an empty selection shows none.
    if ($isManual && !$manualPosts) {
      return [];
    }

    $newsletter = $this->resolveNewsletter();
    $newsletterId = $this->getNewsletterIdForQuery($newsletter);
    $cacheKey = $this->getRenderedPostsCacheKey($newsletter, $newsletterId);
    $alreadyRendered = $this->renderedPostsByRequest[$cacheKey] ?? [];

    $posts = $this->automatedLatestContent->getPosts(new BlockPostQuery([
      'args' => $this->buildQueryArgs($query, $isManual, $manualPosts),
      'dynamic' => true,
      // Manual picks are explicit, so they bypass deduplication and the
      // "newer than last sent" filter used by automated latest content.
      'newsletterId' => $isManual ? false : $newsletterId,
      'newerThanTimestamp' => $isManual ? false : $this->getNewerThanTimestamp($newsletter),
      'postsToExclude' => $isManual ? [] : $alreadyRendered,
    ]));

    $renderedIds = $alreadyRendered;
    foreach ($posts as $post) {
      if ($post instanceof \WP_Post) {
        $renderedIds[] = (int)$post->ID;
      }
    }
    $this->renderedPostsByRequest[$cacheKey] = $renderedIds;

    $posts = array_values(array_filter($posts, static function ($post) {
      return $post instanceof \WP_Post;
    }));

    // post__in is queried in date order, not the picked order, so restore the
    // order the posts were selected in.
    return $isManual ? $this->orderByIds($posts, $manualPosts) : $posts;
  }

  /**
   * Reorders posts to match the given list of IDs, dropping any that are not
   * in it.
   *
   * @param \WP_Post[] $posts
   * @param int[] $ids
   * @return \WP_Post[]
   */
  private function orderByIds(array $posts, array $ids): array {
    $postsById = [];
    foreach ($posts as $post) {
      $postsById[(int)$post->ID] = $post;
    }
    $ordered = [];
    foreach ($ids as $id) {
      if (isset($postsById[$id])) {
        $ordered[] = $postsById[$id];
      }
    }
    return $ordered;
  }

  /**
   * @param array<string, mixed> $query
   * @param int[] $manualPosts
   * @return array{contentType: string, posts?: int[], amount?: int, offset?: int, sortBy?: 'ASC'|'DESC', terms?: array<int, array{taxonomy: string, id: int}>, inclusionType?: 'exclude'|'include'}
   */
  private function buildQueryArgs(array $query, bool $isManual, array $manualPosts): array {
    $args = [
      'contentType' => isset($query['postType']) && is_string($query['postType']) ? $query['postType'] : 'post',
    ];

    if ($isManual) {
      $args['posts'] = $manualPosts;
      return $args;
    }

    $perPage = isset($query['perPage']) && is_numeric($query['perPage']) ? (int)$query['perPage'] : self::DEFAULT_POSTS;
    $offset = isset($query['offset']) && is_numeric($query['offset']) ? (int)$query['offset'] : 0;
    $args['amount'] = max(1, min(self::MAX_POSTS, $perPage));
    $args['offset'] = max(0, $offset);
    $args['sortBy'] = $this->isOldestFirst($query) ? 'ASC' : 'DESC';

    // Categories and tags are post-only taxonomies; applying them to other
    // content types would wrongly filter out every result.
    $terms = $args['contentType'] === 'post' ? $this->getTerms($query) : [];
    if ($terms) {
      $args['terms'] = $terms;
      $args['inclusionType'] = $this->isExclude($query) ? 'exclude' : 'include';
    }

    return $args;
  }

  /**
   * @param array<string, mixed> $query
   * @return int[]
   */
  private function getManualPostIds(array $query): array {
    $posts = isset($query['posts']) && is_array($query['posts']) ? $query['posts'] : [];
    $ids = [];
    foreach ($posts as $post) {
      if (is_numeric($post) && (int)$post > 0) {
        $ids[] = (int)$post;
      }
    }
    return $ids;
  }

  /**
   * @param array<string, mixed> $query
   * @return array<int, array{taxonomy: string, id: int}>
   */
  private function getTerms(array $query): array {
    $terms = isset($query['terms']) && is_array($query['terms']) ? $query['terms'] : [];
    $result = [];
    foreach ($terms as $term) {
      if (is_array($term) && isset($term['taxonomy'], $term['id']) && is_string($term['taxonomy']) && is_numeric($term['id']) && (int)$term['id'] > 0) {
        $result[] = ['taxonomy' => $term['taxonomy'], 'id' => (int)$term['id']];
      }
    }
    return $result;
  }

  /**
   * @param array<string, mixed> $query
   */
  private function isExclude(array $query): bool {
    return isset($query['inclusionType']) && $query['inclusionType'] === 'exclude';
  }

  /**
   * @param array<string, mixed> $query
   */
  private function isOldestFirst(array $query): bool {
    return isset($query['order']) && is_string($query['order']) && strtolower($query['order']) === 'oldest';
  }

  /**
   * @param array<string, mixed> $attrs
   */
  private function getColumns(array $attrs): int {
    $displayLayout = isset($attrs['displayLayout']) && is_array($attrs['displayLayout']) ? $attrs['displayLayout'] : [];
    $columns = isset($displayLayout['columns']) && is_numeric($displayLayout['columns']) ? (int)$displayLayout['columns'] : self::DEFAULT_COLUMNS;
    return $this->normalizeColumns($columns);
  }

  /**
   * Inner blocks rendered once per post, falling back to a default layout when
   * the block has no composed template.
   *
   * @param array<string, mixed> $parentBlock
   * @return array<int, mixed>
   */
  private function getTemplateInnerBlocks(array $parentBlock): array {
    $innerBlocks = isset($parentBlock['innerBlocks']) && is_array($parentBlock['innerBlocks']) ? $parentBlock['innerBlocks'] : [];
    foreach ($innerBlocks as $innerBlock) {
      if (is_array($innerBlock) && ($innerBlock['blockName'] ?? null) === self::TEMPLATE_BLOCK) {
        $templateInnerBlocks = isset($innerBlock['innerBlocks']) && is_array($innerBlock['innerBlocks']) ? $innerBlock['innerBlocks'] : [];
        if ($templateInnerBlocks) {
          return $templateInnerBlocks;
        }
      }
    }
    return $this->getDefaultTemplateInnerBlocks();
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  private function getDefaultTemplateInnerBlocks(): array {
    return [
      ['blockName' => 'core/post-featured-image', 'attrs' => []],
      ['blockName' => 'core/post-title', 'attrs' => ['level' => 3, 'isLink' => true]],
      ['blockName' => 'core/post-excerpt', 'attrs' => []],
    ];
  }

  /**
   * @param \WP_Post[] $posts
   * @param array<int, mixed> $innerBlocks
   */
  private function renderPosts(array $posts, array $innerBlocks, int $columns, ?Rendering_Context $renderingContext, int $availableWidth, string $blockMarginTop = ''): string {
    $renderingContext = $this->resolveRenderingContext($renderingContext);
    $blockGap = $this->getBlockGap($renderingContext);

    if ($columns === 1) {
      $preparedBlocks = $this->preprocessBlocks($this->normalizeInnerBlocks($innerBlocks), $availableWidth, $renderingContext);
      $html = '';
      foreach ($posts as $index => $post) {
        // The first post inherits the block's own top margin (from the block
        // above); the rest use the inter-post gap.
        $gap = $index > 0 ? $blockGap : $blockMarginTop;
        $blocks = $gap !== '' ? $this->withTopGap($preparedBlocks, $gap) : $preparedBlocks;
        $html .= $this->renderInnerBlocks($post, $blocks);
      }
      return $html;
    }

    $rows = array_chunk($posts, max(1, $columns));
    $html = '';
    foreach ($rows as $rowIndex => $row) {
      // The first row inherits the block's own top margin; the rest use the gap.
      $marginTop = $rowIndex > 0 ? $blockGap : $blockMarginTop;
      $html .= $this->renderColumnsRow($row, $innerBlocks, $columns, $availableWidth, $renderingContext, $marginTop);
    }
    return $html;
  }

  /**
   * Renders a row of posts as a real core/columns block: build the
   * core/columns > core/column tree, run it through the email preprocessors,
   * then render with the core Columns/Column renderers.
   *
   * The engine adds the column gap as padding on top of the column widths, so
   * equal columns would overflow the row by the total gap. We pre-shrink the
   * width handed to the splitter so columns plus gaps fit the available width.
   *
   * @param \WP_Post[] $posts
   * @param array<int, mixed> $innerBlocks
   */
  private function renderColumnsRow(array $posts, array $innerBlocks, int $columns, int $availableWidth, ?Rendering_Context $renderingContext, string $marginTop = ''): string {
    if ($renderingContext === null) {
      return $this->renderStackedFallback($posts, $innerBlocks, $availableWidth, $renderingContext, $marginTop);
    }

    $gapPx = $this->parsePx($this->getBlockGap($renderingContext));
    $splitWidth = $gapPx > 0 ? max(1, $availableWidth - ($gapPx * ($columns - 1))) : $availableWidth;

    $columnsAttrs = [];
    if ($gapPx > 0) {
      // Pin the gap to a pixel value so it matches the width we subtracted (the
      // theme gap may otherwise be a preset reference).
      $columnsAttrs['style'] = ['spacing' => ['blockGap' => ['top' => $gapPx . 'px', 'left' => $gapPx . 'px']]];
    }

    $columnBlocks = [];
    for ($i = 0; $i < $columns; $i++) {
      $hasPost = isset($posts[$i]);
      $columnBlocks[] = [
        'blockName' => 'core/column',
        'attrs' => [],
        'innerHTML' => '',
        'innerContent' => [],
        'innerBlocks' => $hasPost ? $this->normalizeInnerBlocks($innerBlocks) : [],
      ];
    }
    $columnsTree = [
      'blockName' => 'core/columns',
      'attrs' => $columnsAttrs,
      'innerHTML' => '',
      'innerContent' => [],
      'innerBlocks' => $columnBlocks,
    ];

    $preprocessed = $this->preprocessBlocks([$columnsTree], $splitWidth, $renderingContext);
    $columnsBlock = $preprocessed[0] ?? $columnsTree;
    if (!is_array($columnsBlock)) {
      $columnsBlock = $columnsTree;
    }
    if ($marginTop !== '') {
      $emailAttrs = isset($columnsBlock['email_attrs']) && is_array($columnsBlock['email_attrs']) ? $columnsBlock['email_attrs'] : [];
      $emailAttrs['margin-top'] = $marginTop;
      $columnsBlock['email_attrs'] = $emailAttrs;
    }
    $preparedColumns = isset($columnsBlock['innerBlocks']) && is_array($columnsBlock['innerBlocks']) ? $columnsBlock['innerBlocks'] : [];

    $columnRenderer = new ColumnRenderer();
    $cells = '';
    foreach ($preparedColumns as $i => $columnBlock) {
      if (!is_array($columnBlock)) {
        continue;
      }
      $post = $posts[$i] ?? null;
      $columnInnerBlocks = isset($columnBlock['innerBlocks']) && is_array($columnBlock['innerBlocks']) ? $columnBlock['innerBlocks'] : [];
      $inner = $post instanceof \WP_Post ? $this->renderInnerBlocks($post, $columnInnerBlocks) : '';
      $columnContent = '<div class="wp-block-column">' . $inner . '</div>';
      $cells .= $columnRenderer->render($columnContent, $columnBlock, $renderingContext);
    }

    $columnsContent = '<div class="wp-block-columns">' . $cells . '</div>';
    return (new ColumnsRenderer())->render($columnsContent, $columnsBlock, $renderingContext);
  }

  /**
   * Fallback when the email engine services are unavailable (e.g. rendering
   * outside the editor pipeline): stack posts vertically so nothing is dropped.
   *
   * @param \WP_Post[] $posts
   * @param array<int, mixed> $innerBlocks
   */
  private function renderStackedFallback(array $posts, array $innerBlocks, int $contentWidth, ?Rendering_Context $renderingContext, string $marginTop = ''): string {
    $preparedBlocks = $this->preprocessBlocks($this->normalizeInnerBlocks($innerBlocks), $contentWidth, $renderingContext);
    $blockGap = $this->getBlockGap($renderingContext);
    $html = '';
    foreach ($posts as $index => $post) {
      $gap = $index > 0 ? $blockGap : $marginTop;
      $blocks = $gap !== '' ? $this->withTopGap($preparedBlocks, $gap) : $preparedBlocks;
      $html .= $this->renderInnerBlocks($post, $blocks);
    }
    return $html;
  }

  /**
   * Sets a top gap on the first block so its renderer emits the standard
   * `email-block-layout` margin-top spacer, the way the engine spaces siblings.
   *
   * @param array<int, mixed> $blocks
   * @return array<int, mixed>
   */
  private function withTopGap(array $blocks, string $gap): array {
    foreach ($blocks as $index => $block) {
      if (!is_array($block)) {
        continue;
      }
      $emailAttrs = isset($block['email_attrs']) && is_array($block['email_attrs']) ? $block['email_attrs'] : [];
      $emailAttrs['margin-top'] = $gap;
      $block['email_attrs'] = $emailAttrs;
      $blocks[$index] = $block;
      break;
    }
    return $blocks;
  }

  /**
   * Runs blocks through the email preprocessors so each block (and any nested
   * image) gets its email_attrs width/spacing for the given available width.
   *
   * @param array<int, mixed> $blocks
   * @return array<int, mixed>
   */
  private function preprocessBlocks(array $blocks, int $contentWidth, ?Rendering_Context $renderingContext): array {
    $processManager = $this->getEmailEditorService(Process_Manager::class);
    $themeController = $this->getEmailEditorService(Theme_Controller::class);
    if (!$processManager instanceof Process_Manager || !$themeController instanceof Theme_Controller) {
      return $blocks;
    }

    $styles = $themeController->get_styles();
    unset($styles['spacing']['padding']['left'], $styles['spacing']['padding']['right']);
    $styles['__variables_map'] = $themeController->get_variables_values_map();

    $layout = $themeController->get_layout_settings();
    $layout['contentSize'] = $contentWidth . 'px';

    return $processManager->preprocess($blocks, $layout, $styles, $renderingContext);
  }

  /**
   * Normalizes a list of parsed blocks so they have the full shape the engine
   * preprocessors and WP_Block expect.
   *
   * @param array<int, mixed> $innerBlocks
   * @return array<int, array<string, mixed>>
   */
  private function normalizeInnerBlocks(array $innerBlocks): array {
    $normalized = [];
    foreach ($innerBlocks as $innerBlock) {
      if (is_array($innerBlock)) {
        $normalized[] = $this->normalizeBlock($innerBlock);
      }
    }
    return $normalized;
  }

  /**
   * Renders the per-post inner blocks once for the given post, like
   * core/post-template's loop.
   *
   * Uses render_block() (not WP_Block::render) so the render_block_data filter
   * applies block supports such as element/link colors; skipping it would drop
   * those styles (e.g. the "Read more" link colour). Post context is provided
   * via the render_block_context filter, like core.
   *
   * @param array<int, mixed> $innerBlocks
   */
  private function renderInnerBlocks(\WP_Post $templatePost, array $innerBlocks): string {
    global $post, $wp_query;

    $previousPost = $post;
    $previousQuery = $wp_query;
    $post = $templatePost; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    $wp_query = new \WP_Query(['p' => (int)$templatePost->ID]); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

    $postId = (int)$templatePost->ID;
    $postType = (string)$templatePost->post_type; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $injectContext = static function (array $context) use ($postId, $postType): array {
      $context['postId'] = $postId;
      $context['postType'] = $postType; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return $context;
    };

    $content = '';
    $this->wp->addFilter('render_block_context', $injectContext, 1);
    try {
      foreach ($innerBlocks as $innerBlock) {
        if (!is_array($innerBlock)) {
          continue;
        }
        $content .= render_block($this->normalizeBlock($innerBlock));
      }
    } finally {
      $this->wp->removeFilter('render_block_context', $injectContext, 1);
      $post = $previousPost; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
      $wp_query = $previousQuery; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    }

    return $this->withPostId($content, $postId);
  }

  /**
   * Adds data-post-id to the post markup. Post notifications read it back from
   * the sent email (Tasks\Posts) to know which posts already went out.
   */
  private function withPostId(string $html, int $postId): string {
    if (trim($html) === '') {
      return $html;
    }
    $processor = new \WP_HTML_Tag_Processor($html);
    if (!$processor->next_tag()) {
      return $html;
    }
    $processor->set_attribute('data-post-id', (string)$postId);
    return $processor->get_updated_html();
  }

  /**
   * Fills in any missing keys so the block has the full shape WP_Block expects.
   * Blocks from parse_blocks() always have them, but ones we build by hand (for
   * example in tests) might not, and the renderer would choke on the gaps.
   *
   * Keep email_attrs around - the preprocessors store spacing there and the
   * render_block filter reads it back later. Since we rebuild the array from
   * scratch here, forgetting to copy it would quietly drop the spacing between
   * posts.
   *
   * @param array $block
   * @return array{blockName: string|null, attrs: array, innerBlocks: array, innerHTML: string, innerContent: array, email_attrs?: array}
   */
  private function normalizeBlock(array $block): array {
    $rawInnerBlocks = isset($block['innerBlocks']) && is_array($block['innerBlocks']) ? $block['innerBlocks'] : [];
    $innerBlocks = [];
    foreach ($rawInnerBlocks as $innerBlock) {
      $innerBlocks[] = is_array($innerBlock) ? $this->normalizeBlock($innerBlock) : $innerBlock;
    }

    $normalized = [
      'blockName' => isset($block['blockName']) && is_string($block['blockName']) ? $block['blockName'] : null,
      'attrs' => isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : [],
      'innerBlocks' => $innerBlocks,
      'innerHTML' => isset($block['innerHTML']) && is_string($block['innerHTML']) ? $block['innerHTML'] : '',
      'innerContent' => isset($block['innerContent']) && is_array($block['innerContent']) ? array_values($block['innerContent']) : [],
    ];

    if (isset($block['email_attrs']) && is_array($block['email_attrs'])) {
      $normalized['email_attrs'] = $block['email_attrs'];
    }

    return $normalized;
  }

  private function renderNoPostsMessage(): string {
    return sprintf(
      '<div style="text-align:center;padding:20px;color:#666666;">%s</div>',
      $this->wp->escHtml(__('No posts found.', 'mailpoet'))
    );
  }

  private function normalizeColumns(int $columns): int {
    return max(self::DEFAULT_COLUMNS, min(self::MAX_COLUMNS, $columns));
  }

  private function getBlockGap(?Rendering_Context $renderingContext): string {
    if ($renderingContext === null) {
      return self::DEFAULT_BLOCK_GAP;
    }
    $styles = $renderingContext->get_theme_styles();
    if (isset($styles['spacing']['blockGap']) && is_string($styles['spacing']['blockGap']) && $styles['spacing']['blockGap'] !== '') {
      return $styles['spacing']['blockGap'];
    }
    return self::DEFAULT_BLOCK_GAP;
  }

  /**
   * Width available to this block. The engine stores it in email_attrs after
   * subtracting every ancestor's padding, so it is the right base for sizing
   * posts and columns. Falls back to the layout content width when absent.
   *
   * @param array<string, mixed> $parsedBlock
   */
  private function getAvailableWidth(array $parsedBlock, ?Rendering_Context $renderingContext): int {
    $emailAttrs = isset($parsedBlock['email_attrs']) && is_array($parsedBlock['email_attrs']) ? $parsedBlock['email_attrs'] : [];
    if (isset($emailAttrs['width']) && is_string($emailAttrs['width'])) {
      $width = $this->parsePx($emailAttrs['width']);
      if ($width > 0) {
        return $width;
      }
    }
    return $this->getContentWidth($renderingContext);
  }

  /**
   * The top margin the engine assigned to our block (block gap from the block
   * above). Empty when the block is the first in the email.
   *
   * @param array<string, mixed> $parsedBlock
   */
  private function getBlockMarginTop(array $parsedBlock): string {
    $emailAttrs = isset($parsedBlock['email_attrs']) && is_array($parsedBlock['email_attrs']) ? $parsedBlock['email_attrs'] : [];
    if (isset($emailAttrs['margin-top']) && is_string($emailAttrs['margin-top'])) {
      return $emailAttrs['margin-top'];
    }
    return '';
  }

  /**
   * Layout content width without padding. Used when preprocessing a row so the
   * engine sizes columns and nested images.
   */
  private function getContentWidth(?Rendering_Context $renderingContext): int {
    if ($renderingContext !== null) {
      $width = $this->parsePx($renderingContext->get_layout_width_without_padding());
      if ($width > 0) {
        return $width;
      }
    }
    return self::DEFAULT_CONTENT_WIDTH_PX;
  }

  private function parsePx(string $value): int {
    $value = trim($value);
    if ($value === '') {
      return 0;
    }
    return (int)round((float)str_replace('px', '', $value));
  }

  /**
   * The engine passes a rendering context during real rendering; when the block
   * is rendered directly (e.g. tests) we build one from the email theme.
   */
  private function resolveRenderingContext(?Rendering_Context $renderingContext): ?Rendering_Context {
    if ($renderingContext !== null) {
      return $renderingContext;
    }
    $themeController = $this->getEmailEditorService(Theme_Controller::class);
    if ($themeController instanceof Theme_Controller) {
      return new Rendering_Context($themeController->get_theme());
    }
    return null;
  }

  /**
   * @param class-string $class
   * @return object|null
   */
  private function getEmailEditorService(string $class) {
    try {
      $service = Email_Editor_Container::container()->get($class);
    } catch (\Throwable $e) {
      return null;
    }
    return $service instanceof $class ? $service : null;
  }

  private function resolveNewsletter(): ?NewsletterEntity {
    $post = $this->wp->getPost();
    if (!$post instanceof \WP_Post) {
      return null;
    }
    return $this->newslettersRepository->findOneBy(['wpPost' => (int)$post->ID]);
  }

  /**
   * @param int|false $newsletterId
   */
  private function getRenderedPostsCacheKey(?NewsletterEntity $newsletter, $newsletterId): int {
    if ($newsletterId !== false) {
      return (int)$newsletterId;
    }
    return $newsletter && $newsletter->getId() ? $newsletter->getId() : 0;
  }

  /**
   * @return int|false
   */
  private function getNewsletterIdForQuery(?NewsletterEntity $newsletter) {
    if (!$newsletter || $newsletter->getType() !== NewsletterEntity::TYPE_NOTIFICATION_HISTORY) {
      return false;
    }
    $parent = $newsletter->getParent();
    return $parent instanceof NewsletterEntity ? ($parent->getId() ?? false) : false;
  }

  /**
   * @return \DateTimeInterface|false
   */
  private function getNewerThanTimestamp(?NewsletterEntity $newsletter) {
    if (!$newsletter || $newsletter->getType() !== NewsletterEntity::TYPE_NOTIFICATION_HISTORY) {
      return false;
    }
    $parent = $newsletter->getParent();
    if (!$parent instanceof NewsletterEntity) {
      return false;
    }
    $lastPost = $this->newsletterPostsRepository->findOneBy(['newsletter' => $parent], ['createdAt' => 'desc']);
    return $lastPost instanceof NewsletterPostEntity ? ($lastPost->getCreatedAt() ?? false) : false;
  }
}
