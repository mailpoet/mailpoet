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
import { useEmailTheme } from './use-email-theme';

export function useEmailCss() {
	const { templateTheme } = useEmailTheme();
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
				templateTheme || {},
			] ) as EmailStyles,
		[ editorTheme, templateTheme ]
	);

	const [ styles ] = useGlobalStylesOutputWithConfig( mergedConfig );

	// eslint-disable-next-line @typescript-eslint/no-unsafe-return
	return [ styles ];
}
