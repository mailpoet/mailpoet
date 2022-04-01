<?php

namespace MailPoet\Form;

class PreviewWidget extends \WP_Widget {

  /** @var string */
  private $formHtml;

  public function __construct(
    $formHtml
  ) {
    $this->formHtml = $formHtml;
    parent::__construct(
      'mailpoet_form_preview',
      'Dummy form form preview',
      []
    );
  }

  /**
   * Output the widget itself.
   */
  public function widget($args, $instance = null) {
    // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
    // phpcs:disable WordPressDotOrg.sniffs.OutputEscaping.UnescapedOutputParameter
    // We control the html
    echo $this->formHtml;
    // phpcs:enable WordPressDotOrg.sniffs.OutputEscaping.UnescapedOutputParameter
    // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
  }
}
