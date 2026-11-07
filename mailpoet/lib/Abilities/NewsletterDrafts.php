<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Segments\SegmentsSimpleListRepository;
use MailPoet\Segments\WooCommerce;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * What the abilities working with newsletter drafts share: the input fields, how they are
 * checked, and the output.
 *
 * @phpstan-import-type DraftError from NewsletterDraftBlocks
 * @phpstan-type PreparedDraft array{subject: ?string, preheader: ?string, markup: ?string, segments: ?array<int, array{id: int}>, errors: DraftError[], warnings: string[]}
 */
class NewsletterDrafts {
  /** @var NewsletterDraftBlocks */
  private $draftBlocks;

  /** @var SegmentsSimpleListRepository */
  private $segmentsListRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var WooCommerce */
  private $wooCommerceSegment;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewsletterDraftBlocks $draftBlocks,
    SegmentsSimpleListRepository $segmentsListRepository,
    SegmentsRepository $segmentsRepository,
    WooCommerce $wooCommerceSegment,
    NewslettersRepository $newslettersRepository,
    WPFunctions $wp
  ) {
    $this->draftBlocks = $draftBlocks;
    $this->segmentsListRepository = $segmentsListRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->wooCommerceSegment = $wooCommerceSegment;
    $this->newslettersRepository = $newslettersRepository;
    $this->wp = $wp;
  }

  /**
   * The same lists and counts as the Send to control in the send step.
   *
   * @return array<int, array{id: int, name: string, type: string, subscribers: int}>
   */
  public function getLists(): array {
    return array_map(function (array $segment): array {
      return [
        'id' => (int)$segment['id'],
        'name' => $segment['name'],
        'type' => $segment['type'],
        'subscribers' => $segment['subscribers'],
      ];
    }, $this->segmentsListRepository->getListWithSubscribedSubscribersCounts($this->wooCommerceSegment->getNewsletterSegmentTypes()));
  }

  /** Standard newsletters from the block email editor. */
  public function findNewsletter(int $id): ?NewsletterEntity {
    $newsletter = $this->newslettersRepository->findOneById($id);
    if (
      !$newsletter
      || $newsletter->getType() !== NewsletterEntity::TYPE_STANDARD
      || $newsletter->getDeletedAt()
      || !$newsletter->getWpPostId()
    ) {
      return null;
    }
    return $newsletter;
  }

  public function getNotFoundError(): \WP_Error {
    return new \WP_Error(
      'mailpoet_draft_not_found',
      __('There is no standard newsletter from the block email editor with this id. Find ids in mailpoet/get-newsletter-draft-options.', 'mailpoet'),
      ['status' => 404]
    );
  }

  public function getContent(NewsletterEntity $newsletter): string {
    $content = $this->wp->getPostField('post_content', (int)$newsletter->getWpPostId());
    return is_string($content) ? $content : '';
  }

  /** It changes whenever the content does, so an update can tell that it read the current one. */
  public function getVersion(NewsletterEntity $newsletter): string {
    $listIds = $newsletter->getSegmentIds();
    sort($listIds);
    return md5(implode("\0", [$this->getContent($newsletter), $newsletter->getSubject(), $newsletter->getPreheader(), implode(',', $listIds)]));
  }

  /**
   * @param array<string, string> $fields
   * @throws \RuntimeException
   */
  public function updatePost(int $postId, array $fields): void {
    $result = $this->wp->wpUpdatePost($this->wp->wpSlash(array_merge($fields, ['ID' => $postId])), true);
    if ($this->wp->isWpError($result)) {
      throw new \RuntimeException('The post was not updated.');
    }
  }

  /** @param array<int, array<string, mixed>>|null $blocks The draft's blocks, null if they can't be read. */
  public function getNotEditableReason(NewsletterEntity $newsletter, ?array $blocks): ?string {
    if ($newsletter->getStatus() !== NewsletterEntity::STATUS_DRAFT) {
      return __('This newsletter is no longer a draft, so it cannot be changed.', 'mailpoet');
    }
    if ($newsletter->getLatestQueue()) {
      return __('This newsletter has started sending, so it cannot be changed.', 'mailpoet');
    }
    if ($blocks === null) {
      return __('This draft has content the block format cannot hold, such as a pattern, a personalization tag or extra styling. A person has to change it in the editor.', 'mailpoet');
    }
    return null;
  }

  public function getInputProperties(): array {
    return [
      'subject' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 250],
      'preheader' => ['type' => 'string', 'maxLength' => 250],
      'blocks' => $this->draftBlocks->getBlocksSchema(),
      'list_ids' => [
        'type' => 'array',
        'uniqueItems' => true,
        'minItems' => 1,
        'maxItems' => 20,
        'items' => ['type' => 'integer'],
        'description' => __('Lists to preselect, from mailpoet/get-newsletter-draft-options. Ask the user which lists they want. A person still confirms the recipients when sending.', 'mailpoet'),
      ],
    ];
  }

  /**
   * Fields that are not in the input stay null.
   *
   * @param array<string, mixed> $input
   * @return PreparedDraft
   */
  public function prepareInput(array $input): array {
    // Validation lets through values it can convert, like a comma separated string for a list.
    /** @var array<string, mixed> $input */
    $input = $this->wp->restSanitizeValueFromSchema($input, ['type' => 'object', 'properties' => $this->getInputProperties()]);
    $warnings = [];
    $errors = [];
    $subject = $this->sanitizeField($input, 'subject', $warnings);
    if ($subject === '') {
      $errors[] = ['field' => 'subject', 'message' => __('subject is empty after removing unsupported content.', 'mailpoet')];
    }
    $prepared = [
      'subject' => $subject,
      'preheader' => $this->sanitizeField($input, 'preheader', $warnings),
      'markup' => null,
      'segments' => null,
      'errors' => $errors,
      'warnings' => $warnings,
    ];

    if (is_array($input['list_ids'] ?? null)) {
      $listIds = array_map(function ($id): int {
        return is_numeric($id) ? (int)$id : 0;
      }, $input['list_ids']);
      $unknownIds = array_diff($listIds, $this->findUsableListIds($listIds));
      if ($unknownIds) {
        // translators: %s is a list of numeric IDs.
        $prepared['errors'][] = ['field' => 'list_ids', 'message' => sprintf(__('These lists do not exist or cannot be used: %s.', 'mailpoet'), implode(', ', $unknownIds))];
      }
      $prepared['segments'] = array_map(function (int $id): array {
        return ['id' => $id];
      }, $listIds);
    }

    if (is_array($input['blocks'] ?? null)) {
      /** @var array<int, array<string, mixed>> $blocks The input schema allows only objects here. */
      $blocks = $input['blocks'];
      $draft = $this->draftBlocks->prepare($blocks);
      $prepared['markup'] = $draft['markup'];
      $prepared['errors'] = array_merge($prepared['errors'], $draft['errors']);
      $prepared['warnings'] = array_merge($prepared['warnings'], $draft['warnings']);
    }

    return $prepared;
  }

  /**
   * @param array<string, mixed> $input
   * @param string[] $warnings
   */
  private function sanitizeField(array $input, string $field, array &$warnings): ?string {
    if (!is_string($input[$field] ?? null)) {
      return null;
    }
    $removed = false;
    $value = $this->draftBlocks->sanitizeText($input[$field], $removed);
    if ($removed) {
      $warnings[] = $this->draftBlocks->getRemovedContentWarning($field);
    }
    return $value;
  }

  /**
   * @param int[] $ids
   * @return int[]
   */
  private function findUsableListIds(array $ids): array {
    $criteria = ['id' => $ids, 'deletedAt' => null];
    $types = $this->wooCommerceSegment->getNewsletterSegmentTypes();
    if ($types) {
      $criteria['type'] = $types;
    }
    return array_map(function ($segment): int {
      return (int)$segment->getId();
    }, $this->segmentsRepository->findBy($criteria));
  }

  /** @param DraftError[] $errors */
  public function getInvalidInputError(array $errors): \WP_Error {
    $messages = array_map(function (array $error): string {
      return $error['field'] . ': ' . $error['message'];
    }, $errors);
    return new \WP_Error(
      'mailpoet_invalid_blocks',
      __('Nothing was saved. Fix these fields and try again.', 'mailpoet') . ' ' . implode(' ', $messages),
      ['status' => 400, 'errors' => $errors]
    );
  }

  public function buildOutput(NewsletterEntity $newsletter): array {
    $postId = (int)$newsletter->getWpPostId();
    return [
      'id' => (int)$newsletter->getId(),
      'wp_post_id' => $postId,
      'subject' => $newsletter->getSubject(),
      'status' => $newsletter->getStatus(),
      'edit_url' => $this->wp->adminUrl('post.php?post=' . $postId . '&action=edit'),
      'list_ids' => array_values(array_map('intval', $newsletter->getSegmentIds())),
      'version' => $this->getVersion($newsletter),
    ];
  }

  public function getOutputProperties(): array {
    return [
      'id' => ['type' => 'integer'],
      'wp_post_id' => ['type' => 'integer'],
      'subject' => ['type' => 'string'],
      'status' => ['type' => 'string'],
      'edit_url' => ['type' => 'string'],
      'list_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
      'version' => [
        'type' => 'string',
        'description' => __('Pass this to mailpoet/update-newsletter-draft.', 'mailpoet'),
      ],
    ];
  }

  public function getWarningsProperty(): array {
    return [
      'type' => 'array',
      'items' => ['type' => 'string'],
      'description' => __('Which fields lost personalization tags, shortcodes or HTML comments. Tell the user about them.', 'mailpoet'),
    ];
  }
}
