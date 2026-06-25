<?php declare(strict_types = 1);

namespace MailPoet\Test\EmailEditor\Integrations\MailPoet\Blocks;

use MailPoet\EmailEditor\Integrations\MailPoet\Blocks\BlockTypes\LatestPosts;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterPostEntity;
use MailPoet\Newsletter\NewsletterPostsRepository;
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

    verify($html)->stringContainsString('width="50%"');
    verify(substr_count($html, '<td valign="top" width="50%"'))->equals(2);
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

  private function createBlockEmailNewsletter(string $type, ?NewsletterEntity $parent = null): NewsletterEntity {
    $postId = $this->wp->wpInsertPost([
      'post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE,
      'post_status' => 'publish',
      'post_title' => 'Latest posts email',
      'post_content' => '<!-- wp:mailpoet/latest-posts /-->',
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
}
