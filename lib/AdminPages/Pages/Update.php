<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Env;
use MailPoet\Config\Menu;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Readme;
use MailPoetVendor\Carbon\Carbon;

class Update {
  /** @var PageRenderer */
  private $pageRenderer;

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  public function __construct(PageRenderer $pageRenderer, WPFunctions $wp, SettingsController $settings) {
    $this->pageRenderer = $pageRenderer;
    $this->wp = $wp;
    $this->settings = $settings;
  }

  public function render() {
    global $wp;
    $currentUrl = $this->wp->homeUrl(add_query_arg($wp->query_string, $wp->request)); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    $redirectUrl =
      (!empty($_GET['mailpoet_redirect']))
        ? urldecode($_GET['mailpoet_redirect'])
        : $this->wp->wpGetReferer();

    if (
      $redirectUrl === $currentUrl
      or
      strpos($redirectUrl, 'mailpoet') === false
    ) {
      $redirectUrl = $this->wp->adminUrl('admin.php?page=' . Menu::MAIN_PAGE_SLUG);
    }

    $data = [
      'settings' => $this->settings->getAll(),
      'current_user' => $this->wp->wpGetCurrentUser(),
      'redirect_url' => $redirectUrl,
      'sub_menu' => Menu::MAIN_PAGE_SLUG,
    ];

    $data['is_new_user'] = true;
    $data['is_old_user'] = false;
    if (!empty($data['settings']['installed_at'])) {
      $installedAt = Carbon::createFromTimestamp(strtotime($data['settings']['installed_at']));
      $currentTime = Carbon::createFromTimestamp($this->wp->currentTime('timestamp'));
      $data['is_new_user'] = $currentTime->diffInDays($installedAt) <= 30;
      $data['is_old_user'] = $currentTime->diffInMonths($installedAt) >= 6;
      $data['stop_call_for_rating'] = isset($data['settings']['stop_call_for_rating']) ? $data['settings']['stop_call_for_rating'] : false;
    }

    $readmeFile = Env::$path . '/readme.txt';
    if (is_readable($readmeFile)) {
      $changelog = Readme::parseChangelog(file_get_contents($readmeFile), 1);
      if ($changelog) {
        $data['changelog'] = $changelog;
      }
    }

    $this->pageRenderer->displayPage('update.html', $data);
  }
}
