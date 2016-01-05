<?php
namespace MailPoet\Models;

if(!defined('ABSPATH')) exit;

class SendingQueue extends Model {
  public static $_table = MP_SENDING_QUEUES_TABLE;

  function __construct() {
    parent::__construct();
  }

  function pause() {
    if($this->count_processed === $this->count_total) {
      return false;
    } else {
      $this->set('status', 'paused');
      return $this->save();
    }
  }

  function resume() {
    if($this->count_processed === $this->count_total) {
      return $this->complete();
    } else {
      $this->set_expr('status', 'NULL');
      return $this->save();
    }
  }

  function complete() {
    $this->set('status', 'completed');
    return $this->save();
  }
}