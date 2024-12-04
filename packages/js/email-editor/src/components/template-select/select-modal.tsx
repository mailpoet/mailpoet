import { useState } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import { dispatch } from '@wordpress/data';
import { Modal, Button, Flex, FlexItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { usePreviewTemplates } from '../../hooks';
import { storeName, TemplatePreview } from '../../store';
import { TemplateList } from './template-list';
import { TemplateCategoriesListSidebar } from './template-categories-list-sidebar';

const BLANK_TEMPLATE = 'email-general';

function SelectTemplateBody( {
	initialCategory,
	templateCategories,
	templates,
	handleTemplateSelection,
} ) {
	const [ selectedCategory, setSelectedCategory ] = useState(
		initialCategory?.name
	);

	return (
		<div className="block-editor-block-patterns-explorer">
			<TemplateCategoriesListSidebar
				templateCategories={ templateCategories }
				selectedCategory={ selectedCategory }
				onClickCategory={ setSelectedCategory }
			/>

			<TemplateList
				templates={ templates }
				onTemplateSelection={ handleTemplateSelection }
				selectedCategory={ selectedCategory }
			/>
		</div>
	);
}

export function SelectTemplateModal( {
	onSelectCallback,
	closeCallback = null,
	previewContent = '',
} ) {
	const [ templates ] = usePreviewTemplates( previewContent );

	const hasTemplates = templates?.length > 0;

	const handleTemplateSelection = ( template: TemplatePreview ) => {
		// When we provide previewContent, we don't want to reset the blocks
		if ( ! previewContent ) {
			void dispatch( editorStore ).resetEditorBlocks(
				template.emailParsed
			);
		}

		void dispatch( storeName ).setTemplateToPost(
			template.slug,
			template.template.mailpoet_email_theme ?? {}
		);
		onSelectCallback();
	};

	const handleCloseWithoutSelection = () => {
		const blankTemplate = templates.find(
			( template ) => template.slug === BLANK_TEMPLATE
		) as unknown as TemplatePreview;
		if ( ! blankTemplate ) {
			return;
		} // Prevent close if blank template is still not loaded
		handleTemplateSelection( blankTemplate );
	};

	const dummyTemplateCategories = [
		{
			name: 'recent',
			label: 'Recent',
		},
		{
			name: 'basic',
			label: 'Basic',
		},
	];

	return (
		<Modal
			title={ __( 'Select a template', 'mailpoet' ) }
			onRequestClose={ () =>
				closeCallback ? closeCallback() : handleCloseWithoutSelection()
			}
			isFullScreen
		>
			<SelectTemplateBody
				initialCategory={ dummyTemplateCategories[ 0 ] }
				templateCategories={ dummyTemplateCategories }
				templates={ templates }
				handleTemplateSelection={ handleTemplateSelection }
			/>

			<Flex justify="flex-end">
				<FlexItem>
					<Button
						variant="tertiary"
						className="email-editor-start_from_scratch_button"
						onClick={ () => handleCloseWithoutSelection() }
						isBusy={ ! hasTemplates }
					>
						{ __( 'Start from scratch', 'mailpoet' ) }
					</Button>
				</FlexItem>
			</Flex>
		</Modal>
	);
}
