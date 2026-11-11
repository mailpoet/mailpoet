<?php declare(strict_types = 1);

namespace integration\AdminPages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Referrals\ReferralDetector;
use MailPoet\Settings\SettingsController;

class PageRendererTest extends \MailPoetTest {
  public function testItDisplaysPageWithoutDeprecationsWhenInstalledAtIsNotSet(): void {
    $settings = $this->diContainer->get(SettingsController::class);
    $originalValue = $settings->get('installed_at');
    $settings->delete('installed_at');
    $this->assertNull($settings->get('installed_at'));

    $deprecations = [];
    set_error_handler(function (int $errno, string $errstr) use (&$deprecations) {
      $deprecations[] = $errstr;
      return true;
    }, E_DEPRECATED);
    ob_start();
    try {
      $this->diContainer->get(PageRenderer::class)->displayPage('blank.html');
    } finally {
      ob_end_clean();
      restore_error_handler();
      if ($originalValue !== null) {
        $settings->set('installed_at', $originalValue);
      }
    }

    $this->assertSame([], $deprecations);
  }

  public function testItDoesNotPassInvalidReferralIdToPage(): void {
    $this->diContainer->get(SettingsController::class)->set(ReferralDetector::REFERRAL_SETTING_NAME, 'invalid referral id');

    $output = $this->renderBlankPage();

    $this->assertStringContainsString('var mailpoet_referral_id = null;', $output);
  }

  public function testItPassesValidReferralIdToPage(): void {
    $this->diContainer->get(SettingsController::class)->set(ReferralDetector::REFERRAL_SETTING_NAME, 'valid_referral-id');

    $output = $this->renderBlankPage();

    $this->assertStringContainsString('var mailpoet_referral_id = "valid_referral-id";', $output);
  }

  public function testItPassesSiteTimeZoneToPage(): void {
    $originalTimeZone = get_option('timezone_string');
    update_option('timezone_string', 'America/New_York');

    try {
      $output = $this->renderBlankPage();
    } finally {
      if ($originalTimeZone === false) {
        delete_option('timezone_string');
      } else {
        update_option('timezone_string', $originalTimeZone);
      }
    }

    // The date helper in JS reads this name to get the UTC offset of each date.
    $this->assertStringContainsString('var mailpoet_date_timezone = "America/New_York";', $output);
  }

  private function renderBlankPage(): string {
    ob_start();
    try {
      $this->diContainer->get(PageRenderer::class)->displayPage('blank.html');
    } finally {
      $output = (string)ob_get_clean();
    }
    return $output;
  }
}
