<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Config\AccessControl;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class RecentNewslettersAbility implements Ability {
  public const NAME = 'mailpoet/list-recent-newsletters';

  private const DEFAULT_LIMIT = 10;
  private const MAX_LIMIT = 20;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterReportSummary */
  private $summary;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    NewsletterReportSummary $summary,
    WPFunctions $wp
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->summary = $summary;
    $this->wp = $wp;
  }

  public function getName(): string {
    return self::NAME;
  }

  public function getRegistrationArgs(): array {
    return [
      'label' => __('List recent MailPoet newsletters', 'mailpoet'),
      'description' => __('The most recently sent MailPoet newsletters (one-off campaigns, not automations or post notifications), newest first, with the summary statistics of the MailPoet campaign listing. Rates are percentages of tracked_sent. Judge performance by clicks and revenue first: opens are inflated by email clients that load images automatically (e.g. Apple Mail Privacy Protection), counted in machine_opens. Contains no subscriber data.', 'mailpoet'),
      'input_schema' => [
        'type' => 'object',
        'properties' => [
          'limit' => [
            'type' => 'integer',
            'minimum' => 1,
            'maximum' => self::MAX_LIMIT,
            'default' => self::DEFAULT_LIMIT,
            'description' => __('How many newsletters to return.', 'mailpoet'),
          ],
        ],
        'additionalProperties' => false,
        'default' => ['limit' => self::DEFAULT_LIMIT],
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
    $limit = (int)($input['limit'] ?? self::DEFAULT_LIMIT);
    $detailedStatisticsAvailable = $this->summary->isDetailedStatisticsAvailable();
    $newsletters = $this->newslettersRepository->findLatestSentStandard($limit);
    return [
      'newsletters' => $this->summary->getItems($newsletters, $detailedStatisticsAvailable),
      'detailed_statistics_available' => $detailedStatisticsAvailable,
    ];
  }

  private function getOutputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'newsletters' => [
          'type' => 'array',
          'items' => [
            'type' => 'object',
            'properties' => $this->summary->getItemSchemaProperties(),
          ],
        ],
        'detailed_statistics_available' => [
          'type' => 'boolean',
          'description' => __('False when the plan does not include detailed analytics, so unsubscribe_reasons is left out.', 'mailpoet'),
        ],
      ],
    ];
  }
}
