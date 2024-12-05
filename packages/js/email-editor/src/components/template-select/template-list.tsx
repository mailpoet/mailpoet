import { useMemo, memo } from '@wordpress/element';
// @ts-expect-error No types available for this component
import { BlockPreview } from '@wordpress/block-editor';
import {
	__experimentalHStack as HStack, // eslint-disable-line
	Notice,
} from '@wordpress/components';
import { Icon, info, blockDefault } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { Async } from './async';
import { TemplateCategory, TemplatePreview } from '../../store';

type Props = {
	templates: TemplatePreview[];
	onTemplateSelection: ( template: TemplatePreview ) => void;
	selectedCategory?: TemplateCategory;
};

function TemplateNoResults() {
	return (
		<div className="block-editor-inserter__no-results">
			<Icon
				className="block-editor-inserter__no-results-icon"
				icon={ blockDefault }
			/>
			<p>{ __( 'No recent templates.' ) }</p>
			<p>
				{ __(
					'Your recent creations will appear here as soon as you begin.'
				) }
			</p>
		</div>
	);
}

function TemplateListBox( {
	templates,
	onTemplateSelection,
	selectedCategory,
}: Props ) {
	if ( selectedCategory === 'recent' && templates.length === 0 ) {
		return <TemplateNoResults />;
	}

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
										css: template.template?.email_theme_css,
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

const compareProps = ( prev, next ) =>
	prev.templates.length === next.templates.length &&
	prev.selectedCategory === next.selectedCategory;

const MemorizedTemplateListBox = memo( TemplateListBox, compareProps );

export function TemplateList( {
	templates,
	onTemplateSelection,
	selectedCategory,
}: Props ) {
	const filteredTemplates = useMemo(
		() =>
			templates.filter(
				( template ) => template.category === selectedCategory
			),
		[ selectedCategory, templates ]
	);

	return (
		<div className="block-editor-block-patterns-explorer__list">
			{ selectedCategory === 'recent' && (
				<Notice isDismissible={ false }>
					<HStack spacing={ 1 } expanded={ false } justify="start">
						<Icon icon={ info } />
						<p>
							{ __(
								'Templates created on the legacy editor will not appear here.',
								'mailpoet'
							) }
						</p>
					</HStack>
				</Notice>
			) }

			<MemorizedTemplateListBox
				templates={ filteredTemplates }
				onTemplateSelection={ onTemplateSelection }
				selectedCategory={ selectedCategory }
			/>
		</div>
	);
}
