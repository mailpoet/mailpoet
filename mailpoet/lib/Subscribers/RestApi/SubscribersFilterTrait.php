<?php declare(strict_types = 1);

namespace MailPoet\Subscribers\RestApi;

use DateTimeImmutable;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscriberListingRepository;

/**
 * Validates the subscribers filter object shared by the listing and bulk action
 * endpoints. The repository normalizes the values, so this only rejects what it
 * would silently drop. Host endpoints must also use
 * {@see \MailPoet\API\REST\ListingRequestValidationTrait} for the date helpers
 * and namespaced error codes.
 */
trait SubscribersFilterTrait {
  /**
   * Empty values ('', null, []) count as absent: the UI sends `filter[segment]=` for "All lists".
   *
   * @param mixed $filters
   */
  protected function validateSubscribersFilter($filters): void {
    if ($filters === null || $filters === []) {
      return;
    }
    if (!is_array($filters)) {
      throw $this->listingValidationError(__('Filters must be an object.', 'mailpoet'), 'filter');
    }
    $values = [];
    foreach ($filters as $key => $value) {
      if (!is_string($key) || !in_array($key, $this->getSubscribersFilterKeys(), true)) {
        throw $this->listingValidationError(__('Unsupported subscribers filter.', 'mailpoet'), 'filter');
      }
      if ($value !== '' && $value !== null && $value !== []) {
        $values[$key] = $value;
      }
    }

    foreach (['segment' => true, 'tag' => false] as $key => $allowWithoutList) {
      if (array_key_exists($key, $values) && !$this->isSubscribersFilterId($values[$key], $allowWithoutList)) {
        throw $this->listingValidationError(__('The filter must be a list or tag ID.', 'mailpoet'), $key);
      }
    }

    $this->validateSubscribersFilterSets($values, ['segment_in', 'segment_not_in'], function($value): bool {
      return $this->isSubscribersFilterId($value, true);
    });
    $this->validateSubscribersFilterSets($values, ['tag_in', 'tag_not_in'], function($value): bool {
      return $this->isSubscribersFilterId($value, false);
    });
    $this->validateSubscribersFilterSets($values, ['status_in', 'status_not_in'], function($value): bool {
      return in_array($value, $this->getSubscribersFilterStatuses(), true);
    });
    $this->validateSubscribersFilterSets($values, ['source_in', 'source_not_in'], function($value): bool {
      return in_array($value, $this->getSubscribersFilterSources(), true);
    });

    $this->validateSubscribersFilterScores($values);

    $createdFrom = $this->validateDateFilter($values, 'created_from');
    $createdTo = $this->validateDateFilter($values, 'created_to');
    $subscribedFrom = $this->validateDateFilter($values, 'subscribed_from');
    $subscribedTo = $this->validateDateFilter($values, 'subscribed_to');
    $this->validateDateRange($createdFrom, $createdTo);
    $this->validateDateRange($subscribedFrom, $subscribedTo);

    $this->validateSubscribersLegacyFilters($values);
  }

  /** @return string[] */
  private function getSubscribersFilterKeys(): array {
    return [
      'segment', 'tag',
      'segment_in', 'segment_not_in', 'tag_in', 'tag_not_in',
      'status_in', 'status_not_in', 'source_in', 'source_not_in',
      'score_gt', 'score_lt', 'score_min', 'score_max',
      'created_from', 'created_to', 'subscribed_from', 'subscribed_to',
      'statusInclude', 'statusExclude', 'engagementScoreInclude', 'engagementScoreExclude',
      'createdAtFrom', 'createdAtTo',
    ];
  }

  /** @return string[] */
  private function getSubscribersFilterStatuses(): array {
    return SubscriberListingRepository::SUPPORTED_STATUSES;
  }

  /** @return string[] */
  private function getSubscribersFilterSources(): array {
    return Source::ALL;
  }

