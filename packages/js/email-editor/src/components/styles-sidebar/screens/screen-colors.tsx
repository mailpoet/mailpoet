/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * WordPress private dependencies
 */
import { StylesColorPanel } from '../../../private-apis';

/**
 * Internal dependencies
 */
import ScreenHeader from './screen-header';
import { useEmailStyles } from '../../../hooks';
import { storeName } from '../../../store';

export function ScreenColors(): JSX.Element {
	const { styles, defaultStyles, updateStyles } = useEmailStyles();
	const theme = useSelect( ( select ) => select( storeName ).getTheme(), [] );

	return (
		<>
			<ScreenHeader
				title={ __( 'Colors', 'mailpoet' ) }
				description={ __(
					'Manage palettes and the default color of different global elements.',
					'mailpoet'
				) }
			/>
			<StylesColorPanel
				value={ styles }
				inheritValue={ defaultStyles }
				onChange={ updateStyles }
				settings={ theme?.settings }
				panelId="colors"
			/>
		</>
	);
}
