<?php

namespace MailPoet\AdminPages\Pages;

use Carbon\Carbon;
use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Env;
use MailPoet\Config\Menu;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Readme;

if (!defined('ABSPATH')) exit;

class Update {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  function __construct(PageRenderer $page_renderer, WPFunctions $wp, SettingsController $settings) {
    $this->page_renderer = $page_renderer;
    $this->wp = $wp;
    $this->settings = $settings;
  }

  function render() {
    global $wp;
    $current_url = $this->wp->homeUrl(add_query_arg($wp->query_string, $wp->request));
    $redirect_url =
      (!empty($_GET['mailpoet_redirect']))
        ? urldecode($_GET['mailpoet_redirect'])
        : $this->wp->wpGetReferer();

    if (
      $redirect_url === $current_url
      or
      strpos($redirect_url, 'mailpoet') === false
    ) {
      $redirect_url = $this->wp->adminUrl('admin.php?page=' . Menu::MAIN_PAGE_SLUG);
    }

    $data = [
      'settings' => $this->settings->getAll(),
      'current_user' => $this->wp->wpGetCurrentUser(),
      'redirect_url' => $redirect_url,
      'sub_menu' => Menu::MAIN_PAGE_SLUG,
    ];

    $data['is_new_user'] = true;
    $data['is_old_user'] = false;
    if (!empty($data['settings']['installed_at'])) {
      $installed_at = Carbon::createFromTimestamp(strtotime($data['settings']['installed_at']));
      $current_time = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
      $data['is_new_user'] = $current_time->diffInDays($installed_at) <= 30;
      $data['is_old_user'] = $current_time->diffInMonths($installed_at) >= 6;
      $data['stop_call_for_rating'] = isset($data['settings']['stop_call_for_rating']) ? $data['settings']['stop_call_for_rating'] : false;
    }

    $readme_file = Env::$path . '/readme.txt';
    if (is_readable($readme_file)) {
      $changelog = Readme::parseChangelog(file_get_contents($readme_file), 1);
      if ($changelog) {
        $data['changelog'] = $changelog;
      }
    }

    $this->page_renderer->displayPage('update.html', $data);
  }
}
