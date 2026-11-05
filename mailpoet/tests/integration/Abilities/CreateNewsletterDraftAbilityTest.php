<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use MailPoet\Abilities\AbilitiesRegistrar;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Newsletter\NewsletterSaveController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Features;
use MailPoet\Test\DataFactories\Segment;

require_once __DIR__ . '/AbilitiesTestHelper.php';

class CreateNewsletterDraftAbilityTest extends \MailPoetTest {
  private const ABILITY = 'mailpoet/create-newsletter-draft';

  /** @var AbilitiesRegistrar */
  private $registrar;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function _before(): void {
    $this->registrar = $this->diContainer->get(AbilitiesRegistrar::class);
    $this->newslettersRepository = $this->diContainer->get(NewslettersRepository::class);
    wp_set_current_user((int)$this->tester->createWordPressUser('draft-admin@example.com', 'administrator'));
    (new Features())->withFeatureEnabled(FeaturesController::FEATURE_MCP_WRITE_ABILITIES);
    $this->diContainer->get(FeaturesController::class)->resetCache();
    AbilitiesTestHelper::register($this->registrar);
  }

  public function _after(): void {
    AbilitiesTestHelper::unregister();
    $this->diContainer->get(FeaturesController::class)->resetCache();
    $this->deleteEmailPosts();
    wp_set_current_user(0);
    $this->tester->deleteCreatedUsers();
    parent::_after();
  }

  public function testItIsNotRegisteredWhenTheFlagIsOff(): void {
    (new Features())->withFeatureDisabled(FeaturesController::FEATURE_MCP_WRITE_ABILITIES);
    $this->diContainer->get(FeaturesController::class)->resetCache();

    AbilitiesTestHelper::register($this->registrar);

    verify(wp_has_ability(self::ABILITY))->false();
    verify(wp_has_ability('mailpoet/get-newsletter-draft-options'))->false();
    verify(wp_has_ability('mailpoet/get-sending-status'))->true();
  }

  public function testItIsRegisteredAsAPublicWriteToolWhenTheFlagIsOn(): void {
    $ability = wp_get_ability(self::ABILITY);

    $this->assertInstanceOf(\WP_Ability::class, $ability);
    verify($ability->get_category())->equals('mailpoet');
    verify($ability->get_meta()['mcp'])->equals(['public' => true, 'type' => 'tool']);
    verify($ability->get_meta()['annotations'])->equals(['readonly' => false, 'destructive' => false, 'idempotent' => false]);
    // MCP clients load every description into the model's context on each request.
    $this->assertLessThanOrEqual(500, strlen($ability->get_description()));
  }

  public function testItPreselectsListsWithoutSendingAnything(): void {
    $list = (new Segment())->withName('Chosen')->create();

    $result = $this->createDraft([
      'subject' => 'For a list',
      'blocks' => [['type' => 'paragraph', 'text' => 'x']],
      'list_ids' => [(int)$list->getId()],
    ]);

    verify($result['list_ids'])->equals([$list->getId()]);
    verify($result['status'])->equals('draft');
    $newsletter = $this->newslettersRepository->findOneById($result['id']);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $this->entityManager->refresh($newsletter);
    verify(array_values($newsletter->getSegmentIds()))->equals([$list->getId()]);
    verify($this->diContainer->get(SendingQueuesRepository::class)->findAll())->empty();
  }

