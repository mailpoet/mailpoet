import { remove } from 'lodash';

const getRequiredAction = (openedPanels, panelId, toggleTo) => {
  const isPanelOpened = openedPanels.includes(panelId);
  let requestedToggleState = toggleTo;
  if (requestedToggleState === undefined) {
    requestedToggleState = isPanelOpened ? 'closed' : 'opened';
  }
  if (isPanelOpened && requestedToggleState === 'closed') {
    return 'close';
  }
  if (!isPanelOpened && requestedToggleState === 'opened') {
    return 'open';
  }
  return null;
};

/**
 * @param {object} state
 * @param {{toggleTo: string|undefined, id: string, type: string}} action
 * @return {object} Modified state object
 */
export default (state, action) => {
  if (action.toggleTo !== undefined && !['opened', 'closed'].includes(action.toggleTo)) {
    throw new Error(`Unexpected toggleTo value "${action.toggleTo}"`);
  }
  const openedPanels = [...state.sidebar.openedPanels];
  const requiredAction = getRequiredAction(openedPanels, action.id, action.toggleTo);
  if (requiredAction === 'open') {
    openedPanels.push(action.id);
  } else if (requiredAction === 'close') {
    remove(openedPanels, (item) => item === action.id);
  }
  return {
    ...state,
    sidebar: {
      ...state.sidebar,
      openedPanels,
    },
  };
};
