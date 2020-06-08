import MailPoet from 'mailpoet';

import {
  Action, KeyActivationState, MssStatus, PremiumStatus, PremiumInstallationStatus,
} from 'settings/store/types';
import { setSettings } from './settings';

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

  yield updateKeyActivationState({ premiumMessage: null });
  // install/activate Premium plugin
  let pluginInstalled = res.meta.premium_plugin_installed;
  let pluginActive = res.meta.premium_plugin_active;

  if (!pluginInstalled) {
    pluginInstalled = yield* installPremiumPlugin();
  }

  if (pluginInstalled && !pluginActive) {
    pluginActive = yield* activatePremiumPlugin(!res.meta.premium_plugin_installed);
  }

  if (pluginInstalled && pluginActive) {
    yield updateKeyActivationState({ premiumStatus: PremiumStatus.VALID_PREMIUM_PLUGIN_ACTIVE });
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
  const doneStatus = isAfterInstall
    ? PremiumInstallationStatus.INSTALL_DONE
    : PremiumInstallationStatus.ACTIVATE_DONE;
  const errorStatus = isAfterInstall
    ? PremiumInstallationStatus.INSTALL_ACTIVATING_ERROR
    : PremiumInstallationStatus.ACTIVATE_ERROR;
  yield updateKeyActivationState({
    premiumStatus: PremiumStatus.VALID_PREMIUM_PLUGIN_BEING_ACTIVATED,
    premiumInstallationStatus: isAfterInstall
      ? PremiumInstallationStatus.INSTALL_ACTIVATING
      : PremiumInstallationStatus.ACTIVATE_ACTIVATING,
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
    premiumStatus: PremiumStatus.VALID_PREMIUM_PLUGIN_BEING_INSTALLED,
    premiumInstallationStatus: PremiumInstallationStatus.INSTALL_INSTALLING,
  });
  const call = yield {
    type: 'CALL_API',
    endpoint: 'premium',
    action: 'installPlugin',
  };
  if (call && !call.success) {
    yield updateKeyActivationState({
      premiumInstallationStatus: PremiumInstallationStatus.INSTALL_INSTALLING_ERROR,
    });
    return false;
  }
  return yield* activatePremiumPlugin(true);
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
