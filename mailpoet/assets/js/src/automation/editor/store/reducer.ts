import { Action } from '@wordpress/data';
import { State } from './types';

export function reducer(state: State, action: Action): State {
  switch (action.type) {
    case 'SET_ACTIVATION_PANEL_VISIBILITY':
      return {
        ...state,
        activationPanel: {
          ...state.activationPanel,
          isOpened: action.value,
        },
      };
    case 'TOGGLE_INSERTER_SIDEBAR':
      return {
        ...state,
        inserterSidebar: {
          ...state.inserterSidebar,
          isOpened: !state.inserterSidebar.isOpened,
        },
      };
    case 'SET_INSERTER_POPOVER':
      return {
        ...state,
        inserterPopover: action.data,
      };
    case 'SET_SELECTED_STEP':
      return {
        ...state,
        selectedStep: action.value,
      };
    case 'UPDATE_AUTOMATION':
      return {
        ...state,
        automationData: action.automation,
        automationSaved: false,
      };
    case 'SAVE':
      return {
        ...state,
        automationData: action.automation,
        automationSaved: true,
      };
    case 'ACTIVATE':
      return {
        ...state,
        automationData: action.automation,
        automationSaved: true,
      };
    case 'DEACTIVATE':
      return {
        ...state,
        automationData: action.automation,
        automationSaved: true,
      };
    case 'TRASH':
      return {
        ...state,
        automationData: action.automation,
        automationSaved: true,
      };
    case 'REGISTER_STEP_TYPE':
      return {
        ...state,
        stepTypes: {
          ...state.stepTypes,
          [action.stepType.key]: action.stepType,
        },
      };
    case 'UPDATE_STEP_ARGS': {
      const prevArgs = state.automationData.steps[action.stepId].args ?? {};

      const value =
        typeof action.value === 'function'
          ? action.value(prevArgs[action.name] ?? undefined)
          : action.value;

      const args =
        value === undefined
          ? Object.fromEntries(
              Object.entries(prevArgs).filter(([name]) => name !== action.name),
            )
          : { ...prevArgs, [action.name]: value };

      const step = { ...state.automationData.steps[action.stepId], args };

      const stepErrors = Object.values(state.errors?.steps ?? {}).filter(
        ({ step_id }) => step_id !== action.stepId,
      );

      return {
        ...state,
        automationData: {
          ...state.automationData,
          steps: {
            ...state.automationData.steps,
            [action.stepId]: step,
          },
        },
        automationSaved: false,
        selectedStep: step,
        errors:
          stepErrors.length > 0
            ? {
                ...state.errors,
                steps: Object.fromEntries(
                  stepErrors.map((error) => [error.step_id, error]),
                ),
              }
            : undefined,
      };
    }
    case 'SET_ERRORS':
      return {
        ...state,
        errors: action.errors,
      };
    default:
      return state;
  }
}
