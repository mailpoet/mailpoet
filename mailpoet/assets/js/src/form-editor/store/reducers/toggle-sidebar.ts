import { ToggleAction, ToggleBlockInserterAction } from '../actions_types';
import { BlockInsertionPoint } from '../state_types';

export const toggleSidebar = (state, action: ToggleAction) => ({
  ...state,
  sidebarOpened: action.toggleTo,
});

export const toggleInserterSidebar = (
  state,
  action: ToggleBlockInserterAction,
) => {
  let inserterPanel: BlockInsertionPoint;
  if (!action.value) {
    inserterPanel = null;
  } else if (action.value === true) {
    inserterPanel = {
      rootClientId: undefined,
      insertionIndex: undefined,
    };
  } else {
    inserterPanel = action.value;
  }
  return {
    ...state,
    inserterPanel,
  };
};
