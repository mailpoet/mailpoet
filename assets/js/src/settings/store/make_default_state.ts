import MailPoet from 'mailpoet';
import { State, PremiumStatus, MssStatus } from './types';
import normalizeSettings from './normalize_settings';

export default function makeDefaultState(window: any): State {
  const pages = window.mailpoet_pages;
  const segments = window.mailpoet_segments;
  const save = { inProgress: false, error: null };
  const data = normalizeSettings(window.mailpoet_settings);
  const flags = {
    error: false,
    woocommerce: !!window.mailpoet_woocommerce_active,
    newUser: !!window.mailpoet_is_new_user,
    mssKeyValid: window.mailpoet_mss_key_valid,
    premiumKeyValid: window.mailpoet_premium_key_valid,
    premiumPluginInstalled: window.mailpoet_premium_plugin_installed,
  };
  const premiumStatus = getPremiumStatus(flags);
  const mssStatus = getMssStatus(flags, data);
  let isKeyValid = null;
  if (mssStatus !== null || premiumStatus !== null) {
    isKeyValid = mssStatus !== 'invalid' || premiumStatus !== 'invalid';
  }
  const keyActivation = {
    mssStatus,
    isKeyValid,
    premiumStatus,
    mssMessage: null,
    premiumMessage: null,
    showFromAddressModal: false,
    premiumInstallationStatus: null,
    key: data.premium.premium_key || data.mta.mailpoet_api_key,
  };
  return {
    data, flags, save, keyActivation, segments, pages,
  };
}

function getPremiumStatus(flags): PremiumStatus {
  const keyValid = flags.premiumKeyValid;
  const pluginInstalled = flags.premiumPluginInstalled;
  const pluginActive = !!MailPoet.premiumVersion;
  if (!keyValid) {
    return 'invalid';
  }
  if (pluginActive) {
    return 'valid_premium_plugin_active';
  }
  return pluginInstalled
    ? 'valid_premium_plugin_not_active'
    : 'valid_premium_plugin_not_installed';
}

function getMssStatus(flags, data): MssStatus {
  const keyValid = flags.mssKeyValid;
  if (!keyValid) return 'invalid';
  const mssActive = data.mta.method === 'MailPoet';
  return mssActive ? 'valid_mss_active' : 'valid_mss_not_active';
}
