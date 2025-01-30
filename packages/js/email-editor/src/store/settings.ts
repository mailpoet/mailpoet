/**
 * Internal dependencies
 */
import { EmailEditorSettings, EmailTheme, EmailEditorUrls } from './types';

export function getEditorSettings(): EmailEditorSettings {
	return window.MailPoetEmailEditor.editor_settings as EmailEditorSettings;
}

export function getEditorTheme(): EmailTheme {
	return window.MailPoetEmailEditor.editor_theme as EmailTheme;
}

export function getUrls(): EmailEditorUrls {
	return window.MailPoetEmailEditor.urls as EmailEditorUrls;
}
