<?php declare(strict_types = 1);

namespace MailPoet\API\REST;

use DateTimeImmutable;

/**
 * Shared request validation for DataViews-backed listing endpoints
 * (see {@see AbstractListingEndpoint}). Provides the reusable building blocks —
 * sort field/order, pagination integers, and `yyyy-MM-dd` date filters — so each
 * listing only declares its own allowed sort fields and filter keys.
 *
 * Error codes are namespaced per listing via {@see getListingValidationErrorPrefix()}
 * so a 400 still says which listing rejected the request
 * (e.g. `mailpoet_logs_invalid_orderby`).
 */
trait ListingRequestValidationTrait {
  /** Lowercase listing slug used to build error codes, e.g. `logs` or `forms`. */
  abstract protected function getListingValidationErrorPrefix(): string;

  private function listingValidationError(string $message, string $suffix): ApiException {
    return new ApiException(
      $message,
      400,
      'mailpoet_' . $this->getListingValidationErrorPrefix() . '_invalid_' . $suffix
    );
  }

  /**
   * @param mixed $sortField
   * @param string[] $allowedFields
   */
  protected function validateSortField($sortField, array $allowedFields): void {
    if ($sortField === null || $sortField === '') {
      return;
    }
    if (!is_string($sortField) || !in_array($sortField, $allowedFields, true)) {
      throw $this->listingValidationError(
        sprintf(
          // translators: %s is a comma-separated list of allowed sort fields.
          __('Unsupported sort field. Allowed values are: %s.', 'mailpoet'),
          implode(', ', $allowedFields)
        ),
        'orderby'
      );
    }
  }

  /** @param mixed $sortOrder */
  protected function validateSortOrder($sortOrder): void {
    if ($sortOrder === null || $sortOrder === '') {
      return;
    }
    if (!is_string($sortOrder) || !in_array(strtolower($sortOrder), ['asc', 'desc'], true)) {
      throw $this->listingValidationError(
        sprintf(
          // translators: %s is a comma-separated list of allowed sort orders.
          __('Unsupported sort order. Allowed values are: %s.', 'mailpoet'),
          implode(', ', ['asc', 'desc'])
        ),
        'order'
      );
    }
  }

  protected function validatePagination(Request $request): void {
    $this->validatePositiveInteger($request->getParam('page'), 'page', 1, self::MAX_PAGE);
    $this->validatePositiveInteger($request->getParam('per_page'), 'per_page', 1, self::MAX_PER_PAGE);
    $this->validatePositiveInteger($request->getParam('limit'), 'limit', 1, self::MAX_PER_PAGE);
    $this->validatePositiveInteger($request->getParam('offset'), 'offset', 0, self::MAX_PAGE);
  }

  /** @param mixed $value */
  private function validatePositiveInteger($value, string $name, int $min, int $max): void {
    if ($value === null || $value === '') {
      return;
    }
    $integer = $this->getIntegerValue($value);
    if ($integer === null || $integer < $min || $integer > $max) {
      throw $this->listingValidationError(
        sprintf(
          // translators: %1$s is a request parameter name, %2$d is the maximum accepted value.
          __('%1$s must be an integer no greater than %2$d.', 'mailpoet'),
          $name,
          $max
        ),
        $name
      );
    }
  }

  /** @param mixed $value */
  private function getIntegerValue($value): ?int {
    if (is_int($value)) {
      return $value;
    }
    if (is_string($value) && ctype_digit($value)) {
      return (int)$value;
    }
    return null;
  }

  /**
   * @param array<string, mixed> $filters
   */
  protected function validateDateFilter(array $filters, string $field): ?DateTimeImmutable {
    if (!array_key_exists($field, $filters) || $filters[$field] === '') {
      return null;
    }
    if (!is_string($filters[$field])) {
      throw $this->listingValidationError(
        __('Date filters must use the YYYY-MM-DD format.', 'mailpoet'),
        $field
      );
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $filters[$field]);
    $errors = DateTimeImmutable::getLastErrors();
    if (!$date || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format('Y-m-d') !== $filters[$field]) {
      throw $this->listingValidationError(
        __('Date filters must use the YYYY-MM-DD format.', 'mailpoet'),
        $field
      );
    }
    return $date;
  }

  protected function validateDateRange(?DateTimeImmutable $from, ?DateTimeImmutable $to): void {
    if ($from && $to && $from > $to) {
      throw $this->listingValidationError(
        __('The from date must be before or equal to the to date.', 'mailpoet'),
        'date_range'
      );
    }
  }
}
