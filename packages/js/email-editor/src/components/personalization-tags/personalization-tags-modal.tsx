import { Modal, SearchControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { PersonalizationTag, storeName } from '../../store';
import { useDispatch, useSelect } from '@wordpress/data';
import { external, Icon } from '@wordpress/icons';
import './index.scss';
import { useState } from '@wordpress/element';
import { CategoryMenu } from './category-menu';
import { CategorySection } from './category-section';

const PersonalizationTagsModal = () => {
	const [ activeCategory, setActiveCategory ] = useState( null );
	const [ searchQuery, setSearchQuery ] = useState( '' );

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
			const { category, name, token } = item;

			if (
				! searchQuery ||
				name.toLowerCase().includes( searchQuery.toLowerCase() ) ||
				token.toLowerCase().includes( searchQuery.toLowerCase() )
			) {
				if ( ! groups[ category ] ) {
					groups[ category ] = [];
				}
				groups[ category ].push( item );
			}
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
				{ __(
					'Insert shortcodes to dynamically fill in information and personalize your emails. Learn more',
					'mailpoet'
				) }{ ' ' }
				<Icon icon={ external } size={ 16 } />
			</p>
			<SearchControl onChange={ setSearchQuery } value={ searchQuery } />
			<CategoryMenu
				groupedTags={ groupedTags }
				activeCategory={ activeCategory }
				onCategorySelect={ setActiveCategory }
			/>
			<CategorySection
				groupedTags={ groupedTags }
				activeCategory={ activeCategory }
			/>
		</Modal>
	);
};

export { PersonalizationTagsModal };
