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
    case 'CHANGE_PREVIEW_DEVICE_TYPE':
      return {
        ...state,
        previewDeviceType: action.previewDeviceType,
      };
    default:
      return state;
  }
}
