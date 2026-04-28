<?php declare(strict_types = 1);

namespace MailPoet\Segments\RestApi\Endpoints;

use MailPoet\API\REST\ApiException;

trait SegmentRequestValidationTrait {
  protected function validateGroup(?string $group): string {
    $group = $group ?: 'all';
    if (!in_array($group, ['all', 'trash'], true)) {
      throw new ApiException(
        __('Unsupported group. Allowed values are: all, trash.', 'mailpoet'),
        400,
        'mailpoet_segments_invalid_group'
      );
    }
    return $group;
  }

  protected function validateOrder(?string $order, string $default): string {
    $order = strtolower($order ?: $default);
    if (!in_array($order, ['asc', 'desc'], true)) {
      throw new ApiException(
        __('Unsupported sort order. Allowed values are: asc, desc.', 'mailpoet'),
        400,
        'mailpoet_segments_invalid_order'
      );
    }
    return $order;
  }

  protected function validatePage($page): int {
    if ($page === null || $page === '') {
      return 1;
    }
    if (!is_numeric($page) || (string)(int)$page !== (string)$page || (int)$page < 1 || (int)$page > 100000) {
      throw new ApiException(
        __('Page must be a positive integer.', 'mailpoet'),
        400,
        'mailpoet_segments_invalid_page'
      );
    }
    return (int)$page;
  }

  protected function validatePerPage($perPage, int $default): int {
    if ($perPage === null || $perPage === '') {
      return $default;
    }
    if (!is_numeric($perPage) || (string)(int)$perPage !== (string)$perPage || (int)$perPage < 1 || (int)$perPage > 100) {
      throw new ApiException(
        sprintf(
          // translators: %d is maximum items per page.
          __('Per page must be a positive integer no greater than %d.', 'mailpoet'),
          100
        ),
        400,
        'mailpoet_segments_invalid_per_page'
      );
    }
    return (int)$perPage;
  }

  protected function validateOffset($offset): int {
    if ($offset === null || $offset === '') {
      return 0;
    }
    if (
      !is_numeric($offset)
      || (string)(int)$offset !== (string)$offset
      || (int)$offset < 0
      || (int)$offset > 100000
    ) {
      throw new ApiException(
        __('Offset must be a non-negative integer no greater than 100000.', 'mailpoet'),
        400,
        'mailpoet_segments_invalid_offset'
      );
    }
    return (int)$offset;
  }

  /**
   * @param mixed $ids
   * @return int[]
   */
  protected function validateIds($ids): array {
    if (!is_array($ids) || $ids === []) {
      throw new ApiException(
        __('At least one segment id is required.', 'mailpoet'),
        400,
        'mailpoet_segments_ids_required'
      );
    }

    $validIds = [];
    foreach ($ids as $id) {
      if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
        throw new ApiException(
          __('Segment ids must be positive integers.', 'mailpoet'),
          400,
          'mailpoet_segments_invalid_ids'
        );
      }
      $id = (int)$id;
      if ($id < 1) {
        throw new ApiException(
          __('Segment ids must be positive integers.', 'mailpoet'),
          400,
          'mailpoet_segments_invalid_ids'
        );
      }
      $validIds[] = $id;
    }

    return array_values(array_unique($validIds));
  }
}
