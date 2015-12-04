<?php
namespace MailPoet\Cron;

use Carbon\Carbon;
use MailPoet\Models\Setting;

class BootStrapMenu {
  function __construct() {
    $this->daemon = Setting::where('name', 'cron_daemon')
      ->findOne();
  }

  function bootStrap() {
    return ($this->daemon) ?
      array_merge(
        array(
          'timeSinceStart' =>
            Carbon::createFromFormat(
              'Y-m-d H:i:s',
              $this->daemon->created_at,
              'UTC'
            )
              ->diffForHumans(),
          'timeSinceUpdate' =>
            Carbon::createFromFormat(
              'Y-m-d H:i:s',
              $this->daemon->updated_at,
              'UTC'
            )
              ->diffForHumans()
        ),
        json_decode($this->daemon->value, true)
      ) :
      "false";
  }
}