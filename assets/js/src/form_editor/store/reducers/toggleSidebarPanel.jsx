import { remove } from 'lodash';

export default (state, action) => {
  const openedPanels = [...state.sidebar.openedPanels];
  const isOpenedCurrent = openedPanels.includes(action.id);
  const isOpenedFinal = action.isOpened !== undefined ? action.isOpened : !isOpenedCurrent;
  if (isOpenedFinal && !isOpenedCurrent) {
    openedPanels.push(action.id);
  }
  if (!isOpenedFinal && isOpenedCurrent) {
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
