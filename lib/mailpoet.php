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
    $this->data = array();
    $this->version = $params['version'];
    $this->shortname = 'mailpoet';
    $this->file = $params['file'];
    $this->dir = (dirname($this->file));
    $this->assets_dir = $this->dir . '/assets';
    $this->assets_url = plugins_url(
      '/assets',
      $this->file
    );
    $this->lib_dir = $this->dir .'/lib';

    // Twig autoloader
    require_once $this->lib_dir . '/Twig/Autoloader.php';
    Twig_Autoloader::register();

    // instantiate renderer
    $this->renderer = new Twig_Environment(
      new Twig_Loader_Filesystem($this->dir . '/views'),
      array(
        // 'cache' => '/path/to/compilation_cache',
      )
    );

    // renderer global variables
    $this->renderer->addGlobal('assets_url', $this->assets_url);

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

    // admin menu
    add_action('admin_menu', array($this, 'admin_menu'));
  }

  // public methods
  public function public_css() {
    $name = $this->shortname . '-public';

    wp_register_style(
      $name,
      $this->assets_url . '/css/public.css',
      array(),
      $this->version
    );
    wp_enqueue_style($name);
  }

  public function public_js() {
    $name = $this->shortname . '-public';
    wp_register_script(
      $name,
      $this->assets_url . '/js/public.js',
      array('jquery'),
      $this->version
    );
    wp_enqueue_script($name);
  }

  public function admin_css($hook = '') {
    $name = $this->shortname . '-admin';
    wp_register_style(
      $name,
      $this->assets_url . '/css/admin.css',
      array(), $this->version
    );
    wp_enqueue_style($name);
  }

  public function admin_js($hook = '') {
    $name = $this->shortname . '-admin';
    wp_register_script(
      $this->shortname . '-admin',
      $this->assets_url . '/js/admin.js',
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

  public function admin_page() {
    // set data
    $this->data = array(
      'title' => __('Twig Sample page'),
      'text' => 'Lorem ipsum dolor sit amet',
      'unsafe_string' => '<script>alert("not triggered");</script>',
      'users' => array(
        array('name' => 'Joo', 'email' => 'jonathan@mailpoet.com'),
        array('name' => 'Marco', 'email' => 'marco@mailpoet.com'),
      )
    );
    // Sample page using Twig
    echo $this->renderer->render('index.html', $this->data);
  }

  public function admin_menu() {
    // main menu
    add_menu_page(
      'MailPoet',
      'MailPoet',
      'manage_options',
      'mailpoet-newsletters',
      array($this, 'admin_page'),
      $this->assets_url . '/img/menu_icon.png',
      30
    );
/*
    // newsletters
    add_submenu_page(
      'mailpoet-newsletters',
      'Newsletters',
      'Newsletters',
      'manage_options',
      'mailpoet-newsletters',
      'mailpoet_newsletters'
    );

    // subscribers
    add_submenu_page('mailpoet-newsletters',
      'Subscribers',
      'Subscribers',
      'manage_options',
      'mailpoet-subscribers',
      'mailpoet_subscribers'
    );

    // forms
    add_submenu_page('mailpoet-newsletters',
      'Forms',
      'Forms',
      'manage_options',
      'mailpoet-forms',
      'mailpoet_forms'
    );

    // settings
    add_submenu_page('mailpoet-newsletters',
      'Settings',
      'Settings',
      'manage_options',
      'mailpoet-settings',
      'mailpoet_settings'
    );

    // premium
    add_submenu_page('mailpoet-newsletters',
      'Premium',
      'Premium',
      'manage_options',
      'mailpoet-premium',
      'mailpoet_premium'
    );

    // statistics
    add_submenu_page('mailpoet-newsletters',
      'Statistics',
      'Statistics',
      'manage_options',
      'mailpoet-statistics',
      'mailpoet_statistics'
    );
*/
  }

  // private methods
  private function log_version_number() {
    update_option(
      $this->shortname . '_version', $this->version
    );
  }
}
