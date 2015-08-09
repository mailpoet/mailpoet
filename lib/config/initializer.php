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

    // localization
    $this->setup_textdomain();
    add_action(
      'init',
      array($this, 'localize'),
      0
    );

    $renderer = new Renderer();
    $this->renderer = $renderer->init();

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

  // public methods
  public function public_css() {
    $name = Env::$plugin_name . '-public';

    wp_register_style(
      $name,
      Env::$assets_url . '/css/public.css',
      array(),
      Env::$version
   );
    wp_enqueue_style($name);
  }

  public function public_js() {
    $name = En::$plugin_name . '-public';
    wp_register_script(
      $name,
      Env::$assets_url . '/js/public.js',
      array('jquery'),
      Env::$version
   );
    wp_enqueue_script($name);
  }

  public function admin_css($hook = '') {
    $name = Env::$plugin_name . '-admin';
    wp_register_style(
      $name,
      Env::$assets_url . '/css/admin.css',
      array(), Env::$version
   );
    wp_enqueue_style($name);
  }

  public function admin_js($hook = '') {
    $name = Env::$plugin_name . '-admin';
    wp_register_script(
      Env::$plugin_name . '-admin',
      Env::$assets_url . '/js/admin.js',
      array('jquery'),
      Env::$version
   );
    wp_enqueue_script($name);
  }

  public function localize() {
    load_plugin_textdomain(
      Env::$plugin_name,
      false,
      dirname(plugin_basename(Env::$file)) . '/lang/'
   );

    // set rtl flag
    $this->renderer->addGlobal('is_rtl', is_rtl());
  }

  public function setup_textdomain() {
    $locale = apply_filters(
      'plugin_locale',
      get_locale(),
      Env::$plugin_name
   );

    $language_path = Env::$languages_path.'/'.Env::$plugin_name.'-'.$locale.'.mo';
    load_textdomain(Env::$plugin_name, $language_path);
    load_plugin_textdomain(
      Env::$plugin_name,
      false,
      dirname(plugin_basename(Env::$file)) . '/lang/'
   );
  }
}
