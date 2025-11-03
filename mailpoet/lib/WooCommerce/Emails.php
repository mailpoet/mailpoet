<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\WooCommerce\Emails\MarketingConfirmation;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WPCOM\DotcomHelperFunctions;

if (!defined('ABSPATH')) exit;

/**
 * WooCommerce Emails Manager
 *
 * Manages registration of MailPoet emails with WooCommerce.
 * Only available in Garden environment.
 */
class Emails {

  /** @var DotcomHelperFunctions */
  private $dotcomHelperFunctions;

  /** @var WPFunctions */
  private $wp;

  public function __construct() {
    $this->wp = new WPFunctions();
    $this->dotcomHelperFunctions = new DotcomHelperFunctions($this->wp);
  }

  /**
   * Initialize the email registration.
   */
  public function init() {
    // Only register in Garden environment.
    if (!$this->dotcomHelperFunctions->isGarden()) {
      return;
    }

    // Register the email classes with WooCommerce.
    $this->wp->addFilter('woocommerce_email_classes', [$this, 'registerEmailClasses']);
    // Register the emails for the block editor.
    $this->wp->addFilter('woocommerce_transactional_emails_for_block_editor', [$this, 'registerTransactionalEmailsForBlockEditor']);
    // Register the marketing email group title.
    $this->wp->addFilter('woocommerce_email_groups', [$this, 'registerEmailGroups']);
    // This filter is required because WCTransactionalEmailPostsGenerator does not provide the email's $template_base property when loading templates.
    // As a result, WooCommerce attempts to load the template from its default template directory instead of MailPoet's.
    // We need to apply this filter until this issue is addressed upstream to ensure MailPoet email templates are found.
    $this->wp->addFilter('wc_get_template', [$this, 'locateBlockTemplate'], 10, 4);
  }

  /**
   * Register MailPoet email classes with WooCommerce.
   *
   * @param array $email_classes Array of email classes.
   * @return array Modified array of email classes.
   */
  public function registerEmailClasses($email_classes) {
    $email_classes['mailpoet_marketing_confirmation'] = new MarketingConfirmation();

    return $email_classes;
  }

  /**
   * Register MailPoet transactional emails for the block editor.
   *
   * @param array $emails Array of email IDs.
   * @return array Modified array of email IDs.
   */
  public function registerTransactionalEmailsForBlockEditor($emails) {
    // Register marketing confirmation email for block editor
    $emails[] = 'mailpoet_marketing_confirmation';

    return $emails;
  }

  /**
   * Register email group titles.
   *
   * @param array $email_groups Array of email groups.
   * @return array Modified array of email groups.
   */
  public function registerEmailGroups($email_groups) {
    if (!isset($email_groups['marketing'])) {
      $email_groups['marketing'] = __('Marketing', 'mailpoet');
    }

    return $email_groups;
  }

  /**
   * Locate block templates for MailPoet emails.
   *
   * @param string $template The template path.
   * @param string $template_name The template name.
   * @param array $args The template arguments.
   * @param string $template_path The template path.
   * @return string The located template path.
   */
  public function locateBlockTemplate($template, $template_name, $args, $template_path) {
    // Only handle block templates for MailPoet emails
    if (strpos($template_name, 'emails/block/marketing-confirmation.php') !== 0) {
      return $template;
    }

    if (file_exists($template_path)) {
        return $template;
    }

    $mailpoet_template = __DIR__ . '/Emails/Templates/' . $template_name;
    if (file_exists($mailpoet_template)) {
        return $mailpoet_template;
    }

    return $template;
  }
}
