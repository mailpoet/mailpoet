<?php
namespace MailPoet\Models;

if (!defined('ABSPATH')) exit;

class StatisticsForms extends Model {
  public static $_table = MP_STATISTICS_FORMS_TABLE;

  public static function getTotalSignups($form_id = false) {
    return self::where('form_id', $form_id)->count();
  }

  public static function record($form_id, $subscriber_id) {
    if ($form_id > 0 && $subscriber_id > 0) {
      // check if we already have a record for today
      $record = self::where('form_id', $form_id)
        ->where('subscriber_id', $subscriber_id)
        ->findOne();

      if ($record === false) {
        // create a new entry
        $record = self::create();
        $record->hydrate([
          'form_id' => $form_id,
          'subscriber_id' => $subscriber_id,
        ]);
        $record->save();
      }
      return $record;
    }
    return false;
  }
}
