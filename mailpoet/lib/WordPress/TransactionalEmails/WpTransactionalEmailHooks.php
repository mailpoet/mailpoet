<?php declare(strict_types = 1);

namespace MailPoet\WordPress\TransactionalEmails;

use MailPoet\WP\Functions as WPFunctions;

class WpTransactionalEmailHooks {
  /** @var WpTransactionalEmails */
  private $wpTransactionalEmails;

  /** @var WpTransactionalEmailContext */
  private $context;

  /** @var WpTransactionalEmailRenderer */
  private $renderer;

  /** @var WPFunctions */
  private $wp;

  /**
   * Set by filterEmailChangeBody to flip the next wp_mail send to text/html.
   * Reset back to false the moment wp_mail_content_type fires so HTML doesn't
   * leak to a different email later in the same request.
   */
  private bool $sendNextAsHtml = false;

  public function __construct(
    WpTransactionalEmails $wpTransactionalEmails,
    WpTransactionalEmailContext $context,
    WpTransactionalEmailRenderer $renderer,
    WPFunctions $wp
  ) {
    $this->wpTransactionalEmails = $wpTransactionalEmails;
    $this->context = $context;
    $this->renderer = $renderer;
    $this->wp = $wp;
  }

  public function initialize(): void {
    $this->wp->addFilter('retrieve_password_notification_email', [$this, 'filterPasswordResetEmail'], 10, 4);
    $this->wp->addFilter('wp_new_user_notification_email', [$this, 'filterNewUserEmail'], 10, 3);
    $this->wp->addFilter('new_user_email_content', [$this, 'filterEmailChangeBody'], 10, 2);
    $this->wp->addFilter('password_change_email', [$this, 'filterPasswordChangeEmail'], 10, 3);
    $this->wp->addFilter('wp_mail_content_type', [$this, 'maybeHtmlContentType']);
  }

  /**
   * @param mixed $contentType
   * @return mixed
   */
  public function maybeHtmlContentType($contentType) {
    if ($this->sendNextAsHtml) {
      $this->sendNextAsHtml = false;
      return 'text/html';
    }
    return $contentType;
  }

  /**
   * @param mixed $email
   * @param mixed $key
   * @param mixed $userLogin
   * @param mixed $userData
   * @return mixed
   */
  public function filterPasswordResetEmail($email, $key, $userLogin, $userData) {
    if (!is_array($email)) {
      return $email;
    }
    $newsletter = $this->getActiveNewsletterFor(WpTransactionalEmails::KIND_PASSWORD_RESET);
    if ($newsletter === null) {
      return $email;
    }
    if (!$userData instanceof \WP_User || !is_string($key)) {
      return $email;
    }

    $context = $this->context->passwordReset($userData, $key);
    $rendered = $this->renderer->render($newsletter, $context, (int)$userData->ID);

    return $this->buildHtmlEmail($email, $rendered);
  }

  /**
   * @param mixed $email
   * @param mixed $user
   * @param mixed $blogname
   * @return mixed
   */
  public function filterNewUserEmail($email, $user, $blogname) {
    if (!is_array($email)) {
      return $email;
    }
    $newsletter = $this->getActiveNewsletterFor(WpTransactionalEmails::KIND_NEW_USER);
    if ($newsletter === null) {
      return $email;
    }
    if (!$user instanceof \WP_User) {
      return $email;
    }

    $context = $this->context->newUser($user);
    $rendered = $this->renderer->render($newsletter, $context, (int)$user->ID);

    return $this->buildHtmlEmail($email, $rendered);
  }

  /**
   * @param mixed $messageContent
   * @param mixed $newUserEmail
   * @return mixed
   */
  public function filterEmailChangeBody($messageContent, $newUserEmail) {
    $newsletter = $this->getActiveNewsletterFor(WpTransactionalEmails::KIND_EMAIL_CHANGE);
    if ($newsletter === null) {
      return $messageContent;
    }

    $currentUser = $this->wp->wpGetCurrentUser();
    if (!$currentUser instanceof \WP_User || !$currentUser->ID) {
      return $messageContent;
    }

    $hash = '';
    $newEmail = '';
    if (is_array($newUserEmail)) {
      $rawHash = $newUserEmail['hash'] ?? '';
      $rawNewEmail = $newUserEmail['newemail'] ?? '';
      $hash = is_string($rawHash) ? $rawHash : '';
      $newEmail = is_string($rawNewEmail) ? $rawNewEmail : '';
    } elseif (is_object($newUserEmail)) {
      $rawHash = $newUserEmail->hash ?? '';
      $rawNewEmail = $newUserEmail->newemail ?? '';
      $hash = is_string($rawHash) ? $rawHash : '';
      $newEmail = is_string($rawNewEmail) ? $rawNewEmail : '';
    }

    $context = $this->context->emailChange($currentUser, $newEmail, $hash);
    $rendered = $this->renderer->render($newsletter, $context, (int)$currentUser->ID);

    // The new_user_email_content filter only exposes the body string. Flip
    // the next wp_mail send to text/html via maybeHtmlContentType so the HTML
    // we just produced renders correctly in the recipient's mail client.
    $this->sendNextAsHtml = true;

    return $rendered['html'];
  }

  /**
   * @param mixed $email
   * @param mixed $user
   * @param mixed $userdata
   * @return mixed
   */
  public function filterPasswordChangeEmail($email, $user, $userdata) {
    if (!is_array($email)) {
      return $email;
    }
    $newsletter = $this->getActiveNewsletterFor(WpTransactionalEmails::KIND_PASSWORD_CHANGE);
    if ($newsletter === null) {
      return $email;
    }
    if (!$userdata instanceof \WP_User) {
      return $email;
    }

    $context = $this->context->passwordChange($userdata);
    $rendered = $this->renderer->render($newsletter, $context, (int)$userdata->ID);

    return $this->buildHtmlEmail($email, $rendered);
  }

  /**
   * @return \MailPoet\Entities\NewsletterEntity|null
   */
  private function getActiveNewsletterFor(string $kind) {
    if (!$this->wpTransactionalEmails->isActive($kind)) {
      return null;
    }
    return $this->wpTransactionalEmails->findByKind($kind);
  }

  /**
   * Substitute subject + body in a WP email array and append an HTML
   * Content-Type header. Preserves the original to/headers entries the
   * caller passed in.
   *
   * @param array<mixed, mixed> $email
   * @param array{html: string, text: string, subject: string} $rendered
   * @return array<mixed, mixed>
   */
  private function buildHtmlEmail(array $email, array $rendered): array {
    if ($rendered['html'] === '') {
      return $email;
    }

    $email['subject'] = $rendered['subject'];
    $email['message'] = $rendered['html'];

    $existingHeaders = $email['headers'] ?? '';
    $headers = [];
    if (is_array($existingHeaders)) {
      $headers = $existingHeaders;
    } elseif (is_string($existingHeaders) && $existingHeaders !== '') {
      $headers = preg_split("/\r?\n/", $existingHeaders) ?: [];
    }
    $headers = array_filter($headers, function ($header) {
      return is_string($header) && stripos($header, 'content-type:') === false;
    });
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $email['headers'] = array_values($headers);

    return $email;
  }
}
