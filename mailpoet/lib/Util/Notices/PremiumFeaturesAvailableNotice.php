<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Config\Installer;
use MailPoet\Config\ServicesChecker;
use MailPoet\Util\Helpers;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class PremiumFeaturesAvailableNotice {

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var ServicesChecker */
  private $servicesChecker;

  /** @var Installer */
  private $premiumInstaller;

  /** @var WPFunctions */
  private $wp;

  const DISMISS_NOTICE_TIMEOUT_SECONDS = 2592000; // 30 days
  const OPTION_NAME = 'dismissed-premium-features-available-notice';

  public function __construct(
    SubscribersFeature $subscribersFeature,
    ServicesChecker $servicesChecker,
    WPFunctions $wp
  ) {
    $this->subscribersFeature = $subscribersFeature;
    $this->servicesChecker = $servicesChecker;
    $this->premiumInstaller = new Installer(Installer::PREMIUM_PLUGIN_PATH);
    $this->wp = $wp;
  }

  public function init($shouldDisplay): ?Notice {
    if (
      $shouldDisplay
      && !$this->wp->getTransient(self::OPTION_NAME)
      && $this->subscribersFeature->hasValidPremiumKey()
      && (!Installer::isPluginInstalled(Installer::PREMIUM_PLUGIN_SLUG) || !$this->servicesChecker->isPremiumPluginActive())
    ) {
      return $this->display();
    }

    return null;
  }

  public function display(): Notice {
    $noticeString = __('Your current MailPoet plan includes advanced features, but they require the MailPoet Premium plugin to be installed and activated.', 'mailpoet');
    $extraClasses = 'mailpoet-dismissible-notice is-dismissible';

    // We reuse already existing translations from premium_messages.tsx
    if (!Installer::isPluginInstalled(Installer::PREMIUM_PLUGIN_SLUG)) {
      // The download URL requires the premium API key, which must not end up in a browser-visible
      // URL (history, referrers, access logs) -- it's submitted as a POST body field instead.
      $noticeString .= ' ' . $this->renderDownloadForm();
      return Notice::displaySuccess($noticeString, $extraClasses, self::OPTION_NAME, true, $this->downloadFormAllowedTags());
    }

    $noticeString .= ' [link]' . __('Activate MailPoet Premium plugin', 'mailpoet') . '[/link]';
    $link = $this->premiumInstaller->generatePluginActivationUrl(Installer::PREMIUM_PLUGIN_PATH);
    $noticeString = Helpers::replaceLinkTags($noticeString, $link, []);

    return Notice::displaySuccess($noticeString, $extraClasses, self::OPTION_NAME);
  }

  public function disable(): void {
    WPFunctions::get()->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }

  private function renderDownloadForm(): string {
    return sprintf(
      '<form method="post" action="%1$s" target="_blank" class="mailpoet-inline-form">'
        . '<input type="hidden" name="api_key" value="%2$s">'
        . '<button type="submit" class="button-link">%3$s</button>'
      . '</form>',
      esc_url(Installer::PREMIUM_PLUGIN_DOWNLOAD_URL),
      esc_attr($this->premiumInstaller->getPremiumKey()),
      esc_html__('Download MailPoet Premium plugin', 'mailpoet')
    );
  }

  private function downloadFormAllowedTags(): array {
    return [
      'form' => ['method' => true, 'action' => true, 'target' => true, 'class' => true],
      'input' => ['type' => true, 'name' => true, 'value' => true],
    ];
  }
}
