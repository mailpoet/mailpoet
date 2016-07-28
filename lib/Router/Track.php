<?php
namespace MailPoet\Router;

use MailPoet\Statistics\Track\Clicks;
use MailPoet\Statistics\Track\Opens;

if(!defined('ABSPATH')) exit;

class Track {
  const ENDPOINT = 'track';
  const ACTION_CLICK = 'click';
  const ACTION_OPEN = 'open';

  static function click($data) {
    $clicks = new Clicks($data);
    $clicks->track();
  }

  static function open($data) {
    $opens = new Opens($data);
    $opens->track();
  }
}