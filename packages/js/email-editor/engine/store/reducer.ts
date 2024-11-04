import { State } from './types';

export function reducer(state: State, action): State {
  switch (action.type) {
    case 'TOGGLE_INSERTER_SIDEBAR':
      return {
        ...state,
        inserterSidebar: {
          ...state.inserterSidebar,
          isOpened: !state.inserterSidebar.isOpened,
        },
        listviewSidebar: {
          ...state.listviewSidebar,
          isOpened: false,
        },
      };
    case 'TOGGLE_LISTVIEW_SIDEBAR':
      return {
        ...state,
        inserterSidebar: {
          ...state.inserterSidebar,
          isOpened: false,
        },
        listviewSidebar: {
          ...state.listviewSidebar,
          isOpened: !state.listviewSidebar.isOpened,
        },
      };
    case 'CHANGE_PREVIEW_STATE':
      return {
        ...state,
        preview: { ...state.preview, ...action.state },
      };
    case 'TOGGLE_SETTINGS_SIDEBAR_ACTIVE_TAB':
      return {
        ...state,
        settingsSidebar: {
          ...state.settingsSidebar,
          activeTab: action.state.activeTab,
        },
      };
    default:
      return state;
  }
}
