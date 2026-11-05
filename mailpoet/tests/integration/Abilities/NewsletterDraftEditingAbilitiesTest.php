<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewsletterSaveController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoetVendor\Carbon\Carbon;

require_once __DIR__ . '/AbilitiesTestHelper.php';

/**
 * @phpstan-type ReadDraft array{editable: bool, reason: ?string, blocks: ?array<int, array<string, mixed>>, subject: string, preheader: string, status: string, list_ids: int[], version: string}
 */
class NewsletterDraftEditingAbilitiesTest extends \MailPoetTest {
  private const ALL_BLOCK_TYPES = [
    ['type' => 'heading', 'text' => 'Title & more', 'level' => 1, 'align' => 'center', 'text_color' => '#111111', 'background_color' => '#eeeeee'],
    ['type' => 'heading', 'text' => 'Default level'],
    ['type' => 'paragraph', 'text' => 'Some <strong>bold</strong> and a <a href="https://example.com/a?b=1&amp;c=2">link</a>', 'align' => 'right', 'text_color' => '#222222'],
    ['type' => 'image', 'url' => 'https://example.com/a.png', 'alt' => 'Alt "quoted"'],
    ['type' => 'button', 'text' => 'Read', 'url' => 'https://example.com/read?a=1&b=2', 'align' => 'center', 'text_color' => '#ffffff', 'background_color' => '#0a7d33'],
    ['type' => 'list', 'items' => ['One', 'Two <em>em</em>'], 'ordered' => true, 'text_color' => '#333333'],
    ['type' => 'quote', 'text' => 'Quoted', 'background_color' => '#fafafa'],
    ['type' => 'spacer', 'height' => 30],
    ['type' => 'section', 'background_color' => '#eef2ff', 'text_color' => '#111144', 'blocks' => [
      ['type' => 'paragraph', 'text' => 'Inside'],
      ['type' => 'button', 'text' => 'Go', 'url' => 'https://example.com/go'],
    ]],
  ];

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function _before(): void {
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    AbilitiesTestHelper::enableDraftAbilities();
    wp_set_current_user((int)$this->tester->createWordPressUser('editing-editor@example.com', 'editor'));
  }

  public function _after(): void {
    AbilitiesTestHelper::disableDraftAbilities();
    wp_set_current_user(0);
    $this->tester->deleteCreatedUsers();
    parent::_after();
  }

  public function testGetReturnsTheBlocksADraftWasCreatedWith(): void {
    $list = (new Segment())->withName('Readers')->create();
    $created = $this->createDraft(['list_ids' => [(int)$list->getId()], 'preheader' => 'Pre']);

    $draft = $this->get($created['id']);

    verify($draft['editable'])->true();
    verify($draft['reason'])->null();
    verify($draft['blocks'])->equals(self::ALL_BLOCK_TYPES);
    verify($draft['subject'])->equals('Original');
    verify($draft['preheader'])->equals('Pre');
    verify($draft['list_ids'])->equals([$list->getId()]);
    verify($draft['version'])->equals($created['version']);
    verify($this->diContainer->get(SendingQueuesRepository::class)->findAll())->empty();
  }

  public function testGetReadsADraftSavedByAUserWithoutUnfilteredHtml(): void {
    $user = wp_get_current_user();
    $user->add_cap('unfiltered_html', false);
    // Setting the same user again is a no-op, and kses only turns on when the user changes.
    wp_set_current_user(0);
    wp_set_current_user($user->ID);
    verify(has_filter('content_save_pre', 'wp_filter_post_kses'))->notEquals(false);

    $draft = $this->get($this->createDraft()['id']);

    verify($draft['blocks'])->equals(self::ALL_BLOCK_TYPES);
  }

