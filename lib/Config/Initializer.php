<?php
namespace MailPoet\Config;

use MailPoet\Models;
use MailPoet\Router;

if (!defined('ABSPATH')) exit;

class Initializer {
  public function __construct($params = array(
    'file'    => '',
    'version' => '1.0.0'
  )) {
    Env::init($params['file'], $params['version']);
  }

  function init() {
    $this->setupDB();
    $this->setupActivator();
    $this->setupRenderer();
    $this->setupLocalizer();
    $this->setupMenu();
    $this->setupRouter();
  }

  function setupDB() {
    \ORM::configure(Env::$db_source_name);
    \ORM::configure('username', Env::$db_username);
    \ORM::configure('password', Env::$db_password);

    $subscribers = Env::$db_prefix . 'subscribers';
    $settings = Env::$db_prefix . 'settings';
    $newsletters = Env::$db_prefix . 'newsletters';

    define('MP_SUBSCRIBERS_TABLE', $subscribers);
    define('MP_SETTINGS_TABLE', $settings);
    define('MP_NEWSLETTERS_TABLE', $newsletters);
  }

  function setupActivator() {
    $activator = new Activator();
    $activator->init();
  }

  function setupRenderer() {
    $renderer = new Renderer();
    $this->renderer = $renderer->init();
  }

  function setupLocalizer() {
    $localizer = new Localizer($this->renderer);
    $localizer->init();
  }

  function setupMenu() {
    $menu = new Menu(
      $this->renderer,
      Env::$assets_url
    );
    $menu->init();
  }

  function setupRouter() {
    $router = new Router\Router();
    $router->init();
  }
}
