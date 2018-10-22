<?php
namespace MailPoet\Form\Gutenberg;

use MailPoet\Config\Env;
use MailPoet\Config\Renderer;
use MailPoet\Form\Widget;
use MailPoet\Models\Form;

if(!defined('ABSPATH')) exit;

class Blocks {

  /** @var Renderer */
  private $renderer;

  function __construct(Renderer $renderer) {
    $this->renderer = $renderer;
  }

  function setupBlocks() {
    add_action('enqueue_block_editor_assets', [$this, 'registerEditorAssets']);
    if (!is_admin()) {
      add_action('enqueue_block_assets', [$this, 'registerFeAssets']);
    }

    /**
     * Hacky lists and forms data fetching and storing them on window object for rendering in selects
     * There is a mechanism for fetching the data in JS:
     *  - use withSelect from wp.data (@see https://wordpress.org/gutenberg/handbook/blocks/creating-dynamic-blocks/)
     *  - register own store to central gutenberg store (@see https://wordpress.org/gutenberg/handbook/packages/packages-data/)
     **/
    if (is_admin()) {
      add_action('admin_head', function() {
        if (!current_user_can('manage_options')) {
          return;
        }
        $lists = \MailPoet\Models\Segment::getSegmentsForImport();
        $lists_data = [];
        foreach ($lists as $list) {
          if ($list['id'] == 1) {
            continue;
          }
          $lists_data[] = $list;
        }
        $forms = array_map(function($form) {
          return $form->asArray();
        }, Form::findMany())
        ?>
          <script type="text/javascript">
            window.mailpoet_lists =<?php echo json_encode($lists_data) ?>;
            window.mailpoet_forms =<?php echo json_encode($forms) ?>;
          </script>
        <?php
      });
    }

    register_block_type('mailpoet/mp-form-block', [
      'render_callback' => [$this, 'renderForm'],
    ]);
  }

  function registerEditorAssets() {
    wp_enqueue_script(
      'mailpoetblock-form-block-js', // Handle.
      Env::$assets_url . '/dist/js/' . $this->renderer->getJsAsset('form_block_editor.js'),
      [ 'wp-blocks', 'wp-i18n', 'wp-element' ],
      Env::$version,
      true
    );
    // Styles.
    wp_enqueue_style(
      'mailpoetblock-form-block-css', // Handle.
      Env::$assets_url . '/dist/css/' . $this->renderer->getCssAsset('block.css'),
      [ 'wp-edit-blocks' ],
      Env::$version
    );
  }

  function registerFeAssets() {
    wp_enqueue_script(
      'mailpoetblock-form-block-fe-js', // Handle.
      Env::$assets_url . '/dist/js/' . $this->renderer->getJsAsset('form_block_fe.js'),
      [ 'jquery' ],
      Env::$version,
      true
    );
    // Styles.
    wp_enqueue_style(
      'mailpoetblock-form-block-css', // Handle.
      Env::$assets_url . '/dist/css/' . $this->renderer->getCssAsset('block.css'),
      [],
      Env::$version
    );
    $basicForm = new Widget(true);
    $basicForm->setupDependencies();
  }

  function renderForm($attributes) {
    if (!$attributes) {
      return '';
    }
    $basicForm = new Widget(true);
    $form_html = $basicForm->widget([
      'form' => (int)$attributes['form']['value'],
      'form_type' => 'html',
    ]);
    return $form_html;
  }
}
