<?php
namespace MailPoet\Config;
use MailPoet\Models;
use MailPoet\WP;

if(!defined('ABSPATH')) exit;

class Initializer {

  public $version;
  public $shortname;
  public $file;
  public $path;
  public $assets_path;
  public $assets_url;

  public function __construct($params = array(
    'file' => '',
    'version' => '1.0.0'
  )) {
    Env::init();
    $this->setup_db();

    $this->data = array();
    $this->version = $params['version'];
    $this->shortname = Env::$plugin_name;
    $this->file = $params['file'];
    $this->path =(dirname($this->file));
    $this->views_path = $this->path . '/views';
    $this->assets_path = $this->path . '/assets';
    $this->languages_path = $this->path . '/lang';
    $this->assets_url = plugins_url(
      '/assets',
      $this->file
    );
    $this->lib_path = $this->path .'/lib';

    // -------------------
    // Template renderer
    // -------------------
    $this->renderer = new \Twig_Environment(
      new \Twig_Loader_Filesystem($this->views_path),
      array(
        'cache' => (WP_DEBUG === false) ? $this->views_path.'/cache' : false,
      )
    );

    // renderer: i18n (passing the text)
    $this->renderer->addExtension(new \MailPoet\Twig\i18n($this->shortname));

    // renderer: Handlebars extension
    $this->renderer->addExtension(new \MailPoet\Twig\Handlebars());

    // renderer: global variables
    $this->renderer->addExtension(new \MailPoet\Twig\Assets(array(
      'assets_url' => $this->assets_url,
      'assets_path' => $this->assets_path
    )));

    // renderer: syntax
    $lexer = new \Twig_Lexer($this->renderer, array(
      'tag_comment'     => array('<%#', '%>'),
      'tag_block'       => array('<%', '%>'),
      'tag_variable'    => array('<%=', '%>'),
      'interpolation'   => array('%{', '}')
    ));
    $this->renderer->setLexer($lexer);

    // hook: plugin activation
    register_activation_hook(
      $this->file,
      array($this, 'install')
    );

    // localization
    $this->setup_textdomain();
    add_action(
      'init',
      array($this, 'localize'),
      0
    );

    $menu = new Menu(
      $this->renderer,
      $this->assets_url
    );
    $menu->init();

    // ajax action
    add_action('wp_ajax_nopriv_mailpoet_ajax', array($this, 'mailpoet_ajax'));
    add_action('wp_ajax_mailpoet_ajax', array($this, 'mailpoet_ajax'));
  }

  function setup_db() {
    \ORM::configure(Env::$db_source_name);
    \ORM::configure('username', Env::$db_username);
    \ORM::configure('password', Env::$db_password);
    define('MP_SUBSCRIBERS_TABLE', Env::$db_prefix . 'subscribers');
    define('MP_SETTINGS_TABLE', Env::$db_prefix . 'settings');
  }

  public function mailpoet_ajax() {
    if(!current_user_can('manage_options')) {
      echo json_encode(array('error' => "Access Denied"));
    } else {
      // routing
      // $method = $_SERVER['REQUEST_METHOD'];
      $controller = (isset($_GET[ 'mailpoet_controller']) ? $_GET[ 'mailpoet_controller'] : null);
      $action = (isset($_GET[ 'mailpoet_action']) ? $_GET[ 'mailpoet_action'] : null);

      try {
        if($controller === null || $action === null) {
          throw new \Exception('unrecognized route');
        } else {
          // set action based on data
          $ajax_action = $controller.'_'.$action;

          if(in_array($ajax_action, get_class_methods($this))) {
            // retrieve HTTP method
            $method = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING);

            // decode json data
            if($method === 'GET') {
              $data = array_diff_key($_GET, array(
                'action' => null,
                'mailpoet_controller' => null,
                'mailpoet_action' => null
              ));
            } else {
              $data = json_decode(file_get_contents('php://input'), true);
            }
            // return json encoded result of ajax action
            echo json_encode(call_user_func_array(array($this, $ajax_action), array($data)));
          } else {
            throw new \Exception('method "' . $ajax_action . '" is undefined');
          }
        }
      } catch(Exception $e) {
        echo json_encode(array('error' => $e->getMessage()));
      }
    }
    wp_die();
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

    // set rtl flag
    $this->renderer->addGlobal('is_rtl', is_rtl());
  }

  public function setup_textdomain() {
    $locale = apply_filters(
      'plugin_locale',
      get_locale(),
      $this->shortname
   );

    $language_path = $this->languages_path.'/'.$this->shortname.'-'.$locale.'.mo';
    load_textdomain($this->shortname, $language_path);
    load_plugin_textdomain(
      $this->shortname,
      false,
      dirname(plugin_basename($this->file)) . '/lang/'
   );
  }

  public function install() {
    $migrator = new \MailPoet\Config\Migrator;
    $migrator->up();
  }
}
