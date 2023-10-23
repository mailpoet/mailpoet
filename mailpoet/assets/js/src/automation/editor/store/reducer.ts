import { State } from './types';

export function reducer(state: State, action): State {
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
        savedState: 'unsaved',
      };
    case 'SAVE':
      return {
        ...state,
        automationData: action.automation,
        savedState: 'saved',
      };
    case 'ACTIVATE':
      return {
        ...state,
        automationData: action.automation,
        savedState: 'saved',
      };
    case 'DEACTIVATE':
      return {
        ...state,
        automationData: action.automation,
        savedState: 'saved',
      };
    case 'TRASH':
      return {
        ...state,
        automationData: action.automation,
        savedState: 'saved',
      };
    case 'SAVING':
      return {
        ...state,
        savedState: 'saving',
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
        savedState: 'unsaved',
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
    case 'UPDATE_AUTOMATION_META':
      return {
        ...state,
        automationData: {
          ...state.automationData,
          meta: {
            ...state.automationData.meta,
            [action.key]: action.value,
          },
        },
        savedState: 'unsaved',
      };
    case 'SET_ERRORS':
      return {
        ...state,
        errors: action.errors,
      };
    case 'REMOVE_STEP_ERRORS': {
      const stepErrors = Object.entries(state.errors?.steps ?? {}).filter(
        ([id]) => id !== action.stepId,
      );
      return {
        ...state,
        errors:
          stepErrors.length > 0
            ? { steps: Object.fromEntries(stepErrors) }
            : undefined,
      };
    }
    default:
      return state;
  }
}
