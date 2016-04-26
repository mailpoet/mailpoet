<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class StatisticsForms extends Model {
  public static $_table = MP_STATISTICS_FORMS_TABLE;

  function __construct() {
    parent::__construct();
  }

  public static function record($form_id) {
    if($form_id > 0) {
      $today = date('Y-m-d');

      // check if we already have a record for today
      $record = self::where('form_id', $form_id)
        ->where('date', $today)
        ->findOne();

      if($record !== false) {
        $record->set('count', $record->count + 1);
      } else {
        // create a new entry
        $record = self::create();
        $record->hydrate(array(
          'form_id' => $form_id,
          'date' => $today,
          'count' => 1
        ));
      }
      $record->save();
      return $record;
    }
    return false;
  }
}