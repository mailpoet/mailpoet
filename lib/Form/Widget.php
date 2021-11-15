<?php

namespace MailPoet\Form;

use MailPoet\API\JSON\API;
use MailPoet\Config\Renderer as ConfigRenderer;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\Renderer as FormRenderer;
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

  /** @var FormsRepository */
  private $formsRepository;

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
    $this->formsRepository = ContainerWrapper::getInstance()->get(FormsRepository::class);
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
    if (!$formId || !$this->formsRepository->findOneById($formId)) return;

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

    $formEditUrl = WPFunctions::get()->adminUrl('admin.php?page=mailpoet-form-editor-template-selection');

    // set title
    $title = isset($instance['title']) ? strip_tags($instance['title']) : '';

    // set form
    $selectedForm = isset($instance['form']) ? (int)($instance['form']) : 0;

    // get forms list
    $forms = $this->formsRepository->findBy(['deletedAt' => null], ['name' => 'asc']);
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
        // Select the first one from the list if none selected
        if ($selectedForm === 0 && !empty($forms)) $selectedForm = $forms[0]->getId();
        foreach ($forms as $form) {
          $isSelected = ($selectedForm === $form->getId()) ? 'selected="selected"' : '';
          $formName = $form->getName() ? $this->wp->escHtml($form->getName()) : "({$this->wp->_x('no name', 'fallback for forms without a name in a form list')})";
          $formName .= $form->getStatus() === FormEntity::STATUS_DISABLED ? ' (' . __('inactive', 'mailpoet') . ')' : '';
          ?>
        <option value="<?php echo $form->getId(); ?>" <?php echo $isSelected; ?>><?php echo $formName; ?></option>
        <?php }  ?>
      </select>
    </p>
    <p>
      <a href="<?php echo $formEditUrl; ?>" target="_blank" class="mailpoet_form_new"><?php WPFunctions::get()->_e('Create a new form', 'mailpoet'); ?></a>
    </p>
    <?php
    return '';
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
      $this->id_base // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    );

    // get form
    if (!empty($instance['form'])) {
      $form = $this->formsRepository->findOneById($instance['form']);
    } else {
      // Backwards compatibility for MAILPOET-3847
      // Get first non deleted form
      $forms = $this->formsRepository->findBy(['deletedAt' => null], ['name' => 'asc']);
      if (empty($forms)) return '';
      $form = $forms[0];
    }

    if (!$form) return '';
    if ($form->getDeletedAt()) return '';
    if ($form->getStatus() !== FormEntity::STATUS_ENABLED) return '';

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

    $body = (!empty($form->getBody()) ? $form->getBody() : []);
    $output = '';
    $settings = $form->getSettings();

    if (!empty($body) && is_array($settings)) {
      $formId = $this->id_base . '_' . $form->getId(); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      $data = [
        'form_html_id' => $formId,
        'form_id' => $form->getId(),
        'form_type' => $formType,
        'form_success_message' => $settings['success_message'],
        'title' => $title,
        'styles' => $this->formRenderer->renderStyles($form, '#' . $formId, FormEntity::DISPLAY_TYPE_OTHERS),
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
        ((int)$_GET['mailpoet_success'] === $form->getId())
      );
      $data['error'] = (
        (isset($_GET['mailpoet_error']))
        &&
        ((int)$_GET['mailpoet_error'] === $form->getId())
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
