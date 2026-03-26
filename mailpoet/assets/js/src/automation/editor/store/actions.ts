import { dispatch, select } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';
import { store as noticesStore } from '@wordpress/notices';
import { __ } from '@wordpress/i18n';
import { store as interfaceStore } from '@wordpress/interface';
import { store as preferencesStore } from '@wordpress/preferences';
import { addQueryArgs } from '@wordpress/url';
import { storeName } from './constants';
import { Feature, State } from './types';
import { LISTING_NOTICES } from '../../listing/automation-listing-notices';
import { MailPoet } from '../../../mailpoet';
import { AutomationStatus } from '../../listing/automation';
import { sendTelemetryEvent } from '../telemetry';

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
  sendTelemetryEvent('validation_error', {
    error_type:
      Array.isArray(payload) && payload.length > 0
        ? String(payload[0])
        : 'unknown',
    automation_id: select(storeName).getAutomationData()?.id ?? null,
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
  void dispatch(storeName).closeActivationPanel();
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

  yield {
    type: 'SAVING',
  };

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
  let data;
  try {
    data = yield apiFetch({
      path: `/automations/${automation.id}`,
      method: 'PUT',
      data: {
        ...automation,
        status: AutomationStatus.ACTIVE,
      },
    });
  } catch {
    sendTelemetryEvent('button_error', {
      button_label: 'activate',
      automation_id: automation.id,
    });
    throw new Error(__('Failed to activate automation.', 'mailpoet'));
  }

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
    sendTelemetryEvent('button_success', {
      button_label: 'activate',
      automation_id: automation.id,
    });
  }

  return {
    type: 'ACTIVATE',
    automation: data?.data ?? automation,
    saved: !!data?.data,
  } as const;
}

export function* deactivate(
  deactivateAutomationRuns = true,
  telemetryContext?: { source: 'header' | 'modal'; selected_option?: string },
) {
  const automation = select(storeName).getAutomationData();
  let data;
  try {
    data = yield apiFetch({
      path: `/automations/${automation.id}`,
      method: 'PUT',
      data: {
        ...automation,
        status: deactivateAutomationRuns
          ? AutomationStatus.DRAFT
          : AutomationStatus.DEACTIVATING,
      },
    });
  } catch {
    if (telemetryContext) {
      sendTelemetryEvent('button_error', {
        button_label: 'deactivate',
        automation_id: automation.id,
        ...(telemetryContext.source === 'modal' && {
          modal_title: 'deactivate_automation',
          selected_option: telemetryContext.selected_option ?? null,
        }),
      });
    }
    throw new Error(__('Failed to deactivate automation.', 'mailpoet'));
  }

  const emitSuccess = () => {
    if (!telemetryContext) return;
    const eventSuffix =
      telemetryContext.source === 'modal'
        ? 'modal_button_success'
        : 'button_success';
    sendTelemetryEvent(eventSuffix, {
      button_label: 'deactivate',
      automation_id: automation.id,
      ...(telemetryContext.source === 'modal' && {
        modal_title: 'deactivate_automation',
        selected_option: telemetryContext.selected_option ?? null,
      }),
    });
  };

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
    emitSuccess();
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
    emitSuccess();
  }

  return {
    type: 'DEACTIVATE',
    automation: data?.data ?? automation,
  } as const;
}

export function* trash(onTrashed: () => void = undefined) {
  const automation = select(storeName).getAutomationData();
  let data;
  try {
    data = yield apiFetch({
      path: `/automations/${automation.id}`,
      method: 'PUT',
      data: {
        ...automation,
        status: AutomationStatus.TRASH,
      },
    });
  } catch {
    sendTelemetryEvent('button_error', {
      button_label: 'move_to_trash',
      automation_id: automation.id,
    });
    throw new Error(__('Failed to move automation to trash.', 'mailpoet'));
  }

  onTrashed?.();

  if (data?.data?.status === AutomationStatus.TRASH) {
    sendTelemetryEvent('button_success', {
      button_label: 'move_to_trash',
      automation_id: automation.id,
    });
    if (window.parent && window.parent !== window) {
      window.parent.postMessage(
        {
          type: 'mailpoet-navigate-to-automation-listing',
          notice: LISTING_NOTICES.automationDeleted,
          'notice-args': [automation.name],
        },
        window.location.origin,
      );
    } else {
      window.location.href = addQueryArgs(MailPoet.urls.automationListing, {
        notice: LISTING_NOTICES.automationDeleted,
        'notice-args': [automation.name],
      });
    }
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

export function registerFilterType(filterType) {
  return {
    type: 'REGISTER_FILTER_TYPE',
    filterType,
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

export function removeStepErrors(stepId) {
  return {
    type: 'REMOVE_STEP_ERRORS',
    stepId,
  };
}

export function alterContext(context: string, key: string, value: unknown) {
  return {
    type: 'UPDATE_CONTEXT',
    context,
    key,
    value,
  };
}

export function setFullscreenForced(value: boolean) {
  return {
    type: 'SET_FULLSCREEN_FORCED',
    value,
  } as const;
}