  public function testItCreatesAStandardDraftInTheBlockEditorAndSendsNothing(): void {
    $this->diContainer->get(SettingsController::class)->set('sender', ['address' => 'sender@example.com', 'name' => 'Sender']);
    $text = 'Čeština 日本語 🎉 "quotes" back\\slash';

    $result = $this->createDraft([
      'subject' => 'October news',
      'preheader' => 'What is new',
      'blocks' => [
        ['type' => 'heading', 'text' => 'Title', 'level' => 1],
        ['type' => 'paragraph', 'text' => $text],
        ['type' => 'image', 'url' => 'https://example.com/a.png', 'alt' => 'An image'],
        ['type' => 'button', 'text' => 'Read', 'url' => 'https://example.com/read'],
      ],
    ]);

    $newsletter = $this->newslettersRepository->findOneById($result['id']);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($newsletter->getType())->equals(NewsletterEntity::TYPE_STANDARD);
    verify($newsletter->getStatus())->equals(NewsletterEntity::STATUS_DRAFT);
    verify($newsletter->getSubject())->equals('October news');
    verify($newsletter->getPreheader())->equals('What is new');
    verify($newsletter->getSenderAddress())->equals('sender@example.com');
    verify($newsletter->getWpPostId())->equals($result['wp_post_id']);

    $postId = $result['wp_post_id'];
    verify(get_post_field('post_type', $postId))->equals(EmailEditor::MAILPOET_EMAIL_POST_TYPE);
    verify(get_post_field('post_status', $postId))->equals('draft');
    verify(get_post_field('post_title', $postId))->equals('October news');
    verify(get_post_meta($postId, '_wp_page_template', true))->equals('newsletter');
    $content = $this->getPostContent($result);
    verify($content)->stringContainsString("<p>$text</p>");
    verify($this->getInnerBlockNames($content))->equals([
      'core/heading',
      'core/paragraph',
      'core/image',
      'core/buttons',
    ]);

    verify($result)->equals([
      'id' => $newsletter->getId(),
      'wp_post_id' => $postId,
      'subject' => 'October news',
      'status' => 'draft',
      'edit_url' => admin_url('post.php?post=' . $postId . '&action=edit'),
      'created' => true,
      'list_ids' => [],
      'warnings' => [],
    ]);
    verify($this->diContainer->get(SendingQueuesRepository::class)->findAll())->empty();
  }

  public function testItRemovesScriptsCommentsAndUnsafeLinksFromParagraphs(): void {
    $result = $this->createDraft([
      'subject' => 'Unsafe',
      'blocks' => [[
        'type' => 'paragraph',
        'text' => 'Safe<script>alert(1)</script> <!-- wp:html --><div>raw</div><!-- /wp:html --> <!--[mailpoet/site-title]-->'
          . ' <a href="javascript:alert(1)">bad</a> <a href="https://example.com/ok">ok</a> <<!-- x -->!-- wp:html -->',
      ]],
    ]);

    $content = $this->getPostContent($result);
    verify($content)->stringNotContainsString('<script');
    verify($content)->stringNotContainsString('javascript:');
    verify($content)->stringNotContainsString('wp:html');
    verify($content)->stringNotContainsString('mailpoet/site-title');
    verify($content)->stringContainsString('<a href="https://example.com/ok">ok</a>');
    verify($this->getInnerBlockNames($content))->equals(['core/paragraph']);
    verify($result['warnings'])->arrayCount(1);
    verify($result['warnings'][0])->stringContainsString('blocks[0]');
  }

  public function testItRemovesShortcodesAndWarnsAboutEachField(): void {
    $result = $this->createDraft([
      'subject' => 'Hi [subscriber:firstname]',
      'preheader' => 'For [subscriber:email]',
      'blocks' => [
        ['type' => 'paragraph', 'text' => 'Hello [subscriber:email] &#91;subscriber:email&#93; [subscr[x:y]iber:email] [1] world'],
        ['type' => 'image', 'url' => 'https://example.com/p.png?e=[subscriber:email]', 'alt' => 'For [subscriber:email]'],
        ['type' => 'button', 'text' => 'Go', 'url' => 'https://example.com/go'],
      ],
    ]);

    verify($result['subject'])->equals('Hi');
    $newsletter = $this->newslettersRepository->findOneById($result['id']);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($newsletter->getPreheader())->equals('For');
    $content = $this->getPostContent($result);
    verify($content)->stringNotContainsString('subscriber:');
    verify($content)->stringNotContainsString('subscriber&#58;');
    verify($content)->stringNotContainsString('&#91;');
    verify($content)->stringContainsString('[1] world');
    verify($content)->stringContainsString('src="https://example.com/p.png?e="');
    $warnings = implode("\n", $result['warnings']);
    verify($warnings)->stringContainsString('subject');
    verify($warnings)->stringContainsString('preheader');
    verify($warnings)->stringContainsString('blocks[0]');
    verify($warnings)->stringContainsString('blocks[1]');
    verify($warnings)->stringNotContainsString('blocks[2]');
  }

