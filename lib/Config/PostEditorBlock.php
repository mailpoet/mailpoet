<?php

namespace MailPoet\PostEditorBlocks;

use MailPoet\Config\Env;
use MailPoet\Config\Renderer;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Form\Widget;
use MailPoet\WP\Functions as WPFunctions;

class SubscriptionFormBlock {
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
    $this->wp->registerBlockType('mailpoet/subscription-form-block-render', [
      'attributes' => [
        'form' => [
          'type' => 'number',
          'default' => null,
        ],
      ],
      'render_callback' => [$this, 'renderForm'],
    ]);
  }

  public function initAdmin() {
    $this->wp->registerBlockType('mailpoet/subscription-form-block', [
      'style' => 'mailpoetblock-form-block-css',
      'editor_script' => 'mailpoet/subscription-form-block',
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

  public function initFrontend() {
    $this->wp->registerBlockType('mailpoet/subscription-form-block', [
      'render_callback' => [$this, 'renderForm'],
    ]);
  }

  public function renderForm(array $attributes = []): string {
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
