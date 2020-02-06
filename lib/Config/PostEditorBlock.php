<?php

namespace MailPoet\Config;

use MailPoet\Entities\FormEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Form\Widget;
use MailPoet\WP\Functions as WPFunctions;

class PostEditorBlock {
  /** @var Renderer */
  private $renderer;

  /** @var WPFunctions */
  private $wp;

  /** @var FormsRepository */
  private $formsRepository;

  public function __construct(
    Renderer $renderer,
    WPFunctions $wp,
    FormsRepository $formsRepository
  ) {
    $this->renderer = $renderer;
    $this->wp = $wp;
    $this->formsRepository = $formsRepository;
  }

  public function init() {
    // this has to be here until we drop support for WordPress < 5.0
    if (!function_exists('register_block_type')) return;

    if (is_admin()) {
      $this->initAdmin();
    } else {
      $this->initFrontend();
    }

    $this->wp->registerBlockType('mailpoet/form-block-render', [
      'attributes' => [
        'form' => [
          'type' => 'number',
          'default' => null,
        ],
      ],
      'render_callback' => [$this, 'renderForm'],
    ]);
  }

  private function initAdmin() {
    $this->wp->wpEnqueueScript(
      'mailpoet-block-form-block-js',
      Env::$assetsUrl . '/dist/js/' . $this->renderer->getJsAsset('post_editor_block.js'),
      ['wp-blocks', 'wp-components', 'wp-server-side-render', 'wp-block-editor'],
      Env::$version,
      true
    );

    $this->wp->wpEnqueueStyle(
      'mailpoetblock-form-block-css',
      Env::$assetsUrl . '/dist/css/' . $this->renderer->getCssAsset('post-editor-block.css'),
      ['wp-edit-blocks'],
      Env::$version
    );

    $this->wp->registerBlockType('mailpoet/form-block', [
      'style' => 'mailpoetblock-form-block-css',
      'editor_script' => 'mailpoet/form-block',
    ]);

    $this->wp->addAction('admin_head', function() {
      $forms = $this->formsRepository->findAllNotDeleted();
      ?>
      <script type="text/javascript">
        window.mailpoet_forms = <?php echo json_encode(array_map(function(FormEntity $form) {
          return $form->toArray();
        }, $forms)) ?>;
        window.locale = {
          selectForm: '<?php echo __('Select a MailPoet form', 'mailpoet') ?>',
          createForm: '<?php echo __('Create a new form', 'mailpoet') ?>',
          subscriptionForm: '<?php echo __('MailPoet Subscription Form', 'mailpoet') ?>',
        };
      </script>
      <?php
    });
  }

  private function initFrontend() {
    $this->wp->registerBlockType('mailpoet/form-block', [
      'render_callback' => [$this, 'renderForm'],
    ]);
  }

  public function renderForm($attributes) {
    if (!$attributes || !isset($attributes['form'])) {
      return '';
    }
    $basicForm = new Widget();
    return $basicForm->widget([
      'form' => (int)$attributes['form'],
      'form_type' => 'html',
    ]);
  }

}
