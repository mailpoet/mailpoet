<?php

namespace MailPoet\Models;

/**
 * @property int $newsletter_id
 * @property int $task_id
 * @property string $updated_at
 */
class StatsNotification extends Model {
  public static $_table = MP_STATS_NOTIFICATIONS_TABLE;

  /** @return Newsletter */
  public function newsletter() {
    return $this->hasOne(
      Newsletter::class,
      'id',
      'newsletter_id'
    );
  }

  /** @return StatsNotification */
  static function createOrUpdate($data = []) {
    $model = null;

    if (isset($data['id']) && (int)$data['id'] > 0) {
      $model = static::findOne((int)$data['id']);
    }

    if (!$model && isset($data['task_id']) && $data['newsletter_id']) {
      $model = self::where('newsletter_id', $data['newsletter_id'])
        ->where('task_id', $data['task_id'])
        ->findOne();
    }

    if (!$model) {
      $model = static::create();
      $model->hydrate($data);
    } else {
      unset($data['id']);
      $model->set($data);
    }

    return $model->save();
  }

}
