<?php

namespace MailPoet\Form;

use MailPoet\API\JSON\API;
use MailPoet\Config\Env;
use MailPoet\Config\Renderer as ConfigRenderer;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Models\Form;
use MailPoet\Models\Setting;
use MailPoet\Util\Security;
use MailPoet\WP\Hooks;

if(!defined('ABSPATH')) exit;

class Widget extends \WP_Widget {
  private $renderer;

  const RECAPTCHA_API_URL = 'https://www.google.com/recaptcha/api.js?onload=reCaptchaCallback&render=explicit';

  function __construct() {
    parent::__construct(
      'mailpoet_form',
      __('MailPoet Form', 'mailpoet'),
      array('description' => __('Add a newsletter subscription form', 'mailpoet'))
    );

    $this->renderer = new \MailPoet\Config\Renderer(!WP_DEBUG, !WP_DEBUG);
    if(!is_admin()) {
      $this->setupIframe();
    } else {
      add_action('widgets_admin_page', array(
        $this,
        'setupAdminWidgetPageDependencies'
      ));
    }
  }

  function setupIframe() {
    $form_id = (isset($_GET['mailpoet_form_iframe']) ? (int)$_GET['mailpoet_form_iframe'] : 0);
    if(!$form_id || !Form::findOne($form_id)) return;

    $form_html = $this->widget(
      array(
        'form' => $form_id,
        'form_type' => 'iframe'
      )
    );

    ob_start();
    wp_print_scripts('jquery');
    wp_print_scripts('mailpoet_vendor');
    wp_print_scripts('mailpoet_public');
    echo '<script src="'.self::RECAPTCHA_API_URL.'" async defer></script>';
    $scripts = ob_get_contents();
    ob_end_clean();

    // language attributes
    $language_attributes = array();
    $is_rtl = (bool)(function_exists('is_rtl') && is_rtl());

    if($is_rtl) {
      $language_attributes[] = 'dir="rtl"';
    }

    if(get_option('html_type') === 'text/html') {
      $language_attributes[] = sprintf('lang="%s"', get_bloginfo('language'));
    }

    $language_attributes = apply_filters(
      'language_attributes', implode(' ', $language_attributes)
    );

    $data = array(
      'language_attributes' => $language_attributes,
      'scripts' => $scripts,
      'form' => $form_html,
      'mailpoet_form' => array(
        'ajax_url' => admin_url('admin-ajax.php', 'absolute'),
        'is_rtl' => $is_rtl
      )
    );

    try {
      echo $this->renderer->render('form/iframe.html', $data);
    } catch(\Exception $e) {
      echo $e->getMessage();
    }

    exit();
  }

  function setupDependencies() {
    wp_enqueue_style(
      'mailpoet_public',
      Env::$assets_url . '/css/' . $this->renderer->getCssAsset('public.css')
    );

    wp_enqueue_script(
      'mailpoet_vendor',
      Env::$assets_url . '/js/' . $this->renderer->getJsAsset('vendor.js'),
      array(),
      Env::$version,
      true
    );

    wp_enqueue_script(
      'mailpoet_public',
      Env::$assets_url . '/js/' . $this->renderer->getJsAsset('public.js'),
      array('jquery'),
      Env::$version,
      true
    );

    $captcha = Setting::getValue('re_captcha');
    if(!empty($captcha['enabled'])) {
      wp_enqueue_script(
        'mailpoet_recaptcha',
        self::RECAPTCHA_API_URL,
        array('mailpoet_public')
      );
    }

    wp_localize_script('mailpoet_public', 'MailPoetForm', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'is_rtl' => (function_exists('is_rtl') ? (bool)is_rtl() : false)
    ));

    $ajax_failed_error_message = __('An error has happened while performing a request, please try again later.');

