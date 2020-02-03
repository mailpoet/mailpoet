<?php

namespace MailPoet\Config;

use MailPoet\Form\FormsRepository;
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
      ['wp-blocks', 'wp-components'],
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

}
