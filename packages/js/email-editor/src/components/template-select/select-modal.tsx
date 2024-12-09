import { useState, useEffect, memo } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import { dispatch } from '@wordpress/data';
import { Modal, Button, Flex, FlexItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { usePreviewTemplates } from '../../hooks';
import {
	EmailEditorPostType,
	storeName,
	TemplateCategory,
	TemplatePreview,
} from '../../store';
import { TemplateList } from './template-list';
import { TemplateCategoriesListSidebar } from './template-categories-list-sidebar';

const BLANK_TEMPLATE = 'email-general';

const TemplateCategories: Array< { name: TemplateCategory; label: string } > = [
	{
		name: 'recent',
		label: 'Recent',
	},
	{
		name: 'basic',
		label: 'Basic',
	},
];

function SelectTemplateBody( {
	hasEmailPosts,
	templates,
	handleTemplateSelection,
} ) {
	const [ selectedCategory, setSelectedCategory ] = useState(
		TemplateCategories[ 1 ].name // Show the “Basic” category by default
	);

	useEffect( () => {
		setTimeout( () => {
			if ( hasEmailPosts ) {
				setSelectedCategory( TemplateCategories[ 0 ].name );
			}
		}, 1000 ); // using setTimeout to ensure the template styles are available before block preview
	}, [ hasEmailPosts ] );

	return (
		<div className="block-editor-block-patterns-explorer">
			<TemplateCategoriesListSidebar
				templateCategories={ TemplateCategories }
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

const MemorizedSelectTemplateBody = memo( SelectTemplateBody );

export function SelectTemplateModal( {
	onSelectCallback,
	closeCallback = null,
	previewContent = '',
} ) {
	const [ templates, emailPosts, hasEmailPosts ] =
		usePreviewTemplates( previewContent );

	const hasTemplates = templates?.length > 0;

	const handleTemplateSelection = ( template: TemplatePreview ) => {
		const templateIsPostContent = template.type === 'mailpoet_email';

		const postContent = template.template as unknown as EmailEditorPostType;

		// When we provide previewContent, we don't want to reset the blocks
		if ( ! previewContent ) {
			void dispatch( editorStore ).resetEditorBlocks(
				template.emailParsed
			);
		}

		void dispatch( storeName ).setTemplateToPost(
			templateIsPostContent ? postContent.template : template.slug,
			templateIsPostContent
				? template?.template?.mailpoet_email_theme || {}
				: template.template.mailpoet_email_theme ?? {}
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

	return (
		<Modal
			title={ __( 'Start with an email preset', 'mailpoet' ) }
			onRequestClose={ () =>
				closeCallback ? closeCallback() : handleCloseWithoutSelection()
			}
			isFullScreen
		>
			<MemorizedSelectTemplateBody
				hasEmailPosts={ hasEmailPosts }
				templates={ [ ...templates, ...emailPosts ] }
				handleTemplateSelection={ handleTemplateSelection }
			/>

			<Flex className="email-editor-modal-footer" justify="flex-end">
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
