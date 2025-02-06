/**
 * External dependencies
 */
import { useMemo, useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import deepmerge from 'deepmerge';
import fastDeepEqual from 'fast-deep-equal/es6';

/**
 * Internal dependencies
 */
import { EmailStyles, storeName } from '../store';
import { useUserTheme } from './use-user-theme';
import { useGlobalStylesOutputWithConfig } from '../private-apis';

export function useEmailCss() {
	const stylesRef = useRef( [] );
	const { userTheme } = useUserTheme();
	const { editorTheme } = useSelect(
		( select ) => ( {
			editorTheme: select( storeName ).getTheme(),
		} ),
		[]
	);

	// @ts-expect-error Todo add styles for editor settings
	const baseStyles = window.MailPoetEmailEditor.editor_settings.styles;

	const mergedConfig = useMemo(
		() =>
			deepmerge.all( [
				{},
				editorTheme || {},
				userTheme || {},
			] ) as EmailStyles,
		[ editorTheme, userTheme ]
	);

	const [ styles ] = useGlobalStylesOutputWithConfig( mergedConfig );
	const finalStyles = [ ...baseStyles, ...( styles ? styles : [] ) ];
	if ( ! fastDeepEqual( stylesRef.current, finalStyles ) ) {
		stylesRef.current = finalStyles;
	}

	// eslint-disable-next-line @typescript-eslint/no-unsafe-return
	return [ stylesRef.current ];
}
