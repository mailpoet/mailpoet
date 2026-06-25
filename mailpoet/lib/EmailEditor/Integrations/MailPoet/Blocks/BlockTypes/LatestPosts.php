<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Blocks\BlockTypes;

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use MailPoet\Config\Env;
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
 * Modeled on WooCommerce's Product Collection block: a curated container
 * (mailpoet/latest-posts) holds a template block (mailpoet/latest-posts-template)
 * whose inner blocks (featured image, title, excerpt, ...) are the composable,
 * repeating unit. Selection controls (how many posts, post type, order, columns)
 * live on the container. At render time we select posts dynamically (reusing the
 * legacy AutomatedLatestContent engine) and render the template's inner blocks
 * once per post, so the markup stays consistent with the rest of the editor.
 */
class LatestPosts extends AbstractBlock {
  protected $blockName = 'latest-posts';

  private const TEMPLATE_BLOCK = 'mailpoet/latest-posts-template';
  private const DEFAULT_COLUMNS = 1;
  private const MAX_COLUMNS = 2;
  private const COLUMN_GAP_PX = 20;
  private const DEFAULT_BLOCK_GAP = '28px';

  /**
   * Core blocks that the template is composed of. They are not email-enabled by
   * default, so we opt them in to keep them usable inside the email editor.
   */
  private const TEMPLATE_CORE_BLOCKS = [
    'core/post-featured-image',
    'core/post-title',
    'core/post-excerpt',
    'core/post-content',
    'core/post-date',
    'core/post-author',
    'core/post-author-name',
    'core/post-author-biography',
    'core/post-terms',
    'core/avatar',
    'core/read-more',
  ];

  /** @var array<int, int[]> */
  private $renderedPostsByRequest = [];

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
    $this->wp->addFilter('block_categories_all', [$this, 'addBlockCategory']);
    $this->wp->addFilter('block_type_metadata_settings', [$this, 'enableEmailSupportForCoreBlocks']);
    $this->wp->addFilter('woocommerce_email_content_renderer_styles', [$this, 'makeLinkColorStylesInlineable']);
    $this->enableEmailSupportForRegisteredCoreBlocks();
    $this->registerEmailRenderCallback();
  }

  /**
   * The block is email-only; it produces no output outside the email rendering
   * pipeline, where renderEmail (render_email_callback) takes over.
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
   * WordPress styles element link colors (e.g. the "Read more" link in
   * core/post-excerpt) with a `:where(:not(.wp-element-button))` selector. The
   * email CSS inliner (Emogrifier) cannot parse `:where()`, so the colour is
   * dropped from the sent email even though the editor (a real browser) honours
   * it. Rewriting it to the equivalent `:not()` selector keeps the same meaning
   * while being inlineable. `:not()` with a single class is supported.
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

    return $this->renderPosts($posts, $innerBlocks, $this->getColumns($attrs), $renderingContext);
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

  private function registerEmailRenderCallback(): void {
    $blockType = \WP_Block_Type_Registry::get_instance()->get_registered($this->getBlockType());
    if (!$blockType instanceof \WP_Block_Type) {
      return;
    }
    $renderEmailCallbackProperty = 'render_email_callback';
    // @phpstan-ignore-next-line -- WooCommerce email editor reads this dynamic block setting.
    $blockType->{$renderEmailCallbackProperty} = [$this, 'renderEmail'];
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

    return array_values(array_filter($posts, static function ($post) {
      return $post instanceof \WP_Post;
    }));
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

    $args['amount'] = isset($query['perPage']) && is_numeric($query['perPage']) ? (int)$query['perPage'] : 3;
    $args['offset'] = isset($query['offset']) && is_numeric($query['offset']) ? (int)$query['offset'] : 0;
    $args['sortBy'] = $this->isOldestFirst($query) ? 'ASC' : 'DESC';

    $terms = $this->getTerms($query);
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
      if (is_numeric($post)) {
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
      if (is_array($term) && isset($term['taxonomy'], $term['id']) && is_string($term['taxonomy']) && is_numeric($term['id'])) {
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
   * Returns the inner blocks rendered once per post. When the block is used
   * without a composed template (e.g. inserted by a pattern or template as a
   * self-closing block), we fall back to a sensible default layout.
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
  private function renderPosts(array $posts, array $innerBlocks, int $columns, ?Rendering_Context $renderingContext): string {
    if ($columns === 1) {
      $html = '';
      $blockGap = $this->getBlockGap($renderingContext);
      $lastIndex = count($posts) - 1;
      foreach ($posts as $index => $post) {
        $marginBottom = $index < $lastIndex ? $blockGap : '0';
        $html .= sprintf(
          '<div style="margin:0 0 %s;">%s</div>',
          $this->wp->escAttr($marginBottom),
          $this->renderPostItem($post, $innerBlocks)
        );
      }
      return $html;
    }

    $html = '';
    foreach (array_chunk($posts, max(1, $columns)) as $row) {
      $html .= $this->renderPostsRow($row, $innerBlocks, $columns, $renderingContext);
    }
    return $html;
  }

  /**
   * @param \WP_Post[] $posts
   * @param array<int, mixed> $innerBlocks
   */
  private function renderPostsRow(array $posts, array $innerBlocks, int $columns, ?Rendering_Context $renderingContext): string {
    $startSide = $this->getStartSide($renderingContext);
    $endSide = $startSide === 'right' ? 'left' : 'right';
    $cellWidth = (int)floor(100 / $columns);
    $gapHalf = (int)floor(self::COLUMN_GAP_PX / 2);

    $html = sprintf(
      '<table role="presentation" width="100%%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 %s;"><tr>',
      $this->wp->escAttr($this->getBlockGap($renderingContext))
    );

    foreach ($posts as $columnIndex => $post) {
      $paddingSide = $columnIndex === 0 ? $endSide : $startSide;
      $html .= sprintf(
        '<td valign="top" width="%1$d%%" style="width:%1$d%%;padding-%2$s:%3$dpx;">%4$s</td>',
        $cellWidth,
        $this->wp->escAttr($paddingSide),
        $gapHalf,
        $this->renderPostItem($post, $innerBlocks)
      );
    }

    $missingCells = $columns - count($posts);
    for ($i = 0; $i < $missingCells; $i++) {
      $html .= sprintf('<td valign="top" width="%1$d%%" style="width:%1$d%%;"></td>', $cellWidth);
    }

    $html .= '</tr></table>';
    return $html;
  }

  /**
   * Renders the per-post inner blocks (featured image, title, excerpt, ...)
   * once for the given post, matching how core/post-template renders its loop.
   *
   * We use render_block() (not WP_Block::render directly) on purpose: it applies
   * the render_block_data filter, which is where WordPress registers block
   * supports such as element/link colors. Skipping it would render the markup
   * but drop the matching styles (e.g. the "Read more" link colour). Post context
   * is provided through the render_block_context filter, exactly like core.
   *
   * @param array<int, mixed> $innerBlocks
   */
  private function renderPostItem(\WP_Post $templatePost, array $innerBlocks): string {
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

    return $content;
  }

  /**
   * Ensures a parsed block array has every key WP_Block expects. Real parsed
   * blocks already do; hand-crafted blocks (e.g. in tests) may not.
   *
   * We deliberately preserve email_attrs: the email editor's preprocessors
   * (block gap, padding, ...) attach them to the parsed tree, and the
   * render_block filter reads them back to apply spacing/styling to each block.
   * Dropping them would strip the default spacing between the inner blocks.
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

  private function getStartSide(?Rendering_Context $renderingContext): string {
    return $renderingContext !== null ? $renderingContext->get_start_side() : 'left';
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
