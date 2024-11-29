import { Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../store';
import { useDispatch, useSelect } from '@wordpress/data';

const PersonalizationTagsModal = () => {
	const { togglePersonalizationTagsModal } = useDispatch( storeName );

	const { isModalOpened } = useSelect(
		( select ) => select( storeName ).getPersonalizationTagsState(),
		[]
	);

	if ( ! isModalOpened ) {
		return null;
	}
	return (
		<Modal
			size="medium"
			title={ __( 'Personalization Tags', 'mailpoet' ) }
			onRequestClose={ () => togglePersonalizationTagsModal( false ) }
		>
			<p>There will be a list of personalization tags here.</p>
		</Modal>
	);
};

export { PersonalizationTagsModal };
