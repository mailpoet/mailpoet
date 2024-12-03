import { useMemo } from '@wordpress/element';
// @ts-expect-error No types available for this component
import { BlockPreview } from '@wordpress/block-editor';
import {
	__experimentalHStack as HStack, // eslint-disable-line
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Async } from './async';

function TemplateListBox( { templates, onTemplateSelection } ) {
	return (
		<div className="block-editor-block-patterns-list" role="listbox">
			{ templates.map( ( template ) => (
				<div
					key={ template.slug }
					className="block-editor-block-patterns-list__list-item"
				>
					<div
						className="block-editor-block-patterns-list__item"
						role="button"
						tabIndex={ 0 }
						onClick={ () => {
							onTemplateSelection( template );
						} }
						onKeyPress={ ( event ) => {
							if ( event.key === 'Enter' || event.key === ' ' ) {
								onTemplateSelection( template );
							}
						} }
					>
						<Async
							placeholder={
								<p>
									{ __( 'rendering template', 'mailpoet' ) }
								</p>
							}
						>
							<BlockPreview
								blocks={ template.previewContentParsed }
								viewportWidth={ 900 }
								minHeight={ 300 }
								additionalStyles={ [
									{
										css: template.template.email_theme_css,
									},
								] }
							/>

							<HStack className="block-editor-patterns__pattern-details">
								<div className="block-editor-block-patterns-list__item-title">
									{ template.template.title.rendered }
								</div>
							</HStack>
						</Async>
					</div>
				</div>
			) ) }
		</div>
	);
}

export function TemplateList( {
	templates,
	onTemplateSelection,
	selectedCategory,
} ) {
	const filteredTemplates = useMemo(
		() =>
			templates.filter(
				( template ) => template.category === selectedCategory
			),
		[ selectedCategory, templates ]
	);

	return (
		<div className="block-editor-block-patterns-explorer__list">
			<TemplateListBox
				templates={ filteredTemplates }
				onTemplateSelection={ onTemplateSelection }
			/>
		</div>
	);
}
