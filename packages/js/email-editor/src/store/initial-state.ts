import { mainSidebarDocumentTab } from './constants';
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
			activeTab: mainSidebarDocumentTab,
		},
		postId,
		editorSettings: getEditorSettings(),
		theme: getEditorTheme(),
		styles: {
			globalStylesPostId: window.MailPoetEmailEditor.user_theme_post_id,
		},
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
		personalizationTags: {
			list: [],
			isFetching: false,
		},
	};
}
