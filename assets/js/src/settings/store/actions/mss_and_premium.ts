import { select } from '@wordpress/data';

import MailPoet from 'mailpoet';
import { STORE_NAME } from 'settings/store';

import {
  Action, KeyActivationState, MssStatus, PremiumStatus,
} from 'settings/store/types';
import { setSettings, setSetting } from './settings';

export function updateKeyActivationState(fields: Partial<KeyActivationState>): Action {
  return { type: 'UPDATE_KEY_ACTIVATION_STATE', fields };
}

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

  const data = select(STORE_NAME).getSettings();
  data.mta_group = 'mailpoet';
  data.mta = { ...data.mta, method: 'MailPoet', mailpoet_api_key: key };
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
    fields.mssStatus = MssStatus.VALID_MSS_ACTIVE;
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
    MailPoet.trackEvent(
      'User has failed to validate a Premium key',
      {
        'MailPoet Free version': MailPoet.version,
        'Premium plugin is active': !!MailPoet.premiumVersion,
      }
    );
    return updateKeyActivationState({
      premiumStatus: PremiumStatus.INVALID,
      premiumMessage: error.join(' ') || null,
      code: res?.meta?.code,
    });
  }

  yield setSetting(['premium', 'premium_key'], key);

  const pluginActive = res.meta.premium_plugin_active;

  let status = PremiumStatus.VALID_PREMIUM_PLUGIN_NOT_ACTIVE;
  if (pluginActive) {
    status = PremiumStatus.VALID_PREMIUM_PLUGIN_ACTIVE;
  }

  yield updateKeyActivationState({
    premiumMessage: null,
    premiumStatus: status,
    code: res?.meta?.code,
    downloadUrl: res?.meta?.premium_plugin_info?.download_link,
  });

  MailPoet.trackEvent(
    'User has validated a Premium key',
    {
      'MailPoet Free version': MailPoet.version,
    }
  );

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
