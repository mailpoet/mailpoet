import { mainSidebarEmailTab } from './constants';
import { State } from './types';
import {
	getEditorSettings,
	getCdnUrl,
	isPremiumPluginActive,
	getEditorTheme,
	getUrls,
} from './settings';

export function getInitialState(): State {
	const searchParams = new URLSearchParams( window.location.search );
	const postId = parseInt( searchParams.get( 'post' ), 10 );
	return {
		inserterSidebar: {
			isOpened: false,
		},
		listviewSidebar: {
			isOpened: false,
		},
		settingsSidebar: {
			activeTab: mainSidebarEmailTab,
		},
		postId,
		editorSettings: getEditorSettings(),
		theme: getEditorTheme(),
		autosaveInterval: 60,
		cdnUrl: getCdnUrl(),
		isPremiumPluginActive: isPremiumPluginActive(),
		urls: getUrls(),
		preview: {
			deviceType: 'Desktop',
			toEmail: window.MailPoetEmailEditor.current_wp_user_email,
			isModalOpened: false,
			isSendingPreviewEmail: false,
			sendingPreviewStatus: null,
		},
	};
}
