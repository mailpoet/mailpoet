<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\RestApi;

use MailPoet\Newsletter\Listing\NewsletterListingRepository;
use MailPoet\Newsletter\RestApi\Endpoints\NewslettersListingEndpoint;

/**
 * Validates the `filter` object shared by the newsletters listing and bulk
 * action endpoints, so “select all” acts on exactly the rows the listing shows.
 * Host endpoints must also use {@see \MailPoet\API\REST\ListingRequestValidationTrait}.
 */
trait NewslettersFilterValidationTrait {
  /**
   * @param mixed $filters
   * @param mixed $type
   */
  protected function validateNewslettersFilters($filters, $type): void {
    if ($filters === null || $filters === []) {
      return;
    }
    if (!is_array($filters)) {
      throw $this->listingValidationError(__('Filters must be an object.', 'mailpoet'), 'filter');
    }
    $allowedKeys = ['status', 'segment', 'segment_ids', 'segment_operator', 'sent_at_from', 'sent_at_to'];
    foreach (array_keys($filters) as $key) {
      if (!is_string($key) || !in_array($key, $allowedKeys, true)) {
        throw $this->listingValidationError(__('Unsupported newsletters filter.', 'mailpoet'), 'filter');
      }
    }

    $this->validateNewslettersStatusFilter($filters['status'] ?? null, $type);
    $this->validateNewslettersSegmentFilters($filters);
    $this->validateDateRange(
      $this->validateDateFilter($filters, 'sent_at_from'),
      $this->validateDateFilter($filters, 'sent_at_to')
    );
  }

  /**
   * @param mixed $status
   * @param mixed $type
   */
  private function validateNewslettersStatusFilter($status, $type): void {
    if ($status === null || $status === '' || $status === []) {
      return;
    }
    $allowed = is_string($type) && in_array($type, NewslettersListingEndpoint::SUPPORTED_TYPES, true)
      ? NewsletterListingRepository::getSupportedStatusesForType($type)
      : [];
    $statuses = is_array($status) ? $status : [$status];
    foreach ($statuses as $value) {
      if (!is_string($value) || !in_array($value, $allowed, true)) {
        throw $this->listingValidationError(
          $allowed === []
            ? __('A status filter requires a supported email type.', 'mailpoet')
            : sprintf(
              // translators: %s is a comma-separated list of allowed email statuses.
              __('Unsupported status filter. Allowed values are: %s.', 'mailpoet'),
              implode(', ', $allowed)
            ),
          'status'
        );
      }
    }
  }

  /** @param array<mixed> $filters */
  private function validateNewslettersSegmentFilters(array $filters): void {
    $segment = $filters['segment'] ?? null;
    if ($segment !== null && $segment !== '' && !NewsletterListingRepository::isListId($segment)) {
      throw $this->listingValidationError(__('The list filter must be a list ID.', 'mailpoet'), 'segment');
    }

    $segmentIds = $filters['segment_ids'] ?? null;
    $hasSegmentIds = $segmentIds !== null && $segmentIds !== '' && $segmentIds !== [];
    if ($hasSegmentIds) {
      if (!is_array($segmentIds)) {
        throw $this->listingValidationError(__('The list IDs filter must be an array of list IDs.', 'mailpoet'), 'segment_ids');
      }
      foreach ($segmentIds as $segmentId) {
        if (!NewsletterListingRepository::isListId($segmentId)) {
          throw $this->listingValidationError(__('The list IDs filter must be an array of list IDs.', 'mailpoet'), 'segment_ids');
        }
      }
    }

    $operator = $filters['segment_operator'] ?? null;
    $hasOperator = $operator !== null && $operator !== '';
    if (($hasOperator || $hasSegmentIds) && !in_array($operator, ['isAny', 'isNone'], true)) {
      throw $this->listingValidationError(
        __('Unsupported list operator. Allowed values are: isAny, isNone.', 'mailpoet'),
        'segment_operator'
      );
    }
  }
}
