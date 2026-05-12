<?php declare(strict_types = 1);

namespace MailPoet\WordPress\TransactionalEmails;

use MailPoet\WP\Functions as WPFunctions;

class WpTransactionalEmailTemplates {
  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  /**
   * Default subject (with personalization tags) for a kind.
   */
  public function getSubject(string $kind): string {
    switch ($kind) {
      case WpTransactionalEmails::KIND_PASSWORD_RESET:
        return __('Reset your password on <!--[mailpoet/site-title]-->', 'mailpoet');
      case WpTransactionalEmails::KIND_NEW_USER:
        return __('Welcome to <!--[mailpoet/site-title]-->', 'mailpoet');
      case WpTransactionalEmails::KIND_EMAIL_CHANGE:
        return __('Confirm your new email address on <!--[mailpoet/site-title]-->', 'mailpoet');
      case WpTransactionalEmails::KIND_PASSWORD_CHANGE:
        return __('Your password on <!--[mailpoet/site-title]--> was changed', 'mailpoet');
      default:
        return __('Notification from <!--[mailpoet/site-title]-->', 'mailpoet');
    }
  }

  /**
   * Default block markup for a kind. Returned content is the post_content of
   * a freshly created mailpoet_email post and is rendered through the same
   * @woocommerce/email-editor pipeline as user newsletters.
   */
  public function getContent(string $kind): string {
    switch ($kind) {
      case WpTransactionalEmails::KIND_PASSWORD_RESET:
        return $this->passwordResetContent();
      case WpTransactionalEmails::KIND_NEW_USER:
        return $this->newUserContent();
      case WpTransactionalEmails::KIND_EMAIL_CHANGE:
        return $this->emailChangeContent();
      case WpTransactionalEmails::KIND_PASSWORD_CHANGE:
        return $this->passwordChangeContent();
      default:
        return '';
    }
  }

  /**
   * Tokens that MUST appear in the rendered HTML for a given kind. Missing
   * tokens cause the filter interceptor to fall through to WP's default
   * email so users never receive an email that lacks its required action.
   *
   * @return string[]
   */
  public function getRequiredTokens(string $kind): array {
    switch ($kind) {
      case WpTransactionalEmails::KIND_PASSWORD_RESET:
        return ['mailpoet/wp-link-password-reset'];
      case WpTransactionalEmails::KIND_NEW_USER:
        // Either set-password or login URL is enough for the recipient
        // to act on the email.
        return ['mailpoet/wp-link-set-password|mailpoet/wp-link-login'];
      case WpTransactionalEmails::KIND_EMAIL_CHANGE:
        return ['mailpoet/wp-link-email-change-confirm'];
      case WpTransactionalEmails::KIND_PASSWORD_CHANGE:
        return [];
      default:
        return [];
    }
  }

  private function passwordResetContent(): string {
    return $this->layout(
      __('Reset your password', 'mailpoet'),
      __('Hi <!--[mailpoet/wp-user-display-name]-->,', 'mailpoet'),
      __("You recently requested to reset your password for your account on <!--[mailpoet/site-title]-->. Use the button below to choose a new one. This link will expire in 24 hours.\n\nIf you didn't request this, you can safely ignore this email.", 'mailpoet'),
      __('Reset password', 'mailpoet'),
      '[mailpoet/wp-link-password-reset]'
    );
  }

  private function newUserContent(): string {
    return $this->layout(
      __('Welcome aboard', 'mailpoet'),
      __('Hi <!--[mailpoet/wp-user-display-name]-->,', 'mailpoet'),
      __("Your account on <!--[mailpoet/site-title]--> is ready. Use the button below to set your password and sign in for the first time.\n\nYour username is <!--[mailpoet/wp-user-login]-->.", 'mailpoet'),
      __('Set your password', 'mailpoet'),
      '[mailpoet/wp-link-set-password]'
    );
  }

  private function emailChangeContent(): string {
    return $this->layout(
      __('Confirm your new email address', 'mailpoet'),
      __('Hi <!--[mailpoet/wp-user-display-name]-->,', 'mailpoet'),
      __("You recently asked to change the email address on your <!--[mailpoet/site-title]--> account to <!--[mailpoet/wp-user-new-email]-->. Use the button below to confirm the change.\n\nIf you didn't request this, you can safely ignore this email.", 'mailpoet'),
      __('Confirm new email', 'mailpoet'),
      '[mailpoet/wp-link-email-change-confirm]'
    );
  }

  private function passwordChangeContent(): string {
    return $this->layout(
      __('Your password was changed', 'mailpoet'),
      __('Hi <!--[mailpoet/wp-user-display-name]-->,', 'mailpoet'),
      __("This is a confirmation that the password for your account on <!--[mailpoet/site-title]--> was just changed.\n\nIf you didn't make this change, contact a site administrator straight away.", 'mailpoet'),
      __('Sign in', 'mailpoet'),
      '[mailpoet/wp-link-login]'
    );
  }

  private function layout(string $heading, string $greeting, string $body, string $buttonLabel, string $buttonUrl): string {
    $bodyParagraphs = '';
    foreach (explode("\n\n", $body) as $paragraph) {
      $paragraph = trim($paragraph);
      if ($paragraph === '') {
        continue;
      }
      $bodyParagraphs .= '<!-- wp:paragraph -->' . "\n";
      $bodyParagraphs .= '<p>' . $this->escapeHtmlPreservingPersonalizationTags($paragraph) . '</p>' . "\n";
      $bodyParagraphs .= '<!-- /wp:paragraph -->' . "\n";
    }

    return implode("\n", [
      '<!-- wp:heading {"level":1,"textAlign":"center"} -->',
      '<h1 class="wp-block-heading has-text-align-center">' . $this->wp->escHtml($heading) . '</h1>',
      '<!-- /wp:heading -->',
      '<!-- wp:paragraph -->',
      '<p>' . $this->escapeHtmlPreservingPersonalizationTags($greeting) . '</p>',
      '<!-- /wp:paragraph -->',
      $bodyParagraphs,
      '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->',
      '<div class="wp-block-buttons">',
      '<!-- wp:button -->',
      // Button href holds a personalization tag like [mailpoet/wp-link-...]
      // which esc_url would strip; the tag is resolved to a real URL at
      // render time and escaped through the existing rendering pipeline.
      '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . $buttonUrl . '">' . $this->wp->escHtml($buttonLabel) . '</a></div>',
      '<!-- /wp:button -->',
      '</div>',
      '<!-- /wp:buttons -->',
    ]);
  }

  private function escapeHtmlPreservingPersonalizationTags(string $text): string {
    $parts = preg_split('/(<!--\[(?:mailpoet|woocommerce)\/[a-zA-Z0-9\-\/]+(?:\s+[^\]]+)?\]-->)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($parts)) {
      return $this->wp->escHtml($text);
    }

    $escaped = array_map(function (string $part): string {
      if (preg_match('/^<!--\[(?:mailpoet|woocommerce)\/[a-zA-Z0-9\-\/]+(?:\s+[^\]]+)?\]-->$/', $part) === 1) {
        return $part;
      }
      return $this->wp->escHtml($part);
    }, $parts);

    return implode('', $escaped);
  }
}
