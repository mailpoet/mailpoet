/**
 * External dependencies
 */
import { Button, Modal, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './index.scss';

const LinkModal = ( { onInsert, isOpened, closeCallback, tag } ) => {
	const [ linkText, setLinkText ] = useState( __( 'Link', 'mailpoet' ) );
	if ( ! isOpened ) {
		return null;
	}

	return (
		<Modal
			size="small"
			title={ __( 'Insert Link', 'mailpoet' ) }
			onRequestClose={ closeCallback }
			className="mailpoet-personalization-tags-modal"
		>
			<TextControl
				label={ __( 'Link Text', 'mailpoet' ) }
				value={ linkText }
				onChange={ setLinkText }
			/>
			<Button
				isPrimary
				onClick={ () => {
					if ( onInsert ) {
						onInsert( tag.token, linkText );
					}
				} }
			>
				{ __( 'Insert', 'mailpoet' ) }
			</Button>
		</Modal>
	);
};

export { LinkModal };
