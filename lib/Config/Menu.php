<?php
namespace MailPoet\Config;

if(!defined('ABSPATH')) exit;

class Menu {
  function __construct($renderer, $assets_url) {
    $this->renderer = $renderer;
    $this->assets_url = $assets_url;
  }

  function init() {
    add_action(
      'admin_menu',
      array($this, 'setup')
    );
  }

  function setup() {
    add_menu_page(
      'MailPoet',
      'MailPoet',
      'manage_options',
      'mailpoet',
      array($this, 'home'),
      $this->assets_url . '/img/menu_icon.png',
      30
    );
    add_submenu_page(
      'mailpoet',
      'Newsletters',
      'Newsletters',
      'manage_options',
      'mailpoet-newsletters',
      array($this, 'newsletters')
    );
    add_submenu_page(
      'mailpoet',
      'Subscribers',
      'Subscribers',
      'manage_options',
      'mailpoet-subscribers',
      array($this, 'subscribers')
    );
    add_submenu_page(
      'mailpoet',
      'Settings',
      'Settings',
      'manage_options',
      'mailpoet-settings',
      array($this, 'settings')
    );
    add_submenu_page(
      'mailpoet',
      'Newsletter editor',
      'Newsletter editor',
      'manage_options',
      'mailpoet-newsletter-editor',
      array($this, 'newsletterEditor')
    );
  }

  function home() {
    $data = array();
    echo $this->renderer->render('index.html', $data);
  }

  function settings() {
    $data = array();
    echo $this->renderer->render('settings.html', $data);
  }

  function subscribers() {
    $data = array();
    echo $this->renderer->render('subscribers.html', $data);
  }

  function newsletters() {
    $data = array();
    echo $this->renderer->render('newsletters.html', $data);
  }

  function newsletterEditor() {
    $data = array();
    echo $this->renderer->render('newsletter/editor.html', $data);
  }
}
