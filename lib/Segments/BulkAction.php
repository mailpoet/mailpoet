<?php

namespace MailPoet\Segments;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Listing\BulkActionController;
use MailPoet\Models\Segment;

class BulkAction {
  /** @var BulkActionController */
  private $actionsController;

  /** @var array  */
  private $data;

  public function __construct(array $data) {
    $this->data = $data;
    $this->actionsController = ContainerWrapper::getInstance()->get(BulkActionController::class);
  }

  /**
   * @return array
   * @throws \Exception
   */
  public function apply() {
    if (!isset($this->data['listing']['filter']['segment'])) {
      throw new \InvalidArgumentException('Missing segment id');
    }
    $segment = Segment::findOne($this->data['listing']['filter']['segment']);
    if ($segment instanceof Segment) {
      $segment = $segment->asArray();
    }
    return $this->applySegment($segment);
  }

  /**
   * @param array|bool $segment
   *
   * @return array
   * @throws \Exception
   */
  private function applySegment($segment) {
    if (is_bool($segment)
      || in_array($segment['type'], [SegmentEntity::TYPE_DEFAULT, SegmentEntity::TYPE_WP_USERS, SegmentEntity::TYPE_WC_USERS], true)
    ) {
      return $this->actionsController->apply('\MailPoet\Models\Subscriber', $this->data);
    } elseif (isset($segment['type']) && $segment['type'] === SegmentEntity::TYPE_DYNAMIC) {
      return $this->actionsController->apply('\MailPoet\Models\SubscribersInDynamicSegment', $this->data);
    }
    throw new \InvalidArgumentException('No handler found for segment');
  }
}
