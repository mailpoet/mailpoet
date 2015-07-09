<?php
if (!defined('ABSPATH')) exit;

class MailPoet {

  public $version;
  public $shortname;
  public $file;
  public $dir;
  public $assets_dir;
  public $assets_url;


  public function __construct ($params = array(
    'file' => '',
    'version' => '1.0.0'
  )) {

  $this->version = $params['version'];
  $this->shortname = 'mailpoet';
  $this->file = $params['file'];
  $this->dir = dirname($this->file);
  $this->assets_dir = $this->dir . 'assets/';
  $this->assets_url = plugins_url(
    '/assets/',
    $this->file
  );

  register_activation_hook(
    $this->file,
    array($this, 'install')
  );

  // public assets
  add_action(
    'wp_enqueue_scripts',
    array($this, 'public_css'),
    10
  );
  add_action(
    'wp_enqueue_scripts',
    array($this, 'public_js'),
    10
  );

  // admin assets
  add_action(
    'admin_enqueue_scripts',
    array($this, 'admin_css'),
    10,
    1
  );
  add_action(
    'admin_enqueue_scripts',
    array($this, 'admin_js'),
    10,
    1
  );

  // localization
  $this->setup_textdomain();
  add_action(
    'init',
    array($this, 'localize'),
    0
  );
  }

  public function public_css() {
    $name = $this->shortname . '-public';

    wp_register_style(
      $name,
      $this->assets_url . 'css/public.css',
      array(),
      $this->version
    );
    wp_enqueue_style($name);
  }

  public function public_js() {
    $name = $this->shortname . '-public';
    wp_register_script(
      $name,
      $this->assets_url . 'js/public.js',
      array('jquery'),
      $this->version
    );
    wp_enqueue_script($name);
  }

  public function admin_css($hook = '') {
    $name = $this->shortname . '-admin';
    wp_register_style(
      $name,
      $this->assets_url . 'css/admin.css',
      array(), $this->version
    );
    wp_enqueue_style($name);
  }

  public function admin_js($hook = '') {
    $name = $this->shortname . '-admin';
    wp_register_script(
      $this->shortname . '-admin',
      $this->assets_url . 'js/admin.js',
      array('jquery'),
      $this->version
    );
    wp_enqueue_script($name);
  }

  public function localize() {
    load_plugin_textdomain(
      $this->shortname,
      false,
      dirname(plugin_basename($this->file)) . '/lang/'
    );
  }

  public function setup_textdomain() {
    $domain = 'mailpoet';
    $locale = apply_filters(
      'plugin_locale',
      get_locale(),
      $domain
    );

    $language_path = WP_LANG_DIR
      . '/'
      . $domain
      . '/'
      . $domain
      . '-'
      . $locale
      . '.mo';

    load_textdomain($domain, $language_path);
    load_plugin_textdomain(
      $domain,
      false,
      dirname(plugin_basename($this->file)) . '/lang/'
    );
  }

  public function install() {
    $this->log_version_number();
  }

  private function log_version_number() {
    update_option(
      $this->shortname . '_version', $this->version
    );
  }
}
