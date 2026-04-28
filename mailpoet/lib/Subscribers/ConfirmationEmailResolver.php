<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\SegmentEntity;

class ConfirmationEmailResolver {
  /**
   * Resolves which confirmation email and page to use based on segment settings.
   *
   * Rules:
   * - If no segments have custom settings, returns [null, null] (use global).
   * - If exactly one unique custom email/page is set, use it.
   * - If segments conflict (different custom values), fall back to global (null).
   *
   * Email and page are resolved independently.
   *
   * @param SegmentEntity[] $segments
   * @return array{0: int|null, 1: int|null} [confirmationEmailId, confirmationPageId]
   */
  public function resolveFromSegments(array $segments): array {
    $emailIds = [];
    $pageIds = [];

    foreach ($segments as $segment) {
      $emailId = $segment->getConfirmationEmailId();
      if ($emailId !== null) {
        $emailIds[] = $emailId;
      }

      $pageId = $segment->getConfirmationPageId();
      if ($pageId !== null) {
        $pageIds[] = $pageId;
      }
    }

    $resolvedEmailId = $this->resolveValue($emailIds);
    $resolvedPageId = $this->resolveValue($pageIds);

    return [$resolvedEmailId, $resolvedPageId];
  }

  /**
   * @param int[] $values
   */
  private function resolveValue(array $values): ?int {
    $unique = array_unique($values);
    if (count($unique) === 1) {
      return $unique[0];
    }
    return null;
  }
}
