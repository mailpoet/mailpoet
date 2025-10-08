<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce\Emails;

use MailPoet\WPCOM\DotcomHelperFunctions;

if (!defined('ABSPATH')) exit;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps -- WC_Email properties use snake case.
// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WC_Email methods use snake case.

/**
 * Marketing Confirmation Email
 * 
 * This email is sent to confirm marketing subscriptions.
 * Only available in Garden environment.
 */
class MarketingConfirmation extends \WC_Email {
  /**
   * Email group slug.
   *
   * @var string
   */
  protected $email_group;

  public function __construct() {
    $this->id = 'mailpoet_marketing_confirmation';
    $this->title = __('MailPoet Marketing Confirmation', 'mailpoet');
    $this->description = __('Email sent to confirm marketing subscriptions.', 'mailpoet');
    $this->customer_email = true;
    $this->email_group = 'marketing';
    $this->heading = $this->get_default_heading();
    $this->subject = $this->get_default_subject();
    $this->template_base = $this->get_template_base_path();
    $this->template_html = 'emails/marketing-confirmation.php';
    $this->template_plain = 'emails/plain/marketing-confirmation.php';
    $this->placeholders = [
      '{site_title}' => $this->get_blogname(),
      '{activation_link}' => '',
      '{subscriber_firstname}' => '',
    ];

    // Call parent constructor
    parent::__construct();
  }

  /**
   * Get template base path.
   */
  public function get_template_base_path() {
    return __DIR__ . '/Templates/';
  }

  /**
   * Get email subject.
   */
  public function get_default_subject() {
    return __('Confirm your subscription to {site_title}', 'mailpoet');
  }

  /**
   * Get email heading.
   */
  public function get_default_heading() {
    return __('Confirm your subscription to {site_title}', 'mailpoet');
  }

  /**
   * Trigger the sending of this email.
   *
   * @param string $to Email address to send to.
   * @param string $activation_link Activation link for the subscriber.
   * @param string $subscriber_firstname First name of the subscriber.
   */
  public function trigger($to, $activation_link = '', $subscriber_firstname = '') {
    $this->setup_locale();

    if ($this->is_enabled() && $to) {
      $this->recipient = $to;
      $this->placeholders['{activation_link}'] = $activation_link;
      $this->placeholders['{subscriber_firstname}'] = $subscriber_firstname;
      
      $this->send($to, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
    }

    $this->restore_locale();
  }

  /**
   * Get content html.
   */
  public function get_content_html() {
    return wc_get_template_html(
      $this->template_html,
      [
        'email_heading' => $this->get_heading(),
        'additional_content' => $this->get_additional_content(),
        'email' => $this,
        'activation_link' => $this->placeholders['{activation_link}'],
        'subscriber_firstname' => $this->placeholders['{subscriber_firstname}'],
      ],
      '',
      $this->template_base
    );
  }

  /**
   * Get content plain.
   */
  public function get_content_plain() {
    return wc_get_template_html(
      $this->template_plain,
      [
        'email_heading' => $this->get_heading(),
        'additional_content' => $this->get_additional_content(),
        'email' => $this,
        'activation_link' => $this->placeholders['{activation_link}'],
        'subscriber_firstname' => $this->placeholders['{subscriber_firstname}'],
      ],
      '',
      $this->template_base
    );
  }

  /**
   * Initialize settings form fields.
   */
  public function init_form_fields() {
    $this->form_fields = [
      'enabled' => [
        'title' => __('Enable/Disable', 'mailpoet'),
        'type' => 'checkbox',
        'label' => __('Enable this email notification', 'mailpoet'),
        'default' => 'yes',
      ],
      'subject' => [
        'title' => __('Subject', 'mailpoet'),
        'type' => 'text',
        'desc_tip' => true,
        'description' => sprintf(
          // translators: %s is a list of available placeholders
          __('Available placeholders: %s', 'mailpoet'),
          '<code>{site_title}, {activation_link}, {subscriber_firstname}</code>'
        ),
        'placeholder' => $this->get_default_subject(),
        'default' => '',
      ],
      'heading' => [
        'title' => __('Email heading', 'mailpoet'),
        'type' => 'text',
        'desc_tip' => true,
        'description' => sprintf(
          // translators: %s is a list of available placeholders
          __('Available placeholders: %s', 'mailpoet'),
          '<code>{site_title}, {activation_link}, {subscriber_firstname}</code>'
        ),
        'placeholder' => $this->get_default_heading(),
        'default' => '',
      ],
      'additional_content' => [
        'title' => __('Additional content', 'mailpoet'),
        'description' => __('Text to appear below the main email content.', 'mailpoet'),
        'type' => 'textarea',
        'default' => '',
        'desc_tip' => true,
      ],
      'email_type' => [
        'title' => __('Email type', 'mailpoet'),
        'type' => 'select',
        'description' => __('Choose which format of email to send.', 'mailpoet'),
        'default' => 'html',
        'class' => 'email_type wc-enhanced-select',
        'options' => $this->get_email_type_options(),
        'desc_tip' => true,
      ],
    ];
  }

  /**
   * Check if this email should be available.
   */
  public static function is_available() {
    $dotcomHelperFunctions = new DotcomHelperFunctions();
    // Only available in Garden environment.
    return $dotcomHelperFunctions->isGarden();
  }
}