  /** @return string[] */
  private function getSubscribersFilterEngagementScores(): array {
    return [
      SubscriberListingRepository::ENGAGEMENT_SCORE_UNKNOWN,
      SubscriberListingRepository::ENGAGEMENT_SCORE_DORMANT,
      SubscriberListingRepository::ENGAGEMENT_SCORE_LOW,
      SubscriberListingRepository::ENGAGEMENT_SCORE_GOOD,
      SubscriberListingRepository::ENGAGEMENT_SCORE_EXCELLENT,
    ];
  }

  /** @param mixed $value */
  private function isSubscribersFilterId($value, bool $allowWithoutList): bool {
    if ($allowWithoutList && $value === SubscriberListingRepository::FILTER_WITHOUT_LIST) {
      return true;
    }
    $id = $this->getIntegerValue($value);
    return $id !== null && $id > 0;
  }

  /**
   * @param array<string, mixed> $values
   * @param string[] $keys
   */
  private function validateSubscribersFilterSets(array $values, array $keys, callable $isValid): void {
    foreach ($keys as $key) {
      if (array_key_exists($key, $values) && !$this->isValidSubscribersFilterSet($values[$key], $isValid)) {
        throw $this->listingValidationError(
          __('The filter must be an array of up to 100 supported values.', 'mailpoet'),
          $key
        );
      }
    }
  }

  /** @param mixed $set */
  private function isValidSubscribersFilterSet($set, callable $isValid): bool {
    if (!is_array($set) || count($set) > 100) {
      return false;
    }
    foreach ($set as $value) {
      if (!$isValid($value)) {
        return false;
      }
    }
    return true;
  }

  /** @param array<string, mixed> $values */
  private function validateSubscribersFilterScores(array $values): void {
    foreach (['score_gt', 'score_lt', 'score_min', 'score_max'] as $key) {
      if (array_key_exists($key, $values) && !is_numeric($values[$key])) {
        throw $this->listingValidationError(__('Score filters must be numbers.', 'mailpoet'), $key);
      }
    }
    $min = $values['score_min'] ?? null;
    $max = $values['score_max'] ?? null;
    if (is_numeric($min) && is_numeric($max) && (float)$min > (float)$max) {
      throw $this->listingValidationError(
        __('The minimum score must be lower than or equal to the maximum score.', 'mailpoet'),
        'score_range'
      );
    }
  }

  /** @param array<string, mixed> $values */
  private function validateSubscribersLegacyFilters(array $values): void {
    $legacySets = [
      'statusInclude' => $this->getSubscribersFilterStatuses(),
      'statusExclude' => $this->getSubscribersFilterStatuses(),
      'engagementScoreInclude' => $this->getSubscribersFilterEngagementScores(),
      'engagementScoreExclude' => $this->getSubscribersFilterEngagementScores(),
    ];
    foreach ($legacySets as $key => $allowed) {
      if (!array_key_exists($key, $values)) {
        continue;
      }
      $set = is_array($values[$key]) ? $values[$key] : [$values[$key]];
      $isValid = function($value) use ($allowed): bool {
        return in_array($value, $allowed, true);
      };
      if (!$this->isValidSubscribersFilterSet($set, $isValid)) {
        throw $this->listingValidationError(
          __('The filter must contain up to 100 supported values.', 'mailpoet'),
          $key
        );
      }
    }
    foreach (['createdAtFrom', 'createdAtTo'] as $key) {
      if (array_key_exists($key, $values) && !$this->isParsableSubscribersDateTime($values[$key])) {
        throw $this->listingValidationError(__('The filter must be a valid date.', 'mailpoet'), $key);
      }
    }
  }

  /** @param mixed $value */
  private function isParsableSubscribersDateTime($value): bool {
    if (!is_string($value)) {
      return false;
    }
    try {
      new DateTimeImmutable($value);
      return true;
    } catch (\Exception $e) {
      return false;
    }
  }
}
