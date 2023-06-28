import { dispatch, select } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';
import { store as noticesStore } from '@wordpress/notices';
import { __ } from '@wordpress/i18n';
import { store as interfaceStore } from '@wordpress/interface';
import { store as preferencesStore } from '@wordpress/preferences';
import { addQueryArgs } from '@wordpress/url';
import { storeName } from './constants';
import { Feature, State } from './types';
import { LISTING_NOTICE_PARAMETERS } from '../../listing/automation-listing-notices';
import { MailPoet } from '../../../mailpoet';
import { AutomationStatus } from '../../listing/automation';

const trackErrors = (errors) => {
  if (!errors?.steps) {
    return;
  }
  const payload = Object.keys(errors.steps as object).map((stepId) => {
    const error = errors.steps[stepId];
    const stepKey = select(storeName).getStepById(stepId)?.key;
    const fields = error.fields.length
      ? Object.keys(error.fields as object)
          .map((field) => `${stepKey}/${field}`)
          .reduce((prev, next) => prev.concat(next))
      : `${stepKey}:no_specific_field`;
    return fields;
  });

  MailPoet.trackEvent('Automations > Automation validation error', {
    errors: payload,
  });
};

export const openActivationPanel = () => ({
  type: 'SET_ACTIVATION_PANEL_VISIBILITY',
  value: true,
});
export const closeActivationPanel = () => ({
  type: 'SET_ACTIVATION_PANEL_VISIBILITY',
  value: false,
});

export const openSidebar = (key) => {
  dispatch(storeName).closeActivationPanel();
  return ({ registry }) =>
    registry.dispatch(interfaceStore).enableComplementaryArea(storeName, key);
};

export const closeSidebar =
  () =>
  ({ registry }) =>
    registry.dispatch(interfaceStore).disableComplementaryArea(storeName);

export const toggleFeature =
  (feature: Feature) =>
  ({ registry }) =>
    registry.dispatch(preferencesStore).toggle(storeName, feature);

export function toggleInserterSidebar() {
  return {
    type: 'TOGGLE_INSERTER_SIDEBAR',
  } as const;
}

export function setInserterPopover(data?: State['inserterPopover']) {
  return {
    type: 'SET_INSERTER_POPOVER',
    data,
  } as const;
}

export function selectStep(value) {
  return {
    type: 'SET_SELECTED_STEP',
    value,
  } as const;
}

export function setAutomationName(name) {
  const automation = select(storeName).getAutomationData();
  return {
    type: 'UPDATE_AUTOMATION',
    automation: {
      ...automation,
      name,
    },
  } as const;
}

export function* save() {
  const automation = select(storeName).getAutomationData();
  const data = yield apiFetch({
    path: `/automations/${automation.id}`,
    method: 'PUT',
    data: { ...automation },
  });

  const { createNotice } = dispatch(noticesStore);
  if (data?.data) {
    void createNotice(
      'success',
      __('The automation has been saved.', 'mailpoet'),
      {
        type: 'snackbar',
      },
    );
  }

  return {
    type: 'SAVE',
    automation: data?.data ?? automation,
  } as const;
}

export function* activate() {
  const automation = select(storeName).getAutomationData();
  const data = yield apiFetch({
    path: `/automations/${automation.id}`,
    method: 'PUT',
    data: {
      ...automation,
      status: AutomationStatus.ACTIVE,
    },
  });

  const { createNotice } = dispatch(noticesStore);
  if (data?.data.status === AutomationStatus.ACTIVE) {
    void createNotice(
      'success',
      __('Well done! Automation is now activated!', 'mailpoet'),
      {
        type: 'snackbar',
      },
    );
    MailPoet.trackEvent('Automations > Automation activated');
  }

  return {
    type: 'ACTIVATE',
    automation: data?.data ?? automation,
  } as const;
}

export function* deactivate(deactivateAutomationRuns = true) {
  const automation = select(storeName).getAutomationData();
  const data = yield apiFetch({
    path: `/automations/${automation.id}`,
    method: 'PUT',
    data: {
      ...automation,
      status: deactivateAutomationRuns
        ? AutomationStatus.DRAFT
        : AutomationStatus.DEACTIVATING,
    },
  });

  const { createNotice } = dispatch(noticesStore);
  if (
    deactivateAutomationRuns &&
    data?.data.status === AutomationStatus.DRAFT
  ) {
    void createNotice(
      'success',
      __('Automation is now deactivated!', 'mailpoet'),
      {
        type: 'snackbar',
      },
    );

    MailPoet.trackEvent('Automations > Automation deactivated', {
      type: 'immediate',
    });
  }
  if (
    !deactivateAutomationRuns &&
    data?.data.status === AutomationStatus.DEACTIVATING
  ) {
    void createNotice(
      'success',
      __(
        'Automation is deactivated. But recent users are still going through the flow.',
        'mailpoet',
      ),
      {
        type: 'snackbar',
      },
    );
    MailPoet.trackEvent('Automations > Automation deactivated', {
      type: 'continuous',
    });
  }

  return {
    type: 'DEACTIVATE',
    automation: data?.data ?? automation,
  } as const;
}

export function* trash(onTrashed: () => void = undefined) {
  const automation = select(storeName).getAutomationData();
  const data = yield apiFetch({
    path: `/automations/${automation.id}`,
    method: 'PUT',
    data: {
      ...automation,
      status: AutomationStatus.TRASH,
    },
  });

  onTrashed?.();

  if (data?.status === AutomationStatus.TRASH) {
    window.location.href = addQueryArgs(MailPoet.urls.automationListing, {
      [LISTING_NOTICE_PARAMETERS.automationDeleted]: automation.id,
    });
  }

  return {
    type: 'TRASH',
    automation: data?.data ?? automation,
  } as const;
}

export function updateAutomation(automation) {
  return {
    type: 'UPDATE_AUTOMATION',
    automation,
  } as const;
}

export function registerStepType(stepType) {
  return {
    type: 'REGISTER_STEP_TYPE',
    stepType,
  };
}

export function updateStepArgs(stepId, name, value) {
  return {
    type: 'UPDATE_STEP_ARGS',
    stepId,
    name,
    value,
  };
}

export function updateAutomationMeta(key, value) {
  return {
    type: 'UPDATE_AUTOMATION_META',
    key,
    value,
  };
}

export function setErrors(errors) {
  trackErrors(errors);
  return {
    type: 'SET_ERRORS',
    errors,
  };
}
