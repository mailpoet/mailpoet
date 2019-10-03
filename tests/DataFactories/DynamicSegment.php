<?php

namespace MailPoet\Test\DataFactories;

use MailPoet\DynamicSegments\Mappers\FormDataMapper;
use MailPoet\DynamicSegments\Persistence\Saver;
use MailPoet\Models\DynamicSegment as DynamicSegmentModel;
use MailPoet\Test\DataFactories\Segment;

class DynamicSegment extends Segment {

  private $filter_data = [];

  public function withUserRoleFilter($role) {
    $this->filter_data['segmentType'] = 'userRole';
    $this->filter_data['wordpressRole'] = $role;
    return $this;
  }

  /** @return DynamicSegmentModel */
  public function create() {
    $segment = DynamicSegmentModel::createOrUpdate($this->data);
    if (!empty($this->filter_data['segmentType'])) {
      $segment = $this->createFilter($segment, $this->filter_data);
    }
    return $segment;
  }

  private function createFilter(DynamicSegmentModel $segment, array $filter_data) {
    $data = array_merge($segment->asArray(), $filter_data);
    $mapper = new FormDataMapper();
    $saver = new Saver();
    $dynamic_segment = $mapper->mapDataToDB($data);
    $saver->save($dynamic_segment);
    return $dynamic_segment;
  }

}
