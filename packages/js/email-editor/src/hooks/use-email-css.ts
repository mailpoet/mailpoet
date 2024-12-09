/**
 * WordPress dependencies
 */
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * WordPress private dependencies
 */
import { useGlobalStylesOutputWithConfig } from '../private-apis';

/**
 * Internal dependencies
 */
import deepmerge from 'deepmerge';
import { EmailStyles, storeName } from '../store';
import { useUserTheme } from './use-user-theme';

export function useEmailCss() {
	const { userTheme } = useUserTheme();
	const { editorTheme } = useSelect(
		( select ) => ( {
			editorTheme: select( storeName ).getTheme(),
		} ),
		[]
	);

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

	// eslint-disable-next-line @typescript-eslint/no-unsafe-return
	return [ styles || [] ];
}
