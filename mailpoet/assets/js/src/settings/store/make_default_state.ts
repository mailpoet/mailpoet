import { MailPoet } from 'mailpoet';
import { MssStatus, PremiumStatus, State, TestEmailState } from './types';
import { normalizeSettings } from './normalize_settings';

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
  return mssActive
    ? MssStatus.VALID_MSS_ACTIVE
    : MssStatus.VALID_MSS_NOT_ACTIVE;
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function makeDefaultState(window: any): State {
  const pages = window.mailpoet_pages;
  const paths = window.mailpoet_paths;
  const segments = window.mailpoet_segments;
  const hosts = window.mailpoet_hosts;
  const save = { inProgress: false, error: null, hasUnsavedChanges: false };
  const data = normalizeSettings(
    window.mailpoet_settings as Record<string, unknown>,
  );
  const originalData = data;
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
      window.mailpoet_premium_plugin_installed,
    );
    isKeyValid =
      mssStatus !== MssStatus.INVALID ||
      premiumStatus !== PremiumStatus.INVALID;
  }

  const keyActivation = {
    isKeyValid,
    mssStatus,
    premiumStatus,
    mssMessage: null,
    premiumMessage: null,
    fromAddressModalCanBeShown: false,
    premiumInstallationStatus: null,
    key: data.premium.premium_key || data.mta.mailpoet_api_key,
    inProgress: false,
    congratulatoryMssEmailSentTo: null,
    downloadUrl: window.mailpoet_premium_plugin_download_url,
    activationUrl: window.mailpoet_premium_plugin_activation_url,
  };
  const testEmail = {
    state: TestEmailState.NONE,
    error: null,
  };
  const reEngagement = {
    showNotice: false,
    action: null,
  };
  return {
    data,
    originalData,
    flags,
    save,
    keyActivation,
    segments,
    pages,
    paths,
    hosts,
    testEmail,
    reEngagement,
  };
}
