<?php

namespace MailPoet\Config;

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

    register_block_type( 'mailpoet/form-block', [
      'style' => 'mailpoetblock-form-block-css',
      'editor_script' => 'mailpoet/form-block',
    ]);

    register_block_type( 'mailpoet/form-block-render', [
      'attributes' => [
        'form' => [
          'type' => 'number',
          'default' => null,
        ],
      ],
      'render_callback' => [$this, 'renderForm'],
    ]);

    if (is_admin()) {
      add_action('admin_head', function() {
        $forms = $this->formsRepository->findAllNotDeleted();
        ?>
        <script type="text/javascript">
          window.mailpoet_forms =<?php echo json_encode($forms) ?>;
        </script>
        <?php
      });
    }

  }

  public function renderForm($attributes) {
    if (!$attributes) {
      return '';
    }
    $basicForm = new Widget();
    return $basicForm->widget([
      'form' => (int)$attributes['form'],
      'form_type' => 'html',
    ]);
  }

}
