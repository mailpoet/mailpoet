import { CHANGE_ACTIVE_SIDEBAR } from 'form_editor/store/actions';

export const changeActiveSidebar = (state, action: CHANGE_ACTIVE_SIDEBAR) => ({
  ...state,
  sidebar: {
    ...state.sidebar,
    activeSidebar: action.sidebar,
  },
});
