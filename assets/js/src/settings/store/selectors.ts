import _ from 'lodash';
import { State, Settings } from './types';

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
  return _.get(state, 'mta.method') === 'MailPoet';
}

export function getSegments(state: State) {
  return state.segments;
}

export function getPages(state: State) {
  return state.pages;
}
