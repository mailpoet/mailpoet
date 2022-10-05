import _ from 'lodash';
import { t } from 'common/functions';
import { State, Settings } from './types';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
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

export function getSavingError(state: State): string[] {
  return state.save.error;
}

export function hasWooCommerce(state: State) {
  return state.flags.woocommerce;
}

export function hasMembersPlugin(state: State) {
  return state.flags.membersPlugin;
}

export function isBuiltInCaptchaSupported(state: State) {
  return state.flags.builtInCaptcha;
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

export function getDefaultSegments(state: State) {
  return state.segments.filter((seg) => seg.type === 'default');
}

export function getPages(state: State) {
  return state.pages;
}

export function getKeyActivationState(state: State) {
  return state.keyActivation;
}

export function getPaths(state: State) {
  return state.paths;
}

export function getWebHosts(state: State) {
  return {
    ...state.hosts.web,
    manual: {
      name: t('notListed'),
      emails: 25,
      interval: 5,
    },
  };
}

export function getAmazonSesOptions(state: State) {
  return state.hosts.smtp.AmazonSES;
}

export function getSendGridOptions(state: State) {
  return state.hosts.smtp.SendGrid;
}

export function getTestEmailState(state: State) {
  return state.testEmail;
}

export function hasReEngagementNotice(state: State): boolean {
  return state.reEngagement.showNotice;
}

export function getReEngagementAction(state: State) {
  return state.reEngagement.action;
}

export function hasUnsavedChanges(state: State) {
  return (
    (state.save.hasUnsavedChanges || state.save.inProgress) &&
    !_.isEqual(state.data, state.originalData)
  );
}
