import * as React from '@wordpress/element';
import { Modal, Button, MenuGroup, MenuItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { PersonalizationTag, storeName } from '../../store';
import { useDispatch, useSelect } from '@wordpress/data';
import { external, Icon } from '@wordpress/icons';
import './index.scss';
import { useState } from '@wordpress/element';

const PersonalizationTagsModal = () => {
	const [ activeCategory, setActiveCategory ] = useState( null );

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

	const getMenuItemClass = ( category ) =>
		category === activeCategory
			? 'mailpoet-personalization-tags-modal__menu-item-active'
			: '';

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
			<MenuGroup className="mailpoet-personalization-tags-modal__menu">
				<MenuItem
					onClick={ () => setActiveCategory( null ) }
					className={ `${ getMenuItemClass( null ) }` }
				>
					{ __( 'All' ) }
				</MenuItem>
				<div
					className="mailpoet-personalization-tags-modal__menu-separator"
					aria-hidden="true"
				></div>
				{ Object.entries( groupedTags ).map(
					( [ category ], index, array ) => (
						<React.Fragment key={ category }>
							<MenuItem
								onClick={ () => setActiveCategory( category ) }
								className={ `${ getMenuItemClass(
									category
								) }` }
							>
								{ category }
							</MenuItem>
							{ index < array.length - 1 && (
								<div
									className="mailpoet-personalization-tags-modal__menu-separator"
									aria-hidden="true"
								></div>
							) }
						</React.Fragment>
					)
				) }
			</MenuGroup>
			{ activeCategory === null ? (
				// Render all categories
				Object.entries( groupedTags ).map( ( [ category, items ] ) => (
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
				) )
			) : (
				// Render selected category
				<div>
					<div className="mailpoet-personalization-tags-modal__category">
						{ activeCategory }
					</div>
					<div className="mailpoet-personalization-tags-modal__category-group">
						{ groupedTags[ activeCategory ]?.map( ( item ) => (
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
			) }
		</Modal>
	);
};

export { PersonalizationTagsModal };