  public function testItRemovesPersonalizationTagsFromLinks(): void {
    $result = $this->createDraft([
      'subject' => 'Links',
      'blocks' => [
        ['type' => 'button', 'text' => 'Plain', 'url' => 'https://example.com/plain?e=[mailpoet/subscriber-email]'],
        ['type' => 'button', 'text' => 'Encoded', 'url' => 'https://example.com/encoded?e=%5Bmailpoet/subscriber-email%5D'],
        ['type' => 'paragraph', 'text' => '<a href="https://example.com/p?e=[mailpoet/subscriber-email default=&quot;x&quot;]">a</a> <a href="https://example.com/q?e=%5bmailpoet/subscriber-email%5d">b</a>'],
        ['type' => 'button', 'text' => 'Clean', 'url' => 'https://example.com/clean?a=1'],
      ],
    ]);

    $content = $this->getPostContent($result);
    verify($content)->stringNotContainsString('subscriber-email');
    verify($content)->stringContainsString('href="https://example.com/plain?e="');
    verify($content)->stringContainsString('href="https://example.com/clean?a=1"');
    $warnings = implode("\n", $result['warnings']);
    verify($warnings)->stringContainsString('blocks[0]');
    verify($warnings)->stringContainsString('blocks[1]');
    verify($warnings)->stringContainsString('blocks[2]');
    verify($warnings)->stringNotContainsString('blocks[3]');
  }

  public function testItSanitizesBlocksInsideASectionAndNamesThemInWarnings(): void {
    $result = $this->createDraft([
      'subject' => 'Nested',
      'blocks' => [
        ['type' => 'section', 'blocks' => [
          ['type' => 'paragraph', 'text' => 'Inside<script>alert(1)</script> [subscriber:email]'],
          ['type' => 'list', 'items' => ['Item [subscriber:email]']],
        ]],
      ],
    ]);

    $content = $this->getPostContent($result);
    verify($content)->stringNotContainsString('<script');
    verify($content)->stringNotContainsString('subscriber:email');
    verify(implode("\n", $result['warnings']))->stringContainsString('blocks[0].blocks[0]');
    verify(implode("\n", $result['warnings']))->stringContainsString('blocks[0].blocks[1]');
  }

  public function testASectionCannotHoldAnotherSection(): void {
    $result = $this->execute([
      'subject' => 'Too deep',
      'blocks' => [['type' => 'section', 'blocks' => [['type' => 'section', 'blocks' => [['type' => 'paragraph', 'text' => 'x']]]]]],
    ]);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('ability_invalid_input');
  }

  public function testRemovingAShortcodeKeepsTheEscapedTextAroundIt(): void {
    $result = $this->createDraft([
      'subject' => 'Escaped',
      'blocks' => [['type' => 'paragraph', 'text' => 'Use the &lt;div&gt; tag [subscriber:email]']],
    ]);

    verify($this->getPostContent($result))->stringContainsString('<p>Use the &lt;div&gt; tag');
  }

  public function testItClosesUnbalancedParagraphTags(): void {
    $result = $this->createDraft([
      'subject' => 'Unbalanced',
      'blocks' => [['type' => 'paragraph', 'text' => 'Read <a href="https://example.com/x">this <strong>now']],
    ]);

    verify($this->getPostContent($result))->stringContainsString('<p>Read <a href="https://example.com/x">this <strong>now</strong></a></p>');
  }

