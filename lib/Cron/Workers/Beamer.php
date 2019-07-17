<?php
namespace MailPoet\Cron\Workers;

use Carbon\Carbon;
use MailPoet\Models\ScheduledTask;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class Beamer extends SimpleWorker {
  const TASK_TYPE = 'beamer';
  const API_URL = 'https://api.getbeamer.com/v0';
  const API_KEY = 'b_neUUX8kIYVEYZqQzSnwhmVggVLA6lT+GzDQOW7hrP38=';

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  function __construct(SettingsController $settings, WPFunctions $wp, $timer = false) {
    parent::__construct($timer);
    $this->settings = $settings;
    $this->wp = $wp;
  }

  function processTaskStrategy(ScheduledTask $task) {
    return $this->setLastAnnouncementDate();
  }

  function setLastAnnouncementDate() {
    $response = $this->wp->wpRemoteGet(self::API_URL . '/posts?published=true&maxResults=1', [
      'headers' => [
        'Beamer-Api-Key' => self::API_KEY,
      ],
    ]);
    $posts = $this->wp->wpRemoteRetrieveBody($response);
    if (empty($posts)) return false;
    $posts = json_decode($posts);
    if (empty($posts) || empty($posts[0]->date)) return false;
    $this->settings->set('last_announcement_date', Carbon::createFromTimeString($posts[0]->date)->getTimestamp());
    return true;
  }

  static function getNextRunDate() {
    $wp = new WPFunctions;
    $date = Carbon::createFromTimestamp($wp->currentTime('timestamp'));
    return $date->hour(11)->minute(00)->second(00)->addDay();
  }
}
