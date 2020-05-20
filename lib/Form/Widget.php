<?php

namespace MailPoet\Form;

use MailPoet\API\JSON\API;
use MailPoet\Config\Renderer as ConfigRenderer;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Form\Renderer as FormRenderer;
use MailPoet\Models\Form;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Security;
use MailPoet\WP\Functions as WPFunctions;

class Widget extends \WP_Widget {
  private $renderer;
  private $wp;

  /** @var AssetsController */
  private $assetsController;

  /** @var FormRenderer */
  private $formRenderer;

  public function __construct() {
    parent::__construct(
      'mailpoet_form',
      WPFunctions::get()->__('MailPoet 3 Form', 'mailpoet'),
      ['description' => WPFunctions::get()->__('Add a newsletter subscription form', 'mailpoet')]
    );
    $this->wp = new WPFunctions;
    $this->renderer = new \MailPoet\Config\Renderer(!WP_DEBUG, !WP_DEBUG);
    $this->assetsController = new AssetsController($this->wp, $this->renderer, SettingsController::getInstance());
    $this->formRenderer = ContainerWrapper::getInstance()->get(FormRenderer::class);
    if (!is_admin()) {
      $this->setupIframe();
    } else {
      WPFunctions::get()->addAction('widgets_admin_page', [
        $this->assetsController,
        'setupAdminWidgetPageDependencies',
      ]);
    }
  }

  public function setupIframe() {
    $formId = (isset($_GET['mailpoet_form_iframe']) ? (int)$_GET['mailpoet_form_iframe'] : 0);
    if (!$formId || !Form::findOne($formId)) return;

    $formHtml = $this->widget(
      [
        'form' => $formId,
        'form_type' => 'iframe',
      ]
    );

    $scripts = $this->assetsController->printScripts();

    // language attributes
    $languageAttributes = [];
    $isRtl = (bool)(function_exists('is_rtl') && WPFunctions::get()->isRtl());

    if ($isRtl) {
      $languageAttributes[] = 'dir="rtl"';
    }

    if (get_option('html_type') === 'text/html') {
      $languageAttributes[] = sprintf('lang="%s"', WPFunctions::get()->getBloginfo('language'));
    }

    $languageAttributes = WPFunctions::get()->applyFilters(
      'language_attributes', implode(' ', $languageAttributes)
    );

    $data = [
      'language_attributes' => $languageAttributes,
      'scripts' => $scripts,
      'form' => $formHtml,
      'mailpoet_form' => [
        'ajax_url' => WPFunctions::get()->adminUrl('admin-ajax.php', 'absolute'),
        'is_rtl' => $isRtl,
      ],
    ];

    try {
      echo $this->renderer->render('form/iframe.html', $data);
    } catch (\Exception $e) {
      echo $e->getMessage();
    }

    exit();
  }

  /**
   * Save the new widget's title.
   */
  public function update($newInstance, $oldInstance) {
    $instance = $oldInstance;
    $instance['title'] = strip_tags($newInstance['title']);
    $instance['form'] = (int)$newInstance['form'];
    return $instance;
  }

  /**
   * Output the widget's option form.
   */
  public function form($instance) {
    $instance = WPFunctions::get()->wpParseArgs(
      (array)$instance,
      [
        'title' => WPFunctions::get()->__('Subscribe to Our Newsletter', 'mailpoet'),
      ]
    );

    $formEditUrl = WPFunctions::get()->adminUrl('admin.php?page=mailpoet-form-editor&id=');

    // set title
    $title = isset($instance['title']) ? strip_tags($instance['title']) : '';

    // set form
    $selectedForm = isset($instance['form']) ? (int)($instance['form']) : 0;

    // get forms list
    $forms = Form::getPublished()->orderByAsc('name')->findArray();
    ?><p>
      <label for="<?php $this->get_field_id( 'title' ) ?>"><?php WPFunctions::get()->_e('Title:', 'mailpoet'); ?></label>
      <input
        type="text"
        class="widefat"
        id="<?php echo $this->get_field_id('title') ?>"
        name="<?php echo $this->get_field_name('title'); ?>"
        value="<?php echo WPFunctions::get()->escAttr($title); ?>"
      />
    </p>
    <p>
      <select class="widefat" id="<?php echo $this->get_field_id('form') ?>" name="<?php echo $this->get_field_name('form'); ?>">
        <?php
        foreach ($forms as $form) {
          $isSelected = ($selectedForm === (int)$form['id']) ? 'selected="selected"' : '';
          $formName = $form['name'] ? $this->wp->escHtml($form['name']) : "({$this->wp->_x('no name', 'fallback for forms without a name in a form list')})"
          ?>
        <option value="<?php echo (int)$form['id']; ?>" <?php echo $isSelected; ?>><?php echo $formName; ?></option>
        <?php }  ?>
      </select>
    </p>
    <p>
      <a href="javascript:;" onClick="createSubscriptionForm()" class="mailpoet_form_new"><?php WPFunctions::get()->_e('Create a new form', 'mailpoet'); ?></a>
    </p>
    <script type="text/javascript">
      function createSubscriptionForm() {
        MailPoet.Ajax.post({
          endpoint: 'forms',
          action: 'create',
          api_version: window.mailpoet_api_version
        }).done(function(response) {
          if (response.data && response.data.id) {
            window.location =
              "<?php echo $formEditUrl; ?>" + response.data.id;
          }
        }).fail((response) => {
          if (response.errors.length > 0) {
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
  public function widget($args, $instance = null) {
    $this->assetsController->setupFrontEndDependencies();

    $beforeWidget = !empty($args['before_widget']) ? $args['before_widget'] : '';
    $afterWidget = !empty($args['after_widget']) ? $args['after_widget'] : '';
    $beforeTitle = !empty($args['before_title']) ? $args['before_title'] : '';
    $afterTitle = !empty($args['after_title']) ? $args['after_title'] : '';

    if ($instance === null) {
      $instance = $args;
    }

    $title = $this->wp->applyFilters(
      'widget_title',
      !empty($instance['title']) ? $instance['title'] : '',
      $instance,
      $this->id_base // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
    );

    // get form
    $form = Form::getPublished()->findOne($instance['form']);
    if (!$form) return '';

    $form = $form->asArray();
    $formType = 'widget';
    if (isset($instance['form_type']) && in_array(
        $instance['form_type'],
        [
          'html',
          'php',
          'iframe',
          'shortcode',
        ]
      )) {
      $formType = $instance['form_type'];
    }

    $body = (isset($form['body']) ? $form['body'] : []);
    $output = '';

    if (!empty($body)) {
      $formId = $this->id_base . '_' . $form['id']; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
      $data = [
        'form_html_id' => $formId,
        'form_id' => $form['id'],
        'form_type' => $formType,
        'form_success_message' => $form['settings']['success_message'],
        'title' => $title,
        'styles' => $this->formRenderer->renderStyles($form, '#' . $formId),
        'html' => $this->formRenderer->renderHTML($form),
        'before_widget' => $beforeWidget,
        'after_widget' => $afterWidget,
        'before_title' => $beforeTitle,
        'after_title' => $afterTitle,
      ];

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
        $output = $renderer->render('form/front_end_form.html', $data);
        $output = WPFunctions::get()->doShortcode($output);
        $output = $this->wp->applyFilters('mailpoet_form_widget_post_process', $output);
      } catch (\Exception $e) {
        $output = $e->getMessage();
      }
    }

    if ($formType === 'widget') {
      echo $output;
    } else {
      return $output;
    }
  }
}
