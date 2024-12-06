import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { PersonalizationTag, storeName } from '../../store';
import { useDispatch, useSelect } from '@wordpress/data';

const CategorySection = ( {
	groupedTags,
	activeCategory,
}: {
	groupedTags: Record< string, PersonalizationTag[] >;
	activeCategory: string | null;
} ) => {
	const { togglePersonalizationTagsModal } = useDispatch( storeName );

	const { onInsert } = useSelect(
		( select ) => select( storeName ).getPersonalizationTagsState(),
		[]
	);

	const categoriesToRender: [ string, PersonalizationTag[] ][] =
		activeCategory === null
			? Object.entries( groupedTags ) // Render all categories
			: [ [ activeCategory, groupedTags[ activeCategory ] || [] ] ]; // Render only one selected category

	return (
		<>
			{ categoriesToRender.map(
				( [ category, items ]: [ string, PersonalizationTag[] ] ) => (
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
									<Button
										variant="link"
										onClick={ () => {
											if ( onInsert ) {
												onInsert( item.token );
											}
											togglePersonalizationTagsModal(
												false
											);
										} }
									>
										{ __( 'Insert' ) }
									</Button>
								</div>
							) ) }
						</div>
					</div>
				)
			) }
		</>
	);
};

export { CategorySection };
