<?php

namespace MailPoet\Premium\DynamicSegments\Persistence;

use MailPoet\Models\Model;
use MailPoet\Premium\DynamicSegments\Exceptions\ErrorSavingException;
use MailPoet\Premium\DynamicSegments\Filters\Filter;
use MailPoet\Premium\Models\DynamicSegment;
use MailPoet\Premium\Models\DynamicSegmentFilter;

class Saver {

  /**
   * @param DynamicSegment $segment
   *
   * @return integer
   * @throws ErrorSavingException
   */
  function save(DynamicSegment $segment) {
    $db = \ORM::get_db();
    $db->beginTransaction();

    $data_segment = $this->saveSegment($db, $segment);
    $this->saveFilters($db, $segment, $data_segment->id());
    $db->commit();
    return $data_segment->id();
  }

  /**
   * @throws ErrorSavingException
   */
  private function saveSegment(\PDO $db, DynamicSegment $segment) {
    $segment->save();
    $this->checkErrors($db, $segment);
    return $segment;
  }

  /**
   * @throws ErrorSavingException
   */
  private function checkErrors(\PDO $db, Model $model) {
    $errors = $model->getErrors();
    if ($errors) {
      $code = null;
      if (array_key_exists(Model::DUPLICATE_RECORD, $errors)) {
        $code = Model::DUPLICATE_RECORD;
      }
      $db->rollBack();
      throw new ErrorSavingException(join(", ", $model->getErrors()), $code);
    }
  }

  /**
   * @throws ErrorSavingException
   */
  private function saveFilters(\PDO $db, DynamicSegment $segment, $saved_data_id) {
    $this->deleteFilters($segment);
    foreach ($segment->getFilters() as $filter) {
      $data_filter = $this->saveFilter($filter, $saved_data_id);
      $this->checkErrors($db, $data_filter);
    }
  }

  private function deleteFilters(DynamicSegment $segment) {
    DynamicSegmentFilter::deleteAllBySegmentIds([$segment->id]);
  }

  private function saveFilter(Filter $filter, $data_segment_id) {
    $data_filter = DynamicSegmentFilter::create();
    if ($data_filter instanceof DynamicSegmentFilter) {
      $data_filter->segment_id = $data_segment_id;
      $data_filter->filter_data = $filter->toArray();
      $data_filter->save();
    }
    return $data_filter;
  }
}
