<?php

namespace MailPoet\Models;

class StatsNotification extends Model {
  public static $_table = MP_STATS_NOTIFICATIONS_TABLE;

  /** @return StatsNotification */
  static function createOrUpdate($data = array()) {
    $model = null;

    if(isset($data['id']) && (int)$data['id'] > 0) {
      $model = static::findOne((int)$data['id']);
    }

    if(!$model && isset($data['task_id']) && $data['newsletter_id']) {
      $model = self::where('newsletter_id', $data['newsletter_id'])
        ->where('task_id', $data['task_id'])
        ->findOne();
    }

    if(!$model) {
      $model = static::create();
      $model->hydrate($data);
    } else {
      unset($data['id']);
      $model->set($data);
    }

    return $model->save();
  }

}
