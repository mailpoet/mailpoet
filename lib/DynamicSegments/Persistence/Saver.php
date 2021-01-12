<?php

namespace MailPoet\DynamicSegments\Persistence;

use MailPoet\DynamicSegments\Exceptions\ErrorSavingException;
use MailPoet\DynamicSegments\Filters\Filter;
use MailPoet\Models\DynamicSegment;
use MailPoet\Models\DynamicSegmentFilter;
use MailPoet\Models\Model;
use MailPoetVendor\Idiorm\ORM;

class Saver {
  /**
   * @param DynamicSegment $segment
   *
   * @return int
   * @throws ErrorSavingException
   */
  public function save(DynamicSegment $segment) {
    $db = ORM::get_db();
    $db->beginTransaction();

    $dataSegment = $this->saveSegment($db, $segment);
    $this->saveFilters($db, $segment, $dataSegment->id());
    $db->commit();
    return $dataSegment->id();
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
      throw new ErrorSavingException(join(", ", $model->getErrors()), (int)$code);
    }
  }

  /**
   * @throws ErrorSavingException
   */
  private function saveFilters(\PDO $db, DynamicSegment $segment, $savedDataId) {
    $this->deleteFilters($segment);
    foreach ($segment->getFilters() as $filter) {
      $dataFilter = $this->saveFilter($filter, $savedDataId);
      $this->checkErrors($db, $dataFilter);
    }
  }

  private function deleteFilters(DynamicSegment $segment) {
    DynamicSegmentFilter::deleteAllBySegmentIds([$segment->id]);
  }

  private function saveFilter(Filter $filter, $dataSegmentId) {
    $dataFilter = DynamicSegmentFilter::create();
    if ($dataFilter instanceof DynamicSegmentFilter) {
      $dataFilter->segmentId = $dataSegmentId;
      $dataFilter->filterData = $filter->toArray();
      $dataFilter->save();
    }
    return $dataFilter;
  }
}
