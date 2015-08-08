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
      'Settings',
      'Settings',
      'manage_options',
      'mailpoet-settings',
      array($this, 'settings')
    );
  }

  function home() {
    $data = array(
      'text' => 'Lorem ipsum dolor sit amet',
      'delete_messages_1' => 1,
      'delete_messages_2' => 10,
      'unsafe_string' => '<script>alert("not triggered");</script>',
      'users' => array(
        array('name' => 'Joo', 'email' => 'jonathan@mailpoet.com'),
        array('name' => 'Marco', 'email' => 'marco@mailpoet.com'),
      )
    );
    echo $this->renderer->render('index.html', $data);
  }

  function settings() {
    echo 'test';
  }
}
