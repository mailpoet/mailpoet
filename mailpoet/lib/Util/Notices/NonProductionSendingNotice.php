<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Config\AccessControl;
use MailPoet\Mailer\SendingEnvironment;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class NonProductionSendingNotice {
  public const ACTION = 'mailpoet_allow_non_production_sending';

  private WPFunctions $wp;

  private SendingEnvironment $sendingEnvironment;

  public function __construct(
    WPFunctions $wp,
    SendingEnvironment $sendingEnvironment
  ) {
    $this->wp = $wp;
    $this->sendingEnvironment = $sendingEnvironment;
  }

  public function init(bool $shouldDisplay): ?Notice {
    // admin-post.php is not a MailPoet page, so the handler must be registered on every request.
    $this->wp->addAction('admin_post_' . self::ACTION, [$this, 'handleAllowSending']);

    if ($this->wp->isAdmin() && !$this->wp->wpDoingCron() && !$this->wp->wpDoingAjax()) {
      $this->sendingEnvironment->clearOverrideOnProduction();
    }

    if (!$shouldDisplay || $this->sendingEnvironment->isSendingAllowed()) {
      return null;
    }
    return $this->display();
  }

  public function handleAllowSending(): void {
    if (!$this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_SETTINGS)) {
      $this->wp->wpDie(esc_html__('You do not have permission to perform this action.', 'mailpoet'), '', ['response' => 403]);
      return;
    }

    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized through WPFunctions below.
    $rawNonce = isset($_POST['_wpnonce']) ? $this->wp->wpUnslash($_POST['_wpnonce']) : '';
    $nonce = is_string($rawNonce) ? $this->wp->sanitizeTextField($rawNonce) : '';
    if (!$this->wp->wpVerifyNonce($nonce, self::ACTION)) {
      $this->wp->wpDie(esc_html__('Security check failed.', 'mailpoet'), '', ['response' => 403]);
      return;
    }

    if (!$this->sendingEnvironment->isProduction()) {
      $this->sendingEnvironment->allowSending();
    }

    $this->wp->wpSafeRedirect($this->wp->wpGetReferer() ?: $this->wp->adminUrl('admin.php?page=mailpoet-homepage'));
    $this->terminate();
  }

  protected function terminate(): void {
    exit;
  }

  private function display(): Notice {
    $message = '<p><strong>' . sprintf(
      // translators: %1$s is the site environment type, for example "staging" or "local".
      esc_html__('MailPoet isn’t sending emails because this is a %1$s site.', 'mailpoet'),
      esc_html($this->sendingEnvironment->getEnvironmentType())
    ) . '</strong> '
      . esc_html__('Newsletters, post notifications, automation emails and bulk confirmation resends wait in the queue. Sign-up confirmations, test emails, WordPress and WooCommerce emails, and the Premium “Send notification email” automation step still go out. Everything waiting in the queue goes out once sending is on.', 'mailpoet')
      . '</p>';

    if ($this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_SETTINGS)) {
      $message .= '<p>' . esc_html__('If this is your live site, turn sending on. You can change this later in Settings › Advanced.', 'mailpoet') . '</p>'
        . $this->renderForm();
    } else {
      $message .= '<p>' . esc_html__('If this is your live site, ask an administrator to turn sending on.', 'mailpoet') . '</p>';
    }

    return Notice::displayWarning($message, '', '', false, $this->allowedTags());
  }

  private function renderForm(): string {
    return sprintf(
      '<form method="post" action="%1$s">'
        . '<input type="hidden" name="action" value="%2$s">'
        . '<input type="hidden" name="_wpnonce" value="%3$s">'
        . '<p><button type="submit" class="button button-primary">%4$s</button></p>'
      . '</form>',
      esc_url($this->wp->adminUrl('admin-post.php')),
      esc_attr(self::ACTION),
      esc_attr($this->wp->wpCreateNonce(self::ACTION)),
      esc_html__('Turn sending on', 'mailpoet')
    );
  }

  private function allowedTags(): array {
    return [
      'form' => ['method' => true, 'action' => true, 'class' => true],
      'input' => ['type' => true, 'name' => true, 'value' => true],
      'button' => ['type' => true, 'class' => true],
      'strong' => [],
    ];
  }
}
