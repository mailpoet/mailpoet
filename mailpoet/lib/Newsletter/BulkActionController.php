<?php declare(strict_types = 1);

namespace MailPoet\Newsletter;

use MailPoet\Listing\ListingDefinition;
use MailPoet\Newsletter\Listing\NewsletterListingRepository;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\WP\Functions as WPFunctions;

/**
 * Centralized newsletter bulk-action dispatcher used by both the legacy
 * {@see \MailPoet\API\JSON\v1\Newsletters::bulkAction()} JSON endpoint and
 * the REST endpoint at `mailpoet/v1/newsletters/bulk-action`. The
 * `export_stats` action is handled by the REST endpoint directly because it
 * is asynchronous and premium-gated; this controller covers the synchronous
 * destructive actions both transports share.
 */
class BulkActionController {
  public const ACTION_TRASH = 'trash';
  public const ACTION_RESTORE = 'restore';
  public const ACTION_DELETE = 'delete';

  public const SUPPORTED_ACTIONS = [
    self::ACTION_TRASH,
    self::ACTION_RESTORE,
    self::ACTION_DELETE,
  ];

  /** @var NewsletterListingRepository */
  private $newsletterListingRepository;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterDeleteController */
  private $newsletterDeleteController;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewsletterListingRepository $newsletterListingRepository,
    NewslettersRepository $newslettersRepository,
    NewsletterDeleteController $newsletterDeleteController,
    WPFunctions $wp
  ) {
    $this->newsletterListingRepository = $newsletterListingRepository;
    $this->newslettersRepository = $newslettersRepository;
    $this->newsletterDeleteController = $newsletterDeleteController;
    $this->wp = $wp;
  }

  /**
   * @return array{action: string, count: int, ids: int[]}
   * @throws BulkActionException
   */
  public function execute(string $action, ListingDefinition $definition): array {
    if (!in_array($action, self::SUPPORTED_ACTIONS, true)) {
      throw new BulkActionException(
        // translators: %s is the offending bulk-action name.
        sprintf(__("Invalid bulk action '%s' provided.", 'mailpoet'), $action),
        'mailpoet_newsletters_invalid_bulk_action',
        400
      );
    }

    $ids = array_values(array_map('intval', $this->newsletterListingRepository->getActionableIds($definition)));

    if ($action === self::ACTION_TRASH) {
      $this->newslettersRepository->bulkTrash($ids);
    } elseif ($action === self::ACTION_RESTORE) {
      $this->newslettersRepository->bulkRestore($ids);
    } elseif ($action === self::ACTION_DELETE) {
      // Hooks fire around the cascading delete so premium add-ons (and any
      // third-party listeners) keep observing the same lifecycle they did
      // under the legacy JSON endpoint.
      $this->wp->doAction('mailpoet_api_newsletters_delete_before', $ids);
      $this->newsletterDeleteController->bulkDelete($ids);
      $this->wp->doAction('mailpoet_api_newsletters_delete_after', $ids);
    }

    return [
      'action' => $action,
      'count' => count($ids),
      'ids' => $ids,
    ];
  }
}
