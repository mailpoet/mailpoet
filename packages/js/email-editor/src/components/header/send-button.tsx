/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import {
	// @ts-expect-error No types available for useEntitiesSavedStatesIsDirty
	useEntitiesSavedStatesIsDirty,
} from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { MailPoetEmailData, storeName } from '../../store';
import { useEditorMode } from '../../hooks';
import { recordEvent } from '../../events';

export function SendButton( { validateContent, isContentInvalid } ) {
	const [ mailpoetEmail ] = useEntityProp(
		'postType',
		'mailpoet_email',
		'mailpoet_data'
	);

	const { isDirty } = useEntitiesSavedStatesIsDirty();

	const { hasEmptyContent, isEmailSent } = useSelect(
		( select ) => ( {
			hasEmptyContent: select( storeName ).hasEmptyContent(),
			isEmailSent: select( storeName ).isEmailSent(),
		} ),
		[]
	);

	const [ editorMode ] = useEditorMode();

	const isDisabled =
		editorMode === 'template' ||
		hasEmptyContent ||
		isEmailSent ||
		isContentInvalid ||
		isDirty;

	const mailpoetEmailData: MailPoetEmailData = mailpoetEmail;
	return (
		<Button
			variant="primary"
			onClick={ () => {
				recordEvent( 'header_send_button_clicked' );
				if ( validateContent() ) {
					window.location.href = `admin.php?page=mailpoet-newsletters#/send/${ mailpoetEmailData.id }`;
				}
			} }
			disabled={ isDisabled }
		>
			{ __( 'Send', 'mailpoet' ) }
		</Button>
	);
}
