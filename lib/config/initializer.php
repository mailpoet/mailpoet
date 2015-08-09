<?php
namespace MailPoet\Config;
use MailPoet\Models;

if(!defined('ABSPATH')) exit;

class Initializer {
  public function __construct($params = array(
    'file' => '',
    'version' => '1.0.0'
  )) {
    Env::init($params['file'], $params['version']);
    $this->setup_db();

    $activator = new Activator();
    $activator->init();

    $renderer = new Renderer();
    $this->renderer = $renderer->init();

    $localizer = new Localizer($this->renderer);
    $localizer->init();

    $menu = new Menu(
      $this->renderer,
      Env::$assets_url
    );
    $menu->init();
  }

  function setup_db() {
    \ORM::configure(Env::$db_source_name);
    \ORM::configure('username', Env::$db_username);
    \ORM::configure('password', Env::$db_password);
    define('MP_SUBSCRIBERS_TABLE', Env::$db_prefix . 'subscribers');
    define('MP_SETTINGS_TABLE', Env::$db_prefix . 'settings');
  }
}