  public function testUpdateReplacesTheContentAndKeepsTheDraftAsADraft(): void {
    $list = (new Segment())->withName('Readers')->create();
    $oldList = (new Segment())->withName('Old readers')->create();
    $created = $this->createDraft(['list_ids' => [(int)$oldList->getId()]]);

    $updated = $this->update($created['id'], [
      'version' => $created['version'],
      'subject' => 'Changed',
      'preheader' => 'Changed pre',
      'blocks' => [['type' => 'paragraph', 'text' => 'Back\\slash [subscriber:email]']],
      'list_ids' => [(int)$list->getId()],
    ]);

    $this->assertIsArray($updated);
    verify($updated['subject'])->equals('Changed');
    verify($updated['status'])->equals('draft');
    verify($updated['list_ids'])->equals([$list->getId()]);
    verify((string)wp_json_encode($updated['warnings']))->stringContainsString('blocks[0]');
    verify($updated['version'])->notEquals($created['version']);
    verify(get_post_field('post_title', $created['wp_post_id']))->equals('Changed');
    verify(get_post_status($created['wp_post_id']))->equals('draft');
    $draft = $this->get($created['id']);
    verify($draft['blocks'])->equals([['type' => 'paragraph', 'text' => 'Back\\slash']]);
    verify($draft['preheader'])->equals('Changed pre');
  }

  public function testUpdateChangesOnlyTheFieldsItIsGiven(): void {
    $list = (new Segment())->withName('Readers')->create();
    $created = $this->createDraft(['list_ids' => [(int)$list->getId()], 'preheader' => 'Pre']);

    $updated = $this->update($created['id'], ['version' => $created['version'], 'subject' => 'Only subject']);
    $this->assertIsArray($updated);
    $draft = $this->get($created['id']);
    verify($draft['blocks'])->equals(self::ALL_BLOCK_TYPES);
    verify($draft['subject'])->equals('Only subject');
    verify($draft['preheader'])->equals('Pre');
    verify($draft['list_ids'])->equals([$list->getId()]);

    $updated = $this->update($created['id'], ['version' => $draft['version'], 'blocks' => [['type' => 'paragraph', 'text' => 'Only blocks']]]);
    $this->assertIsArray($updated);
    $draft = $this->get($created['id']);
    verify($draft['blocks'])->equals([['type' => 'paragraph', 'text' => 'Only blocks']]);
    verify($draft['subject'])->equals('Only subject');
    verify(get_post_field('post_title', $created['wp_post_id']))->equals('Only subject');
  }

