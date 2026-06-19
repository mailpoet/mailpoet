<?php declare(strict_types = 1);

namespace MailPoet\Logging\RestApi;

/**
 * Validates and normalizes the logs filter object (`from`/`to`/`name`/`level`)
 * shared by the logs listing and deletion endpoints, so both interpret the same
 * filter shape identically. Host endpoints must also use
 * {@see \MailPoet\API\REST\ListingRequestValidationTrait} for the date helpers
 * and namespaced error codes.
 */
trait LogsFilterTrait {
  /**
   * @param mixed $filters
   * @return array{from?: string, to?: string, name?: string[], level?: int[]}
   */
  protected function validateAndNormalizeLogsFilter($filters): array {
    if ($filters === null || $filters === []) {
      return [];
    }
    if (!is_array($filters)) {
      throw $this->listingValidationError(__('Filters must be an object.', 'mailpoet'), 'filter');
    }
    $allowedKeys = ['from', 'to', 'name', 'level'];
    $normalizedFilters = [];
    foreach ($filters as $key => $value) {
      if (!is_string($key) || !in_array($key, $allowedKeys, true)) {
        throw $this->listingValidationError(__('Unsupported logs filter.', 'mailpoet'), 'filter');
      }
      $normalizedFilters[$key] = $value;
    }

    $criteria = [];
    $names = $this->normalizeLogsNameFilter($normalizedFilters);
    if ($names !== []) {
      $criteria['name'] = $names;
    }
    $levels = $this->normalizeLogsLevelFilter($normalizedFilters);
    if ($levels !== []) {
      $criteria['level'] = $levels;
    }

    $from = $this->validateDateFilter($normalizedFilters, 'from');
    $to = $this->validateDateFilter($normalizedFilters, 'to');
    $this->validateDateRange($from, $to);
    if ($from) {
      $criteria['from'] = $from->format('Y-m-d');
    }
    if ($to) {
      $criteria['to'] = $to->format('Y-m-d');
    }

    return $criteria;
  }

  /**
   * @param array<string, mixed> $filters
   * @return string[]
   */
  private function normalizeLogsNameFilter(array $filters): array {
    if (!array_key_exists('name', $filters) || $filters['name'] === '' || $filters['name'] === []) {
      return [];
    }
    if (!is_array($filters['name'])) {
      throw $this->listingValidationError(__('The name filter must be an array of strings.', 'mailpoet'), 'name');
    }
    $names = [];
    foreach ($filters['name'] as $value) {
      if (!is_string($value)) {
        throw $this->listingValidationError(__('The name filter must be an array of strings.', 'mailpoet'), 'name');
      }
      $names[] = $value;
    }
    return array_values(array_unique($names));
  }

  /**
   * @param array<string, mixed> $filters
   * @return int[]
   */
  private function normalizeLogsLevelFilter(array $filters): array {
    if (!array_key_exists('level', $filters) || $filters['level'] === '' || $filters['level'] === []) {
      return [];
    }
    if (!is_array($filters['level'])) {
      throw $this->listingValidationError(__('The level filter must be an array of integers.', 'mailpoet'), 'level');
    }
    $levels = [];
    foreach ($filters['level'] as $value) {
      $integer = $this->getIntegerValue($value);
      if ($integer === null) {
        throw $this->listingValidationError(__('The level filter must be an array of integers.', 'mailpoet'), 'level');
      }
      $levels[] = $integer;
    }
    return array_values(array_unique($levels));
  }
}