    $inline_script = <<<EOL
function initMailpoetTranslation() {
  if(typeof MailPoet !== 'undefined') {
    MailPoet.I18n.add('ajaxFailedErrorMessage', '%s')
  } else {
    setTimeout(initMailpoetTranslation, 250);
  }
}
setTimeout(initMailpoetTranslation, 250);
EOL;
    wp_add_inline_script(
      'mailpoet_public',
      sprintf($inline_script, $ajax_failed_error_message),
      'after'
    );
  }

  function setupAdminWidgetPageDependencies() {
    wp_enqueue_script(
      'mailpoet_vendor',
      Env::$assets_url . '/js/' . $this->renderer->getJsAsset('vendor.js'),
      array(),
      Env::$version,
      true
    );

    wp_enqueue_script(
      'mailpoet_admin',
      Env::$assets_url . '/js/' . $this->renderer->getJsAsset('mailpoet.js'),
      array(),
      Env::$version,
      true
    );
  }

  /**
   * Save the new widget's title.
   */
  public function update($new_instance, $old_instance) {
    $instance = $old_instance;
    $instance['title'] = strip_tags($new_instance['title']);
    $instance['form'] = (int)$new_instance['form'];
    return $instance;
  }

  /**
   * Output the widget's option form.
   */
  public function form($instance) {
    $instance = wp_parse_args(
      (array)$instance,
      array(
        'title' => __('Subscribe to Our Newsletter', 'mailpoet')
      )
    );

    $form_edit_url = admin_url('admin.php?page=mailpoet-form-editor&id=');

    // set title
    $title = isset($instance['title']) ? strip_tags($instance['title']) : '';

    // set form
    $selected_form = isset($instance['form']) ? (int)($instance['form']) : 0;

    // get forms list
    $forms = Form::getPublished()->orderByAsc('name')->findArray();
    ?><p>
      <label for="<?php $this->get_field_id( 'title' ) ?>"><?php _e('Title:', 'mailpoet'); ?></label>
      <input
        type="text"
        class="widefat"
        id="<?php echo $this->get_field_id('title') ?>"
        name="<?php echo $this->get_field_name('title'); ?>"
        value="<?php echo esc_attr($title); ?>"
      />
    </p>
    <p>
      <select class="widefat" id="<?php echo $this->get_field_id('form') ?>" name="<?php echo $this->get_field_name('form'); ?>">
        <?php
        foreach($forms as $form) {
          $is_selected = ($selected_form === (int)$form['id']) ? 'selected="selected"' : '';
        ?>
        <option value="<?php echo (int)$form['id']; ?>" <?php echo $is_selected; ?>><?php echo esc_html($form['name']); ?></option>
        <?php }  ?>
      </select>
    </p>
    <p>
      <a href="javascript:;" onClick="createSubscriptionForm()" class="mailpoet_form_new"><?php _e('Create a new form', 'mailpoet'); ?></a>
    </p>
    <script type="text/javascript">
    function createSubscriptionForm() {
        MailPoet.Ajax.post({
          endpoint: 'forms',
          action: 'create'
        }).done(function(response) {
          if(response.data && response.data.id) {
            window.location =
              "<?php echo $form_edit_url; ?>" + response.data.id;
          }
        }).fail((response) => {
          if(response.errors.length > 0) {
            MailPoet.Notice.error(
              response.errors.map((error) => { return error.message; }),
              { scroll: true }
            );
          }
        });
        return false;
    }
    </script>
    <?php
  }

  /**
   * Output the widget itself.
   */
  function widget($args, $instance = null) {
    $this->setupDependencies();

    // turn $args into variables
    extract($args);

    if($instance === null) {
      $instance = $args;
    }

    $title = Hooks::applyFilters(
      'widget_title',
      !empty($instance['title']) ? $instance['title'] : '',
      $instance,
      $this->id_base
    );

    // get form
    $form = Form::getPublished()->findOne($instance['form']);
    if(!$form) return '';

    $form = $form->asArray();
    $form_type = 'widget';
    if(isset($instance['form_type']) && in_array(
        $instance['form_type'],
        array(
          'html',
          'php',
          'iframe',
          'shortcode'
        )
      )) {
      $form_type = $instance['form_type'];
    }

    $body = (isset($form['body']) ? $form['body'] : array());
    $output = '';

    if(!empty($body)) {
      $form_id = $this->id_base . '_' . $form['id'];
      $data = array(
        'form_id' => $form_id,
        'form_type' => $form_type,
        'form' => $form,
        'title' => $title,
        'styles' => FormRenderer::renderStyles($form, '#' . $form_id),
        'html' => FormRenderer::renderHTML($form),
        'before_widget' => (!empty($before_widget) ? $before_widget : ''),
        'after_widget' => (!empty($after_widget) ? $after_widget : ''),
        'before_title' => (!empty($before_title) ? $before_title : ''),
        'after_title' => (!empty($after_title) ? $after_title : '')
      );

      // (POST) non ajax success/error variables
      $data['success'] = (
        (isset($_GET['mailpoet_success']))
        &&
        ((int)$_GET['mailpoet_success'] === (int)$form['id'])
      );
      $data['error'] = (
        (isset($_GET['mailpoet_error']))
        &&
        ((int)$_GET['mailpoet_error'] === (int)$form['id'])
      );

      // generate security token
      $data['token'] = Security::generateToken();

      // add API version
      $data['api_version'] = API::CURRENT_VERSION;

      // render form
      $renderer = new ConfigRenderer();
      try {
        $output = $renderer->render('form/widget.html', $data);
        $output = do_shortcode($output);
        $output = Hooks::applyFilters('mailpoet_form_widget_post_process', $output);
      } catch(\Exception $e) {
        $output = $e->getMessage();
      }
    }

    if($form_type === 'widget') {
      echo $output;
    } else {
      return $output;
    }
  }
}
