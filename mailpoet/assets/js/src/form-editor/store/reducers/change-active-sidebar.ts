import { CHANGE_ACTIVE_SIDEBAR } from 'form-editor/store/actions';

export const changeActiveSidebar = (state, action: CHANGE_ACTIVE_SIDEBAR) => ({
  ...state,
  sidebar: {
    ...state.sidebar,
    activeSidebar: action.sidebar,
  },
});