  public function testADraftSavedByTheEditorCanStillBeRead(): void {
    $created = $this->createDraft();
    // Copied from a real save. The editor writes alignment, apostrophes and some attributes
    // differently from this ability.
    $saved = '<!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:heading {"level":3,"style":{"typography":{"textAlign":"left"}}} -->
<h3 class="wp-block-heading has-text-align-left">What\'s new > here</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"#333333","background":"#e7f5ff"},"typography":{"textAlign":"right"}}} -->
<p class="has-text-align-right has-text-color has-background" style="color:#333333;background-color:#e7f5ff">Edited in the editor</p>
<!-- /wp:paragraph -->

<!-- wp:list {"style":{"color":{"text":"#7a003c"}}} -->
<ul style="color:#7a003c" class="wp-block-list has-text-color"><!-- wp:list-item -->
<li>Item</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:group {"style":{"color":{"background":"#eef2ff","text":"#111144"},"spacing":{"padding":{"top":"20px","right":"20px","bottom":"20px","left":"20px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-text-color has-background" style="color:#111144;background-color:#eef2ff;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px"><!-- wp:paragraph -->
<p>Inside</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->';
    $this->setContent($created['wp_post_id'], $saved);

    verify($this->get($created['id'])['blocks'])->equals([
      ['type' => 'heading', 'text' => "What's new > here", 'level' => 3, 'align' => 'left'],
      ['type' => 'paragraph', 'text' => 'Edited in the editor', 'align' => 'right', 'text_color' => '#333333', 'background_color' => '#e7f5ff'],
      ['type' => 'list', 'items' => ['Item'], 'text_color' => '#7a003c'],
      ['type' => 'section', 'background_color' => '#eef2ff', 'text_color' => '#111144', 'blocks' => [['type' => 'paragraph', 'text' => 'Inside']]],
    ]);
  }

  public function testUpdateIsRefusedWhenThePersonChangedTheDraftSinceItWasRead(): void {
    $created = $this->createDraft();
    $content = str_replace('Inside', 'Edited by a person', $this->getPostContent($created['wp_post_id']));
    $this->setContent($created['wp_post_id'], $content);

    $result = $this->update($created['id'], ['version' => $created['version'], 'blocks' => [['type' => 'paragraph', 'text' => 'Agent']]]);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('mailpoet_draft_changed');
    verify($this->getPostContent($created['wp_post_id']))->stringContainsString('Edited by a person');
  }

  public function testUpdateIsRefusedWhenThePersonChangedTheListsSinceItWasRead(): void {
    $list = (new Segment())->withName('Chosen by a person')->create();
    $created = $this->createDraft();
    $this->diContainer->get(NewsletterSaveController::class)->save(['id' => $created['id'], 'segments' => [['id' => (int)$list->getId()]]]);

    $result = $this->update($created['id'], ['version' => $created['version'], 'subject' => 'Changed']);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('mailpoet_draft_changed');
  }

  public function testUpdateWritesNothingWhenTheInputIsInvalid(): void {
    $created = $this->createDraft();

    $result = $this->update($created['id'], [
      'version' => $created['version'],
      'subject' => 'Changed',
      'blocks' => [['type' => 'heading']],
    ]);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('mailpoet_invalid_blocks');
    $draft = $this->get($created['id']);
    verify($draft['subject'])->equals('Original');
    verify($draft['version'])->equals($created['version']);
  }

  public function testADraftWithContentTheFormatCannotHoldIsNotEditable(): void {
    $created = $this->createDraft();
    $content = str_replace('<p>Inside</p>', '<p>Inside <!--[mailpoet/subscriber-firstname]--></p>', $this->getPostContent($created['wp_post_id']));
    $this->setContent($created['wp_post_id'], $content);

    $draft = $this->get($created['id']);
    $result = $this->update($created['id'], ['version' => $draft['version'], 'blocks' => [['type' => 'paragraph', 'text' => 'Agent']]]);

    verify($draft['editable'])->false();
    verify($draft['blocks'])->null();
    verify($draft['reason'])->notEmpty();
    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('mailpoet_draft_not_editable');
    verify($this->getPostContent($created['wp_post_id']))->equals($content);
  }

  public function testADraftWithoutTheWrappingGroupIsNotEditable(): void {
    $created = $this->createDraft();
    $this->setContent($created['wp_post_id'], "<!-- wp:paragraph -->\n<p>No wrapping group</p>\n<!-- /wp:paragraph -->");

    verify($this->get($created['id'])['blocks'])->null();
  }

  public function testContentOutsideTheBlocksMakesADraftNotEditable(): void {
    $created = $this->createDraft();
    $this->setContent($created['wp_post_id'], '<p>Text outside any block</p>' . "\n\n" . $this->getPostContent($created['wp_post_id']));

    verify($this->get($created['id'])['blocks'])->null();
  }

  public function testABlockWithMoreStylingThanTheFormatHoldsMakesADraftNotEditable(): void {
    $created = $this->createDraft();
    $content = str_replace(
      "<!-- wp:paragraph -->\n<p>Inside</p>",
      '<!-- wp:paragraph {"fontSize":"small"} -->' . "\n" . '<p class="has-small-font-size">Inside</p>',
      $this->getPostContent($created['wp_post_id'])
    );
    $this->setContent($created['wp_post_id'], $content);

    verify($this->get($created['id'])['blocks'])->null();
  }

  public function testABlockTheInputSchemaRejectsMakesADraftNotEditable(): void {
    $created = $this->createDraft();
    $content = str_replace(
      '<!-- wp:heading -->' . "\n" . '<h2 class="wp-block-heading">Default level</h2>' . "\n" . '<!-- /wp:heading -->',
      '<!-- wp:heading {"level":4} -->' . "\n" . '<h4 class="wp-block-heading">Default level</h4>' . "\n" . '<!-- /wp:heading -->',
      $this->getPostContent($created['wp_post_id'])
    );
    $this->setContent($created['wp_post_id'], $content);

    verify($this->get($created['id'])['blocks'])->null();
  }

  public function testASentNewsletterIsNotEditable(): void {
    $created = $this->createDraft();
    $this->getNewsletter($created['id'])->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->flush();

    $draft = $this->get($created['id']);
    $result = $this->update($created['id'], ['version' => $draft['version'], 'subject' => 'Changed']);

    verify($draft['editable'])->false();
    verify($draft['status'])->equals('sent');
    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('mailpoet_draft_not_editable');
    verify($this->get($created['id'])['subject'])->equals('Original');
  }

  public function testADraftWithASendingQueueIsNotEditable(): void {
    $created = $this->createDraft();
    $withQueue = (new Newsletter())->withDraftStatus()->withWpPostId($created['wp_post_id'])->withSendingQueue()->create();

    $draft = $this->get((int)$withQueue->getId());

    verify($draft['status'])->equals('draft');
    verify($draft['blocks'])->notNull();
    verify($draft['editable'])->false();
  }

  public function testOnlyStandardBlockEditorNewslettersAreFound(): void {
    $created = $this->createDraft();
    $trashed = $this->createDraft();
    $this->getNewsletter($trashed['id'])->setDeletedAt(new Carbon());
    $this->getNewsletter($created['id'])->setType(NewsletterEntity::TYPE_AUTOMATION);
    $this->entityManager->flush();
    $legacy = (new Newsletter())->withDraftStatus()->create();

    foreach ([$legacy->getId(), $trashed['id'], $created['id']] as $id) {
      $result = $this->ability('mailpoet/get-newsletter-draft')->execute(['id' => (int)$id]);
      $this->assertInstanceOf(\WP_Error::class, $result);
      verify($result->get_error_code())->equals('mailpoet_draft_not_found');
    }
  }

  public function testUsersWhoCannotManageEmailsAreRefused(): void {
    $created = $this->createDraft();
    wp_set_current_user((int)$this->tester->createWordPressUser('editing-author@example.com', 'author'));

    $read = $this->ability('mailpoet/get-newsletter-draft')->execute(['id' => $created['id']]);
    $write = $this->update($created['id'], ['version' => $created['version'], 'subject' => 'Changed']);

    $this->assertInstanceOf(\WP_Error::class, $read);
    verify($read->get_error_code())->equals('ability_invalid_permissions');
    $this->assertInstanceOf(\WP_Error::class, $write);
    verify($write->get_error_code())->equals('ability_invalid_permissions');
  }

  /**
   * @param array<string, mixed> $input
   * @return array{id: int, wp_post_id: int, version: string}
   */
  private function createDraft(array $input = []): array {
    $result = $this->ability('mailpoet/create-newsletter-draft')->execute(array_merge([
      'subject' => 'Original',
      'blocks' => self::ALL_BLOCK_TYPES,
    ], $input));
    $this->assertIsArray($result);
    /** @var array{id: int, wp_post_id: int, version: string} $result */
    return $result;
  }

  /** @return ReadDraft */
  private function get(int $id): array {
    $result = $this->ability('mailpoet/get-newsletter-draft')->execute(['id' => $id]);
    $this->assertIsArray($result);
    /** @var ReadDraft $result */
    return $result;
  }

  /**
   * @param array<string, mixed> $input
   * @return mixed
   */
  private function update(int $id, array $input) {
    return $this->ability('mailpoet/update-newsletter-draft')->execute(array_merge(['id' => $id], $input));
  }

  private function setContent(int $postId, string $content): void {
    wp_update_post(wp_slash(['ID' => $postId, 'post_content' => $content]));
  }

  private function getNewsletter(int $id): NewsletterEntity {
    $newsletter = $this->newslettersRepository->findOneById($id);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    return $newsletter;
  }

  private function ability(string $name): \WP_Ability {
    $ability = wp_get_ability($name);
    $this->assertInstanceOf(\WP_Ability::class, $ability);
    return $ability;
  }

  private function getPostContent(int $postId): string {
    $content = get_post_field('post_content', $postId);
    $this->assertIsString($content);
    return $content;
  }
}
