<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Config\AccessControl;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class NewsletterDraftOptionsAbility implements Ability {
  public const NAME = 'mailpoet/get-newsletter-draft-options';

  private const RECENT_DRAFTS_LIMIT = 10;

  /** @var NewsletterDrafts */
  private $drafts;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewsletterDrafts $drafts,
    NewslettersRepository $newslettersRepository,
    WPFunctions $wp
  ) {
    $this->drafts = $drafts;
    $this->newslettersRepository = $newslettersRepository;
    $this->wp = $wp;
  }

  public function getName(): string {
    return self::NAME;
  }

  public function getRegistrationArgs(): array {
    return [
      'label' => __('Get MailPoet newsletter draft options', 'mailpoet'),
      'description' => __('Call this before writing a newsletter draft. Returns the subscriber lists with their subscribed counts, so you can ask the user which list the draft is for, the site colors to design with, and recent drafts to open with mailpoet/get-newsletter-draft.', 'mailpoet'),
      'input_schema' => [
        'type' => 'object',
        'additionalProperties' => false,
        'default' => [],
      ],
      'output_schema' => $this->getOutputSchema(),
      'execute_callback' => [$this, 'execute'],
      'permission_callback' => [$this, 'hasPermission'],
      'meta' => AbilitiesRegistrar::getReadOnlyMeta(),
    ];
  }

  public function hasPermission(): bool {
    return $this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_EMAILS);
  }

  /** @param array|null $input */
  public function execute($input = null): array {
    return [
      'lists' => $this->drafts->getLists(),
      'site_colors' => $this->getSiteColors(),
      'recent_drafts' => array_map(function (NewsletterEntity $newsletter): array {
        $updatedAt = $newsletter->getUpdatedAt();
        return [
          'id' => (int)$newsletter->getId(),
          'subject' => $newsletter->getSubject(),
          'updated_at' => $updatedAt ? $updatedAt->format('c') : null,
        ];
      }, $this->newslettersRepository->findRecentBlockEditorDrafts(self::RECENT_DRAFTS_LIMIT)),
    ];
  }

  /**
   * Themes can define palette colors as CSS variables, which mean nothing in an email.
   *
   * @return array<int, array{name: string, color: string}>
   */
  private function getSiteColors(): array {
    $palette = $this->wp->wpGetGlobalSettings(['color', 'palette', 'theme']);
    $colors = [];
    foreach (is_array($palette) ? $palette : [] as $item) {
      if (!is_array($item)) {
        continue;
      }
      $color = $this->wp->sanitizeHexColor(is_string($item['color'] ?? null) ? $item['color'] : '');
      if ($color !== '') {
        $colors[] = ['name' => is_string($item['name'] ?? null) ? $item['name'] : '', 'color' => $color];
      }
    }
    return $colors;
  }

  private function getOutputSchema(): array {
    return [
      'type' => 'object',
      'required' => ['lists', 'site_colors', 'recent_drafts'],
      'additionalProperties' => false,
      'properties' => [
        'lists' => [
          'type' => 'array',
          'items' => [
            'type' => 'object',
            'properties' => [
              'id' => ['type' => 'integer'],
              'name' => ['type' => 'string'],
              'type' => ['type' => 'string'],
              'subscribers' => ['type' => 'integer'],
            ],
          ],
        ],
        'site_colors' => [
          'type' => 'array',
          'items' => [
            'type' => 'object',
            'properties' => [
              'name' => ['type' => 'string'],
              'color' => ['type' => 'string'],
            ],
          ],
        ],
        'recent_drafts' => [
          'type' => 'array',
          'items' => [
            'type' => 'object',
            'properties' => [
              'id' => ['type' => 'integer'],
              'subject' => ['type' => 'string'],
              'updated_at' => ['type' => ['string', 'null']],
            ],
          ],
        ],
      ],
    ];
  }
}
