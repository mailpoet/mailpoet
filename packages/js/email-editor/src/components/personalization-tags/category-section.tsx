/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { PersonalizationTag } from '../../store';

const CategorySection = ( {
	groupedTags,
	activeCategory,
	onInsert,
	canInsertLink,
	closeCallback,
	openLinkModal,
}: {
	groupedTags: Record< string, PersonalizationTag[] >;
	activeCategory: string | null;
	onInsert: ( tag: string, isLink: boolean ) => void;
	canInsertLink: boolean;
	closeCallback: () => void;
	openLinkModal: ( tag: PersonalizationTag ) => void;
} ) => {
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
										{ item.valueToInsert }
									</div>
									<div
										style={ {
											display: 'flex',
											flexDirection: 'column',
											alignItems: 'flex-end',
										} }
									>
										<Button
											variant="link"
											onClick={ () => {
												if ( onInsert ) {
													onInsert(
														item.valueToInsert,
														false
													);
												}
											} }
										>
											{ __( 'Insert', 'mailpoet' ) }
										</Button>
										{ category ===
											__( 'Link', 'mailpoet' ) &&
											canInsertLink && (
												<>
													<Button
														variant="link"
														onClick={ () => {
															closeCallback();
															openLinkModal(
																item
															);
														} }
													>
														{ __(
															'Insert as link',
															'mailpoet'
														) }
													</Button>
												</>
											) }
									</div>
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
