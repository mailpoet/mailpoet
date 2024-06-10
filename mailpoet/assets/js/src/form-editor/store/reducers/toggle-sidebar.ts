import { ToggleAction, ToggleBlockInserterAction } from '../actions-types';
import { BlockInsertionPoint } from '../state-types';

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
    isListViewOpened: false,
  };
};

export const toggleListView = (state) => ({
  ...state,
  isListViewOpened: !state.isListViewOpened,
  inserterPanel: null,
});
