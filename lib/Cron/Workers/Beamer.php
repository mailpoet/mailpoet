<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Models\ScheduledTask;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use function GuzzleHttp\json_decode;

if (!defined('ABSPATH')) exit;

class Beamer extends SimpleWorker {
  const TASK_TYPE = 'beamer';
  const API_KEY = 'b_neUUX8kIYVEYZqQzSnwhmVggVLA6lT+GzDQOW7hrP38=';

  /** @var SettingsController */
  private $settings;

  function __construct(SettingsController $settings, $timer = false) {
    parent::__construct($timer);
    $this->settings = $settings;
  }

  function processTaskStrategy(ScheduledTask $task) {
    return $this->setLastAnnouncementDate();
  }

  function setLastAnnouncementDate() {
    $wp = new WPFunctions();
    $response = $wp->wpRemoteGet('https://api.getbeamer.com/v0/posts?published=true&maxResults=1', [
      'headers' => [
        'Beamer-Api-Key' => self::API_KEY,
      ],
    ]);
    $posts = $wp->wpRemoteRetrieveBody($response);
    if (empty($posts)) return false;
    $posts = json_decode($posts);
    $this->settings->set('last_announcement_date', Carbon::createFromTimeString($posts[0]->date)->getTimestamp());
    return true;
  }

  static function getNextRunDate() {
    $wp = new WPFunctions();
    $date = Carbon::createFromTimestamp($wp->currentTime('timestamp'));
    return $date->hour(11)->minute(00)->second(00)->addDay();
  }
}
