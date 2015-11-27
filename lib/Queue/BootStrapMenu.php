<?php
namespace MailPoet\Queue;

use Carbon\Carbon;
use MailPoet\Models\Queue;
use MailPoet\Models\Setting;

class BootStrapMenu {
  function __construct() {
    $this->daemon = Setting::where('name', 'daemon')
      ->findOne();
  }

  function bootStrap() {
    $queues = Queue::findMany();
    return ($this->daemon) ?
      array_merge(
        array(
          'time_since_start' =>
            Carbon::createFromFormat(
              'Y-m-d H:i:s',
              $this->daemon->created_at,
              'UTC'
            )->diffForHumans(),
          'time_since_update' =>
            Carbon::createFromFormat(
              'Y-m-d H:i:s',
              $this->daemon->updated_at,
              'UTC'
            )->diffForHumans()
        ),
        json_decode($this->daemon->value, true)
      ) :
      "false";
  }
}