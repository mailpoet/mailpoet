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

export const openSidebar =
  (key) =>
  ({ registry }) =>
    registry.dispatch(interfaceStore).enableComplementaryArea(storeName, key);

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
      status: 'active',
    },
  });

  const { createNotice } = dispatch(noticesStore as StoreDescriptor);
  void createNotice(
    'success',
    __('Well done! Automation is now activated!', 'mailpoet'),
    {
      type: 'snackbar',
    },
  );

  return {
    type: 'ACTIVATE',
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
      status: 'trash',
    },
  });

  onTrashed?.();

  if (data?.status === 'trash') {
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
  return {
    type: 'SET_ERRORS',
    errors,
  };
}
