import { select } from '@wordpress/data';
import MailPoet from 'mailpoet';
import { STORE_NAME } from '.';
import { Action, KeyActivationState, MssStatus } from './types';

export function setSetting(path: string[], value: any): Action {
  return { type: 'SET_SETTING', path, value };
}

export function setErrorFlag(value: boolean): Action {
  return { type: 'SET_ERROR_FLAG', value };
}

export function* openWoocommerceCustomizer(newsletterId?: string) {
  let id = newsletterId;
  if (!id) {
    const { res, success, error } = yield {
      type: 'CALL_API',
      endpoint: 'settings',
      action: 'set',
      data: { 'woocommerce.use_mailpoet_editor': 1 },
    };
    if (!success) {
      return { type: 'SAVE_FAILED', error };
    }
    id = res.data.woocommerce.transactional_email_id;
  }
  window.location.href = `?page=mailpoet-newsletter-editor&id=${id}`;
  return null;
}

export function updateKeyActivationState(fields: Partial<KeyActivationState>): Action {
  return { type: 'UPDATE_KEY_ACTIVATION_STATE', fields };
}

export function* saveSettings() {
  yield { type: 'SAVE_STARTED' };
  const data = select(STORE_NAME).getSettings();
  const { success, error } = yield {
    type: 'CALL_API',
    endpoint: 'settings',
    action: 'set',
    data,
  };
  if (!success) {
    return { type: 'SAVE_FAILED', error };
  }
  yield { type: 'TRACK_SETTINGS_SAVED' };
  return { type: 'SAVE_DONE' };
}

export function* verifyMssKey(key: string, activateMssIfKeyValid: boolean) {
  const { success, error, res } = yield {
    type: 'CALL_API',
    endpoint: 'services',
    action: 'checkMSSKey',
    data: { key },
  };
  if (!success) {
    return updateKeyActivationState({
      mssStatus: 'invalid',
      mssMessage: error.join(' ') || null,
    });
  }
  const fields: Partial<KeyActivationState> = {
    mssMessage: res.data.message || null,
  };
  if (activateMssIfKeyValid) {
    const call = yield {
      type: 'CALL_API',
      endpoint: 'settings',
      action: 'set',
      data: {
        mta_group: 'mailpoet',
        mta: { method: 'MailPoet', mailpoet_api_key: key },
        signup_confirmation: { enabled: '1' },
      },
    };
    if (!call.success) {
      fields.mssStatus = 'valid_mss_not_active';
    } else {
      yield setSetting(['mta_group'], 'mailpoet');
      yield setSetting(['mta', 'method'], 'MailPoet');
      yield setSetting(['mta', 'mailpoet_api_key'], key);
      yield setSetting(['signup_confirmation', 'enabled'], '1');
      fields.mssStatus = 'valid_mss_active';
    }
  } else {
    fields.mssStatus = 'valid_mss_not_active';
  }
  yield updateKeyActivationState(fields);
  return fields.mssStatus;
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
      premiumStatus: 'invalid',
      premiumMessage: error.join(' ') || null,
    });
  }

  yield updateKeyActivationState({ premiumMessage: null });
  // install/activate Premium plugin
  let pluginInstalled = res.meta.premium_plugin_installed;
  let pluginActive = res.meta.premium_plugin_active;

  if (!pluginInstalled) {
    const actions = installPremiumPlugin();
    let action = actions.next();
    while (!action.done) {
      yield action.value;
      action = actions.next();
    }
    pluginInstalled = action.value;
  }

  if (pluginInstalled && !pluginActive) {
    pluginActive = yield* activatePremiumPlugin(!res.meta.premium_plugin_installed);
  }

  if (pluginInstalled && pluginActive) {
    yield updateKeyActivationState({ premiumStatus: 'valid_premium_plugin_active' });
  }

  MailPoet.trackEvent(
    'User has validated a Premium key',
    {
      'MailPoet Free version': MailPoet.version,
      'Premium plugin is active': pluginActive,
    }
  );

  return null;
}

export function* activatePremiumPlugin(isAfterInstall) {
  const doneStatus = isAfterInstall ? 'install_done' : 'activate_done';
  const errorStatus = isAfterInstall ? 'install_activating_error' : 'activate_error';
  yield updateKeyActivationState({
    premiumStatus: 'valid_premium_plugin_being_activated',
    premiumInstallationStatus: isAfterInstall ? 'install_activating' : 'activate_activating',
  });
  const call = yield {
    type: 'CALL_API',
    endpoint: 'premium',
    action: 'activatePlugin',
  };
  if (call && !call.success) {
    yield updateKeyActivationState({ premiumInstallationStatus: errorStatus });
    return false;
  }
  yield updateKeyActivationState({ premiumInstallationStatus: doneStatus });
  return true;
}

export function* installPremiumPlugin() {
  yield updateKeyActivationState({
    premiumStatus: 'valid_premium_plugin_being_installed',
    premiumInstallationStatus: 'install_installing',
  });
  const call = yield {
    type: 'CALL_API',
    endpoint: 'premium',
    action: 'installPlugin',
  };
  if (call && !call.success) {
    yield updateKeyActivationState({ premiumInstallationStatus: 'install_installing_error' });
    return false;
  }
  return yield* activatePremiumPlugin(true);
}
