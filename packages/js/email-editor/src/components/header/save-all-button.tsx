import { useRef } from '@wordpress/element';
import { Button, Dropdown } from '@wordpress/components';
import {
	// @ts-expect-error Our current version of packages doesn't have EntitiesSavedStates export
	EntitiesSavedStates,
} from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { storeName } from '../../store';

export function SaveAllButton() {
	const { isSaving } = useSelect(
		( select ) => ( {
			isSaving: select( storeName ).isSaving(),
		} ),
		[]
	);

	const buttonRef = useRef( null );

	let label = __( 'Save', 'mailpoet' );
	if ( isSaving ) {
		label = __( 'Saving', 'mailpoet' );
	}

	return (
		<div ref={ buttonRef }>
			<Dropdown
				popoverProps={ {
					placement: 'bottom',
					anchor: buttonRef.current,
				} }
				contentClassName="mailpoet-email-editor-save-button__dropdown"
				renderToggle={ ( { onToggle } ) => (
					<Button
						onClick={ onToggle }
						variant="primary"
						disabled={ isSaving }
					>
						{ label }
					</Button>
				) }
				renderContent={ ( { onToggle } ) => (
					<EntitiesSavedStates close={ onToggle } />
				) }
			/>
		</div>
	);
}
