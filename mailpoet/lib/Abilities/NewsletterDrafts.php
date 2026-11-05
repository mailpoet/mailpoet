<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Segments\SegmentsSimpleListRepository;
use MailPoet\Segments\WooCommerce;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * What the abilities working with newsletter drafts share: the input fields, how they are
 * checked, and the output.
 */
class NewsletterDrafts {
  /** @var NewsletterDraftBlocks */
  private $draftBlocks;

  /** @var SegmentsSimpleListRepository */
  private $segmentsListRepository;

  /** @var WooCommerce */
  private $wooCommerceSegment;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewsletterDraftBlocks $draftBlocks,
    SegmentsSimpleListRepository $segmentsListRepository,
    WooCommerce $wooCommerceSegment,
    WPFunctions $wp
  ) {
    $this->draftBlocks = $draftBlocks;
    $this->segmentsListRepository = $segmentsListRepository;
    $this->wooCommerceSegment = $wooCommerceSegment;
    $this->wp = $wp;
  }

  /**
   * The same lists and counts as the Send to control in the send step.
   *
   * @return array<int, array{id: int, name: string, type: string, subscribers: int}>
   */
  public function getLists(): array {
    $types = $this->wooCommerceSegment->shouldShowWooCommerceSegment() ? [] : SegmentEntity::NON_WOO_RELATED_TYPES;
    return array_map(function (array $segment): array {
      return [
        'id' => (int)$segment['id'],
        'name' => $segment['name'],
        'type' => $segment['type'],
        'subscribers' => $segment['subscribers'],
      ];
    }, $this->segmentsListRepository->getListWithSubscribedSubscribersCounts($types));
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
   * @return array{subject: ?string, preheader: ?string, markup: ?string, segments: ?array<int, array{id: int}>, errors: array<int, array{field: string, message: string}>, warnings: string[]}
   */
  public function prepareInput(array $input): array {
    $prepared = ['subject' => null, 'preheader' => null, 'markup' => null, 'segments' => null, 'errors' => [], 'warnings' => []];

    if (is_string($input['subject'] ?? null)) {
      $removed = false;
      $prepared['subject'] = $this->draftBlocks->sanitizeText($input['subject'], $removed);
      if ($removed) {
        $prepared['warnings'][] = $this->draftBlocks->getRemovedContentWarning('subject');
      }
      if ($prepared['subject'] === '') {
        $prepared['errors'][] = ['field' => 'subject', 'message' => __('subject is empty after removing unsupported content.', 'mailpoet')];
      }
    }

    if (is_string($input['preheader'] ?? null)) {
      $removed = false;
      $prepared['preheader'] = $this->draftBlocks->sanitizeText($input['preheader'], $removed);
      if ($removed) {
        $prepared['warnings'][] = $this->draftBlocks->getRemovedContentWarning('preheader');
      }
    }

    if (is_array($input['list_ids'] ?? null)) {
      $knownIds = array_column($this->getLists(), 'id');
      $listIds = array_map(function ($id): int {
        return is_int($id) ? $id : 0;
      }, $input['list_ids']);
      $unknownIds = array_diff($listIds, $knownIds);
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

  /** @param array<int, array{field: string, message: string}> $errors */
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

  /** @param string[] $warnings */
  public function buildOutput(NewsletterEntity $newsletter, array $warnings): array {
    $postId = (int)$newsletter->getWpPostId();
    return [
      'id' => (int)$newsletter->getId(),
      'wp_post_id' => $postId,
      'subject' => $newsletter->getSubject(),
      'status' => $newsletter->getStatus(),
      'edit_url' => $this->wp->adminUrl('post.php?post=' . $postId . '&action=edit'),
      'list_ids' => array_values(array_map('intval', $newsletter->getSegmentIds())),
      'warnings' => $warnings,
    ];
  }

  public function getOutputProperties(): array {
    return [
      'id' => ['type' => 'integer'],
      'wp_post_id' => ['type' => 'integer'],
      'subject' => ['type' => 'string'],
      'status' => [
        'type' => 'string',
        'description' => __('The newsletter status. A new draft is always draft, but a repeated client_reference returns the current status.', 'mailpoet'),
      ],
      'edit_url' => ['type' => 'string'],
      'list_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
      'warnings' => [
        'type' => 'array',
        'items' => ['type' => 'string'],
        'description' => __('Which fields lost personalization tags, shortcodes or HTML comments. Tell the user about them.', 'mailpoet'),
      ],
    ];
  }
}
