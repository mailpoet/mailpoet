import { ExternalLink, Modal, SearchControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { PersonalizationTag, storeName } from '../../store';
import { useSelect } from '@wordpress/data';
import './index.scss';
import { useState } from '@wordpress/element';
import { CategoryMenu } from './category-menu';
import { CategorySection } from './category-section';
import { LinkModal } from './link-modal';

const PersonalizationTagsModal = ( {
	onInsert,
	isOpened,
	closeCallback,
	canInsertLink = false,
} ) => {
	const [ activeCategory, setActiveCategory ] = useState( null );
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ selectedTag, setSelectedTag ] = useState( null );
	const [ isLinkModalOpened, setIsLinkModalOpened ] = useState( false );

	const list = useSelect(
		( select ) => select( storeName ).getPersonalizationTagsList(),
		[]
	);

	if ( isLinkModalOpened ) {
		return (
			<LinkModal
				onInsert={ ( tag, linkText ) => {
					onInsert( tag, linkText );
					setIsLinkModalOpened( false );
				} }
				isOpened={ isLinkModalOpened }
				closeCallback={ () => setIsLinkModalOpened( false ) }
				tag={ selectedTag }
			/>
		);
	}

	if ( ! isOpened ) {
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
			onRequestClose={ closeCallback }
			className="mailpoet-personalization-tags-modal"
		>
			<p>
				{ __(
					'Insert personalization tags to dynamically fill in information and personalize your emails.',
					'mailpoet'
				) }{ ' ' }
				<ExternalLink href="https://kb.mailpoet.com/article/435-a-guide-to-personalisation-tags-for-tailored-newsletters#list">
					{ __( 'Learn more', 'mailpoet' ) }
				</ExternalLink>
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
				onInsert={ onInsert }
				closeCallback={ closeCallback }
				canInsertLink={ canInsertLink }
				openLinkModal={ ( tag ) => {
					setSelectedTag( tag );
					setIsLinkModalOpened( true );
				} }
			/>
		</Modal>
	);
};

export { PersonalizationTagsModal };
