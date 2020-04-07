<?php

namespace MailPoet\Config;

use MailPoet\Models\Subscriber;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\Util\License\License;
use MailPoet\WP\DateTime;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice as WPNotice;

class ServicesChecker {

  /** @var SettingsController */
  private $settings;

  public function __construct() {
    $this->settings = SettingsController::getInstance();
  }

  public function isMailPoetAPIKeyValid($displayErrorNotice = true, $forceCheck = false) {
    if (!$forceCheck && !Bridge::isMPSendingServiceEnabled()) {
      return null;
    }

    $mssKeySpecified = Bridge::isMSSKeySpecified();
    $mssKey = $this->settings->get(Bridge::API_KEY_STATE_SETTING_NAME);

    if (!$mssKeySpecified
      || empty($mssKey['state'])
      || $mssKey['state'] == Bridge::KEY_INVALID
    ) {
      if ($displayErrorNotice) {
        $error = '<h3>' . __('All sending is currently paused!', 'mailpoet') . '</h3>';
        $error .= '<p>' . __('Your key to send with MailPoet is invalid.', 'mailpoet') . '</p>';
        $error .= '<p><a '
          . ' href="https://account.mailpoet.com?s=' . (Subscriber::getTotalSubscribers() + 1) . '"'
          . ' class="button button-secondary" '
          . ' target="_blank"'
          . '>' . __('Visit MailPoet.com to purchase a key', 'mailpoet') . '</a></p>';

        WPNotice::displayError($error, '', '', false, false);
      }
      return false;
    } elseif ($mssKey['state'] == Bridge::KEY_EXPIRING
      && !empty($mssKey['data']['expire_at'])
    ) {
      if ($displayErrorNotice) {
        $dateTime = new DateTime();
        $date = $dateTime->formatDate(strtotime($mssKey['data']['expire_at']));
        $error = Helpers::replaceLinkTags(
          WPFunctions::get()->__("Your newsletters are awesome! Don't forget to [link]upgrade your MailPoet email plan[/link] by %s to keep sending them to your subscribers.", 'mailpoet'),
          'https://account.mailpoet.com?s=' . Subscriber::getTotalSubscribers(),
          ['target' => '_blank']
        );
        $error = sprintf($error, $date);
        WPNotice::displayWarning($error);
      }
      return true;
    } elseif ($mssKey['state'] == Bridge::KEY_VALID) {
      return true;
    }

    return false;
  }

  public function isPremiumKeyValid($displayErrorNotice = true) {
    $premiumKeySpecified = Bridge::isPremiumKeySpecified();
    $premiumPluginActive = License::getLicense();
    $premiumKey = $this->settings->get(Bridge::PREMIUM_KEY_STATE_SETTING_NAME);

    if (!$premiumPluginActive) {
      $displayErrorNotice = false;
    }

    if (!$premiumKeySpecified
      || empty($premiumKey['state'])
      || $premiumKey['state'] === Bridge::KEY_INVALID
      || $premiumKey['state'] === Bridge::KEY_ALREADY_USED
    ) {
      if ($displayErrorNotice) {
        $errorString = WPFunctions::get()->__('[link1]Register[/link1] your copy of the MailPoet Premium plugin to receive access to automatic upgrades and support. Need a license key? [link2]Purchase one now.[/link2]', 'mailpoet');
        $error = Helpers::replaceLinkTags(
          $errorString,
          'admin.php?page=mailpoet-settings#premium',
          [],
          'link1'
        );
        $error = Helpers::replaceLinkTags(
          $error,
          'admin.php?page=mailpoet-premium',
          [],
          'link2'
        );
        WPNotice::displayWarning($error);
      }
      return false;
    } elseif ($premiumKey['state'] === Bridge::KEY_EXPIRING
      && !empty($premiumKey['data']['expire_at'])
    ) {
      if ($displayErrorNotice) {
        $dateTime = new DateTime();
        $date = $dateTime->formatDate(strtotime($premiumKey['data']['expire_at']));
        $error = Helpers::replaceLinkTags(
          WPFunctions::get()->__("Your License Key for MailPoet is expiring! Don't forget to [link]renew your license[/link] by %s to keep enjoying automatic updates and Premium support.", 'mailpoet'),
          'https://account.mailpoet.com',
          ['target' => '_blank']
        );
        $error = sprintf($error, $date);
        WPNotice::displayWarning($error);
      }
      return true;
    } elseif ($premiumKey['state'] === Bridge::KEY_VALID) {
      return true;
    }

    return false;
  }

  public function isMailPoetAPIKeyPendingApproval(): bool {
    $mssActive = Bridge::isMPSendingServiceEnabled();
    $mssKeyValid = $this->isMailPoetAPIKeyValid();
    $mssKeyPendingApproval = $this->settings->get('mta.mailpoet_api_key_state.data.is_approved') === false;
    return $mssActive && $mssKeyValid && $mssKeyPendingApproval;
  }
}
