import { dispatch, select, StoreDescriptor } from '@wordpress/data';
import { apiFetch } from '@wordpress/data-controls';
import { store as noticesStore } from '@wordpress/notices';
import { __ } from '@wordpress/i18n';
import { store as interfaceStore } from '@wordpress/interface';
import { store as preferencesStore } from '@wordpress/preferences';
import { addQueryArgs } from '@wordpress/url';
import { storeName } from './constants';
import { Feature, State } from './types';
import { LISTING_NOTICE_PARAMETERS } from '../../listing/workflow-listing-notices';
import { MailPoet } from '../../../mailpoet';
import { WorkflowStatus } from '../../listing/workflow';

const trackErrors = (errors) => {
  if (!errors?.steps) {
    return;
  }
  const payload = Object.keys(errors.steps as object).map((stepId) => {
    const error = errors.steps[stepId];
    const stepKey = select(storeName).getStepById(stepId)?.key;
    const fields = Object.keys(error.fields as object)
      .map((field) => `${stepKey}/${field}`)
      .reduce((prev, next) => prev.concat(next));
    return fields;
  });

  MailPoet.trackEvent('Automations > Workflow validation error', {
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

export function setWorkflowName(name) {
  const workflow = select(storeName).getWorkflowData();
  return {
    type: 'UPDATE_WORKFLOW',
    workflow: {
      ...workflow,
      name,
    },
  } as const;
}

export function* save() {
  const workflow = select(storeName).getWorkflowData();
  const data = yield apiFetch({
    path: `/workflows/${workflow.id}`,
    method: 'PUT',
    data: { ...workflow },
  });

  const { createNotice } = dispatch(noticesStore as StoreDescriptor);
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
    workflow: data?.data ?? workflow,
  } as const;
}

export function* activate() {
  const workflow = select(storeName).getWorkflowData();
  const data = yield apiFetch({
    path: `/workflows/${workflow.id}`,
    method: 'PUT',
    data: {
      ...workflow,
      status: WorkflowStatus.ACTIVE,
    },
  });

  const { createNotice } = dispatch(noticesStore as StoreDescriptor);
  if (data?.data.status === WorkflowStatus.ACTIVE) {
    void createNotice(
      'success',
      __('Well done! Automation is now activated!', 'mailpoet'),
      {
        type: 'snackbar',
      },
    );
    MailPoet.trackEvent('Automations > Workflow activated');
  }

  return {
    type: 'ACTIVATE',
    workflow: data?.data ?? workflow,
  } as const;
}

export function* deactivate(deactivateWorkflowRuns = true) {
  const workflow = select(storeName).getWorkflowData();
  const data = yield apiFetch({
    path: `/workflows/${workflow.id}`,
    method: 'PUT',
    data: {
      ...workflow,
      status: deactivateWorkflowRuns
        ? WorkflowStatus.DRAFT
        : WorkflowStatus.DEACTIVATING,
    },
  });

  const { createNotice } = dispatch(noticesStore as StoreDescriptor);
  if (deactivateWorkflowRuns && data?.data.status === WorkflowStatus.DRAFT) {
    void createNotice(
      'success',
      __('Automation is now deactivated!', 'mailpoet'),
      {
        type: 'snackbar',
      },
    );

    MailPoet.trackEvent('Automations > Workflow deactivated', {
      type: 'immediate',
    });
  }
  if (
    !deactivateWorkflowRuns &&
    data?.data.status === WorkflowStatus.DEACTIVATING
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
    MailPoet.trackEvent('Automations > Workflow deactivated', {
      type: 'continuous',
    });
  }

  return {
    type: 'DEACTIVATE',
    workflow: data?.data ?? workflow,
  } as const;
}

export function* trash(onTrashed: () => void = undefined) {
  const workflow = select(storeName).getWorkflowData();
  const data = yield apiFetch({
    path: `/workflows/${workflow.id}`,
    method: 'PUT',
    data: {
      ...workflow,
      status: WorkflowStatus.TRASH,
    },
  });

  onTrashed?.();

  if (data?.status === WorkflowStatus.TRASH) {
    window.location.href = addQueryArgs(MailPoet.urls.automationListing, {
      [LISTING_NOTICE_PARAMETERS.workflowDeleted]: workflow.id,
    });
  }

  return {
    type: 'TRASH',
    workflow: data?.data ?? workflow,
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

export function setErrors(errors) {
  trackErrors(errors);
  return {
    type: 'SET_ERRORS',
    errors,
  };
}
