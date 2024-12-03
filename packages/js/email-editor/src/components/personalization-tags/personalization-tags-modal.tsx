import { Modal, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { PersonalizationTag, storeName } from '../../store';
import { useDispatch, useSelect } from '@wordpress/data';
import { external, Icon } from '@wordpress/icons';
import './index.scss';

const PersonalizationTagsModal = () => {
	const { togglePersonalizationTagsModal } = useDispatch( storeName );

	const { isModalOpened, list } = useSelect(
		( select ) => select( storeName ).getPersonalizationTagsState(),
		[]
	);

	if ( ! isModalOpened ) {
		return null;
	}

	const groupedTags: Record< string, PersonalizationTag[] > = list.reduce(
		( groups, item ) => {
			const { category } = item;
			if ( ! groups[ category ] ) {
				groups[ category ] = [];
			}
			groups[ category ].push( item );
			return groups;
		},
		{} as Record< string, PersonalizationTag[] >
	);

	return (
		<Modal
			size="medium"
			title={ __( 'Personalization Tags', 'mailpoet' ) }
			onRequestClose={ () => togglePersonalizationTagsModal( false ) }
			className="mailpoet-personalization-tags-modal"
		>
			<p>
				Insert shortcodes to dynamically fill in information and
				personalize your emails. Learn more{ ' ' }
				<Icon icon={ external } size={ 16 } />
			</p>
			{ Object.entries( groupedTags ).map( ( [ category, items ] ) => (
				<div key={ category }>
					<div className="mailpoet-personalization-tags-modal__category">
						{ category }
					</div>
					<div className="mailpoet-personalization-tags-modal__category-group">
						{ items.map( ( item ) => (
							<div
								className="mailpoet-personalization-tags-modal__category-group-item"
								key={ item.token }
							>
								<div className="mailpoet-personalization-tags-modal__item-text">
									<strong>{ item.name }</strong>
									{ item.token }
								</div>
								<Button variant="link">
									{ __( 'Insert' ) }
								</Button>
							</div>
						) ) }
					</div>
				</div>
			) ) }
		</Modal>
	);
};

export { PersonalizationTagsModal };
