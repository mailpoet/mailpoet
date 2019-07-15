<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Menu;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Shortcodes\ShortcodesHelper;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsController;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class NewsletterEditor {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var SettingsController */
  private $settings;

  /** @var UserFlagsController */
  private $user_flags;

  /** @var WPFunctions */
  private $wp;

  function __construct(
    PageRenderer $page_renderer,
    SettingsController $settings,
    UserFlagsController $user_flags,
    WPFunctions $wp
  ) {
    $this->page_renderer = $page_renderer;
    $this->settings = $settings;
    $this->user_flags = $user_flags;
    $this->wp = $wp;
  }

  function render() {
    $subscriber = Subscriber::getCurrentWPUser();
    $subscriber_data = $subscriber ? $subscriber->asArray() : [];
    $data = [
      'shortcodes' => ShortcodesHelper::getShortcodes(),
      'settings' => $this->settings->getAll(),
      'editor_tutorial_seen' => $this->user_flags->get('editor_tutorial_seen'),
      'current_wp_user' => array_merge($subscriber_data, $this->wp->wpGetCurrentUser()->to_array()),
      'sub_menu' => Menu::MAIN_PAGE_SLUG,
      'mss_active' => Bridge::isMPSendingServiceEnabled(),
    ];
    $this->wp->wpEnqueueMedia();
    $this->wp->wpEnqueueScript('tinymce-wplink', $this->wp->includesUrl('js/tinymce/plugins/wplink/plugin.js'));
    $this->wp->wpEnqueueStyle('editor', $this->wp->includesUrl('css/editor.css'));

    $this->page_renderer->displayPage('newsletter/editor.html', $data);
  }
}