  public function testItReturnsEveryBlockErrorAndWritesNothing(): void {
    $postCount = $this->countEmailPosts();

    $result = $this->execute([
      'subject' => '[subscriber:firstname]',
      'list_ids' => [999999],
      'blocks' => [
        ['type' => 'button', 'text' => 'x', 'url' => 'javascript:alert(1)'],
        ['type' => 'heading'],
        ['type' => 'paragraph', 'text' => 'ok', 'url' => 'https://example.com'],
        ['type' => 'image', 'url' => 'https://example.com/a.png'],
        ['type' => 'image', 'url' => '//example.com/a.png', 'alt' => 'No scheme'],
        ['type' => 'heading', 'text' => 'Bad color', 'text_color' => 'red'],
        ['type' => 'section', 'blocks' => [['type' => 'heading'], ['type' => 'paragraph', 'text' => 'fine']]],
        ['type' => 'paragraph', 'text' => 'fine'],
      ],
    ]);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('mailpoet_invalid_blocks');
    $data = $result->get_error_data();
    $this->assertIsArray($data);
    verify($data['status'])->equals(400);
    $this->assertIsArray($data['errors']);
    verify(array_column($data['errors'], 'field'))->equals(['subject', 'list_ids', 'blocks[0]', 'blocks[1]', 'blocks[2]', 'blocks[3]', 'blocks[4]', 'blocks[5]', 'blocks[6].blocks[0]']);
    verify($result->get_error_message())->stringContainsString('blocks[3]');
    verify($this->newslettersRepository->findAll())->empty();
    verify($this->countEmailPosts())->equals($postCount);
  }

  public function testItReportsAFailedSaveAndKeepsNothing(): void {
    $postCount = $this->countEmailPosts();
    add_filter('wp_insert_post_empty_content', '__return_true');

    $result = $this->execute(['subject' => 'Fails', 'blocks' => [['type' => 'paragraph', 'text' => 'x']]]);

    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('mailpoet_draft_not_created');
    verify($this->newslettersRepository->findAll())->empty();
    verify($this->countEmailPosts())->equals($postCount);
  }

  public function testItSavesTheSameContentForUsersWithoutUnfilteredHtml(): void {
    $input = [
      'subject' => 'Roles',
      'blocks' => [
        ['type' => 'heading', 'text' => 'Title'],
        ['type' => 'paragraph', 'text' => '<strong>Bold</strong> <em>em</em> <a href="https://example.com/a?b=1&c=2">link</a>'],
        ['type' => 'image', 'url' => 'https://example.com/a.png', 'alt' => 'Alt "quoted"'],
        ['type' => 'button', 'text' => 'Read', 'url' => 'https://example.com/read?a=1&b=2', 'align' => 'center', 'text_color' => '#ffffff', 'background_color' => '#0a7d33'],
        ['type' => 'list', 'items' => ['One', 'Two']],
        ['type' => 'quote', 'text' => 'Quoted', 'text_color' => '#333333'],
        ['type' => 'spacer', 'height' => 30],
        ['type' => 'section', 'background_color' => '#eef2ff', 'text_color' => '#111111', 'blocks' => [
          ['type' => 'heading', 'text' => 'Inside', 'align' => 'center', 'background_color' => '#fff3cd'],
        ]],
      ],
    ];
    $withUnfilteredHtml = $this->createDraft($input);

    $user = wp_get_current_user();
    $user->add_cap('unfiltered_html', false);
    // Setting the same user again is a no-op, and kses only turns on when the user changes.
    wp_set_current_user(0);
    wp_set_current_user($user->ID);
    verify(has_filter('content_save_pre', 'wp_filter_post_kses'))->notEquals(false);
    $withoutUnfilteredHtml = $this->createDraft($input);

    // Kses only adds a space before "/>", which block validation ignores. On multisite both saves
    // are filtered, so both sides are normalized.
    $withoutSpace = function (string $content): string {
      return str_replace(' />', '/>', $content);
    };
    verify($withoutSpace($this->getPostContent($withoutUnfilteredHtml)))->equals($withoutSpace($this->getPostContent($withUnfilteredHtml)));
  }

  public function testARepeatedClientReferenceReturnsTheSameDraft(): void {
    $input = [
      'subject' => 'Once',
      'blocks' => [['type' => 'paragraph', 'text' => 'Only one']],
      'client_reference' => 'retry-1',
    ];

    $first = $this->createDraft($input);
    $second = $this->createDraft($input);
    $other = $this->createDraft(array_merge($input, ['client_reference' => 'retry-2']));

    verify($first['created'])->true();
    verify($second['created'])->false();
    verify($second['id'])->equals($first['id']);
    verify($second['wp_post_id'])->equals($first['wp_post_id']);
    verify($other['created'])->true();
    verify($other['id'])->notEquals($first['id']);
    verify($this->newslettersRepository->findAll())->arrayCount(2);
  }

