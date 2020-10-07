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
  let toggleTo;
  if (action.toggleTo === true) toggleTo = 'opened';
  if (action.toggleTo === false) toggleTo = 'closed';
  const openedPanels = [...state.sidebar.openedPanels];
  const requiredAction = getRequiredAction(openedPanels, action.id, toggleTo);
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
