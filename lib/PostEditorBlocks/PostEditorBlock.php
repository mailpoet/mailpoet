<?php

namespace MailPoet\PostEditorBlocks;

use MailPoet\Config\Env;
use MailPoet\Config\Renderer;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Form\Widget;
use MailPoet\WP\Functions as WPFunctions;

class PostEditorBlock {
  /** @var Renderer */
  private $renderer;

  /** @var WPFunctions */
  private $wp;

  /** @var SubscriptionFormBlock */
  private $subscriptionFormBlock;

  public function __construct(
    Renderer $renderer,
    WPFunctions $wp,
    SubscriptionFormBlock $subscriptionFormBlock
  ) {
    $this->renderer = $renderer;
    $this->wp = $wp;
    $this->subscriptionFormBlock = $subscriptionFormBlock;
  }

  public function init() {
    // this has to be here until we drop support for WordPress < 5.0
    if (!function_exists('register_block_type')) return;
    $this->subscriptionFormBlock->init();

    if (is_admin()) {
      $this->initAdmin();
    } else {
      $this->initFrontend();
    }
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

    $this->subscriptionFormBlock->initAdmin();
  }

  private function initFrontend() {
    $this->subscriptionFormBlock->initFrontend();
  }
}
