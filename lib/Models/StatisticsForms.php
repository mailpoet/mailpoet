<?php

namespace MailPoet\Models;

class StatisticsForms extends Model {
  public static $_table = MP_STATISTICS_FORMS_TABLE;

  public static function getTotalSignups($formId = false) {
    return self::where('form_id', $formId)->count();
  }

  public static function record($formId, $subscriberId) {
    if ($formId > 0 && $subscriberId > 0) {
      // check if we already have a record for today
      $record = self::where('form_id', $formId)
        ->where('subscriber_id', $subscriberId)
        ->findOne();

      if ($record === false) {
        // create a new entry
        $record = self::create();
        $record->hydrate([
          'form_id' => $formId,
          'subscriber_id' => $subscriberId,
        ]);
        $record->save();
      }
      return $record;
    }
    return false;
  }
}
