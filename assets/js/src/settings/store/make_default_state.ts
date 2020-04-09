import MailPoet from 'mailpoet';
import { State, PremiumStatus, MssStatus } from './types';
import normalizeSettings from './normalize_settings';

export default function makeDefaultState(window: any): State {
  const pages = window.mailpoet_pages;
  const paths = window.mailpoet_paths;
  const segments = window.mailpoet_segments;
  const save = { inProgress: false, error: null };
  const data = normalizeSettings(window.mailpoet_settings);
  const flags = {
    error: false,
    newUser: !!window.mailpoet_is_new_user,
    woocommerce: !!window.mailpoet_woocommerce_active,
    membersPlugin: !!window.mailpoet_members_plugin_active,
    builtInCaptcha: window.mailpoet_built_in_captcha_supported,
  };

  let isKeyValid = null;
  let mssStatus = null;
  let premiumStatus = null;

  if (data.premium.premium_key || data.mta.mailpoet_api_key) {
    mssStatus = getMssStatus(window.mailpoet_mss_key_valid, data);
    premiumStatus = getPremiumStatus(
      window.mailpoet_premium_key_valid,
      window.mailpoet_premium_plugin_installed
    );
    isKeyValid = mssStatus !== MssStatus.INVALID || premiumStatus !== PremiumStatus.INVALID;
  }

  const keyActivation = {
    isKeyValid,
    mssStatus,
    premiumStatus,
    mssMessage: null,
    premiumMessage: null,
    showFromAddressModal: false,
    premiumInstallationStatus: null,
    key: data.premium.premium_key || data.mta.mailpoet_api_key,
  };
  return {
    data, flags, save, keyActivation, segments, pages, paths,
  };
}

function getPremiumStatus(keyValid, premiumInstalled): PremiumStatus {
  const pluginActive = !!MailPoet.premiumVersion;
  if (!keyValid) {
    return PremiumStatus.INVALID;
  }
  if (pluginActive) {
    return PremiumStatus.VALID_PREMIUM_PLUGIN_ACTIVE;
  }
  return premiumInstalled
    ? PremiumStatus.VALID_PREMIUM_PLUGIN_NOT_ACTIVE
    : PremiumStatus.VALID_PREMIUM_PLUGIN_NOT_INSTALLED;
}

function getMssStatus(keyValid, data): MssStatus {
  if (!keyValid) return MssStatus.INVALID;
  const mssActive = data.mta.method === 'MailPoet';
  return mssActive ? MssStatus.VALID_MSS_ACTIVE : MssStatus.VALID_MSS_NOT_ACTIVE;
}
