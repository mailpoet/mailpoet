<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use MailPoet\Config\AccessControl;
use MailPoet\EmailEditor\Engine\Dependency_Check;
use MailPoet\WP\Functions as WPFunctions;

class DependencyNotice {
  private const EMAIL_EDITOR_DEPENDENCY_NOTICE = 'email_editor_dependencies_not_met';
  private WPFunctions $wp;
  private Dependency_Check $dependencyCheck;

  public function __construct(
    WPFunctions $wp,
    Dependency_Check $dependencyCheck
  ) {
    $this->wp = $wp;
    $this->dependencyCheck = $dependencyCheck;
  }

  public function checkDependenciesAndEventuallyShowNotice(): bool {
    if ($this->dependencyCheck->are_dependencies_met()) {
      $this->wp->deleteTransient(self::EMAIL_EDITOR_DEPENDENCY_NOTICE);
      return false;
    }
    // For admins, we redirect to newsletters page and show notice there, for other users we display a notice immediately
    if ($this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_EMAILS)) {
      $this->wp->setTransient(self::EMAIL_EDITOR_DEPENDENCY_NOTICE, true);
      $this->wp->wpSafeRedirect($this->wp->adminUrl('admin.php?page=mailpoet-newsletters'));
      return true;
    } else {
      $this->displayMessage();
      return true;
    }
  }

  public function displayMessageIfNeeded(): void {
    if ($this->wp->getTransient(self::EMAIL_EDITOR_DEPENDENCY_NOTICE)) {
      $this->displayMessage();
    }
    $this->wp->deleteTransient(self::EMAIL_EDITOR_DEPENDENCY_NOTICE);
  }

  private function displayMessage(): void {
    $dependencyErrorMessage = sprintf(
      // translators: %1$s: WordPress version e.g. 6.7
      __('This email was created using the new editor, which requires WordPress version %1$s or higher. Please update your setup to continue editing or previewing this email.', 'mailpoet'),
      Dependency_Check::MIN_WP_VERSION,
    );
    echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html($dependencyErrorMessage) . '</p></div>';
  }
}