  public function testARepeatedClientReferenceReportsTheCurrentStatusOfASentNewsletter(): void {
    $input = [
      'subject' => 'Sent later',
      'blocks' => [['type' => 'paragraph', 'text' => 'Sent']],
      'client_reference' => 'retry-sent',
    ];
    $first = $this->createDraft($input);
    $newsletter = $this->newslettersRepository->findOneById($first['id']);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $newsletter->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->flush();

    $second = $this->createDraft($input);

    verify($second['status'])->equals('sent');
    verify($second['created'])->false();
    verify($this->newslettersRepository->findAll())->arrayCount(1);
  }

  public function testADuplicatedDraftDoesNotTakeOverTheClientReference(): void {
    $input = [
      'subject' => 'Original',
      'blocks' => [['type' => 'paragraph', 'text' => 'Original']],
      'client_reference' => 'retry-copy',
    ];
    $first = $this->createDraft($input);
    wp_update_post(['ID' => $first['wp_post_id'], 'post_date' => '2020-01-01 10:00:00', 'post_date_gmt' => '2020-01-01 10:00:00']);
    $newsletter = $this->newslettersRepository->findOneById($first['id']);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $copy = $this->diContainer->get(NewsletterSaveController::class)->duplicate($newsletter);
    verify(get_post_meta((int)$copy->getWpPostId(), '_mailpoet_client_reference', true))->equals('retry-copy');

    $second = $this->createDraft($input);

    verify($second['created'])->false();
    verify($second['id'])->equals($first['id']);
  }

  public function testOnlyUsersWhoCanManageEmailsCanCreateDrafts(): void {
    $input = ['subject' => 'Access', 'blocks' => [['type' => 'paragraph', 'text' => 'x']]];

    wp_set_current_user((int)$this->tester->createWordPressUser('draft-author@example.com', 'author'));
    $refused = $this->execute($input);
    wp_set_current_user((int)$this->tester->createWordPressUser('draft-editor@example.com', 'editor'));
    $allowed = $this->createDraft($input);

    $this->assertInstanceOf(\WP_Error::class, $refused);
    verify($refused->get_error_code())->equals('ability_invalid_permissions');
    verify($allowed['created'])->true();
  }

  /**
   * @param array<string, mixed> $input
   * @return mixed
   */
  private function execute(array $input) {
    $ability = wp_get_ability(self::ABILITY);
    $this->assertInstanceOf(\WP_Ability::class, $ability);
    return $ability->execute($input);
  }

  /**
   * @param array<string, mixed> $input
   * @return array{id: int, wp_post_id: int, subject: string, status: string, edit_url: string, created: bool, list_ids: int[], warnings: string[]}
   */
  private function createDraft(array $input): array {
    $result = $this->execute($input);
    $this->assertIsArray($result);
    /** @var array{id: int, wp_post_id: int, subject: string, status: string, edit_url: string, created: bool, list_ids: int[], warnings: string[]} $result */
    return $result;
  }

  /** @param array{wp_post_id: int} $result */
  private function getPostContent(array $result): string {
    $content = get_post_field('post_content', $result['wp_post_id']);
    $this->assertIsString($content);
    return $content;
  }

  /** @return array<int, string|null> */
  private function getInnerBlockNames(string $content): array {
    /** @var array<int, array{blockName: string|null, innerBlocks: array<int, array{blockName: string|null}>}> $blocks */
    $blocks = parse_blocks($content);
    verify($blocks[0]['blockName'])->equals('core/group');
    return array_column($blocks[0]['innerBlocks'], 'blockName');
  }

  // Other test classes leave posts of this type behind, so tests compare counts.
  private function countEmailPosts(): int {
    return count(get_posts([
      'post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE,
      'post_status' => 'any',
      'numberposts' => -1,
      'fields' => 'ids',
    ]));
  }

  private function deleteEmailPosts(): void {
    $ids = get_posts([
      'post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE,
      'post_status' => ['draft', 'sent'],
      'numberposts' => -1,
      'fields' => 'ids',
    ]);
    array_map(function ($id) {
      wp_delete_post((int)$id, true);
    }, $ids);
  }
}
