import _ from 'lodash';
import MailPoet from 'mailpoet';
import {
  State, Settings, PremiumStatus, MssStatus,
} from './types';

export function getSetting(state: State, path: string[]): any {
  return _.get(state.data, path);
}

export function getSettings(state: State): Settings {
  return state.data;
}

export function isSaving(state: State): boolean {
  return state.save.inProgress;
}

export function hasErrorFlag(state: State): boolean {
  return state.flags.error;
}

export function hasSavingError(state: State): boolean {
  return state.save.error !== null;
}

export function getSavingError(state: State): any {
  return state.save.error;
}

export function hasWooCommerce(state: State) {
  return state.flags.woocommerce;
}

export function isNewUser(state: State) {
  return state.flags.newUser;
}

export function isMssActive(state: State) {
  return _.get(state, 'data.mta.method') === 'MailPoet';
}

export function getSegments(state: State) {
  return state.segments;
}

export function getPages(state: State) {
  return state.pages;
}

export function getPremiumStatus(state: State): PremiumStatus {
  const keyValid = state.flags.premiumKeyValid;
  const pluginInstalled = state.flags.premiumPluginInstalled;
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

export function getMssStatus(state: State): MssStatus {
  const keyValid = state.flags.mssKeyValid;
  const mssActive = isMssActive(state);
  if (!keyValid) {
    return 'invalid';
  }
  return mssActive ? 'valid_mss_active' : 'valid_mss_not_active';
}
