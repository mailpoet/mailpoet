import { State } from './types';

export function reducer( state: State, action ): State {
	switch ( action.type ) {
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
