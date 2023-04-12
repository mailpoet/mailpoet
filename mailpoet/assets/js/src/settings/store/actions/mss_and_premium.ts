import { cloneDeep, set } from 'lodash';
import { select } from '@wordpress/data';

import { MailPoet } from 'mailpoet';

import { STORE_NAME } from 'settings/store/store_name';
import {
  KeyActivationState,
  MssStatus,
  PremiumStatus,
} from 'settings/store/types';
import { updateKeyActivationState } from './key_activation';
import { setSetting, setSettings } from './settings';
import { getMssStatus } from '../make_default_state';

export function* verifyMssKey(key: string) {
  const { success, error, res } = yield {
    type: 'CALL_API',
    endpoint: 'services',
    action: 'checkMSSKey',
    data: { key },
  };

  if (!success) {
    return updateKeyActivationState({
      mssStatus: MssStatus.INVALID,
      mssMessage: error.join(' ') || null,
    });
  }
  const fields: Partial<KeyActivationState> = {
    mssMessage: res.data.message || null,
  };

  if (res.data.state === 'valid_underprivileged') {
    fields.mssStatus = MssStatus.VALID_UNDERPRIVILEGED;
    fields.mssAccessRestriction = res.data?.result?.access_restriction ?? null;
    return updateKeyActivationState(fields);
  }

  const data = cloneDeep(select(STORE_NAME).getSettings());

  data.mta_group = 'mailpoet';
  data.mta = {
    ...data.mta,
    method: 'MailPoet',
    mailpoet_api_key: key,
  };

  data.mta = set(
    data.mta,
    'mailpoet_api_key_state.data.is_approved',
    res.data.result.data.is_approved,
  );
  data.signup_confirmation.enabled = '1';

  const call = yield {
    type: 'CALL_API',
    endpoint: 'settings',
    action: 'set',
    data,
  };
  if (!call.success) {
    fields.mssStatus = MssStatus.VALID_MSS_NOT_ACTIVE;
  } else {
    yield setSettings(call.res.data);
    fields.mssStatus = getMssStatus(
      Number(call.res.data.mta.mailpoet_api_key_state.code) === 200,
      call.res.data,
    );
  }
  return updateKeyActivationState(fields);
}

export function* verifyPremiumKey(key: string) {
  const { res, success, error } = yield {
    type: 'CALL_API',
    endpoint: 'services',
    action: 'checkPremiumKey',
    data: { key },
  };
  if (!success) {
    MailPoet.trackEvent('User has failed to validate a Premium key', {
      'Premium plugin is active': !!MailPoet.premiumVersion,
    });
    return updateKeyActivationState({
      premiumStatus: PremiumStatus.INVALID,
      premiumMessage: error.join(' ') || null,
      code: res?.meta?.code,
    });
  }

  yield setSetting(['premium', 'premium_key'], key);

  const pluginActive = res.meta.premium_plugin_active;
  const premiumInstalled = res.meta.premium_plugin_installed;

  let status = premiumInstalled
    ? PremiumStatus.VALID_PREMIUM_PLUGIN_NOT_ACTIVE
    : PremiumStatus.VALID_PREMIUM_PLUGIN_NOT_INSTALLED;
  if (pluginActive) {
    status = PremiumStatus.VALID_PREMIUM_PLUGIN_ACTIVE;
  }

  const fields: Partial<KeyActivationState> = {
    premiumMessage: null,
    premiumStatus: status,
    code: res?.meta?.code,
    downloadUrl: res?.meta?.premium_plugin_info?.download_link,
  };

  if (res.data?.state === 'valid_underprivileged') {
    fields.premiumStatus = PremiumStatus.VALID_UNDERPRIVILEGED;
  }

  yield updateKeyActivationState(fields);

  MailPoet.trackEvent('User has validated a Premium key');

  yield { type: 'SAVE_DONE' };

  return null;
}

export function* sendCongratulatoryMssEmail() {
  const call = yield {
    type: 'CALL_API',
    endpoint: 'services',
    action: 'sendCongratulatoryMssEmail',
  };
  if (call && call.success) {
    return updateKeyActivationState({
      congratulatoryMssEmailSentTo: call.res.data.email_address,
    });
  }
  return null;
}
