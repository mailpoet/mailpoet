import { __ } from '@wordpress/i18n';
import { useCallback, useMemo } from '@wordpress/element';
import { dispatch, useSelect, subscribe } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import { storeName as emailEditorStore } from '../store';
import { store as coreDataStore } from '@wordpress/core-data';
import { useShallowEqual } from './use-shallow-equal';
import { useValidationNotices } from './use-validation-notices';

export type ContentValidationData = {
	isValid: boolean;
	validateContent: () => boolean;
};

export const useContentValidation = (): ContentValidationData => {
	const { contentBlockId, hasFooter } = useSelect( ( select ) => {
		const allBlocks = select( blockEditorStore ).getBlocks();
		const noBodyBlocks = allBlocks.filter(
			( block ) =>
				block.name !== 'mailpoet/powered-by-mailpoet' &&
				block.name !== 'core/post-content'
		);
		// @ts-expect-error getBlocksByName is not defined in types
		const blocks = select( blockEditorStore ).getBlocksByName(
			'core/post-content'
		) as string[] | undefined;
		return {
			contentBlockId: blocks?.[ 0 ],
			hasFooter: noBodyBlocks.length > 0,
		};
	} );

	const { addValidationNotice, hasValidationNotice, removeValidationNotice } =
		useValidationNotices();
	const { editedContent, editedTemplateContent, defaultTemplateId } =
		useSelect( ( mapSelect ) => ( {
			editedContent:
				mapSelect( emailEditorStore ).getEditedEmailContent(),
			editedTemplateContent:
				mapSelect( emailEditorStore ).getTemplateContent(),
			defaultTemplateId: mapSelect( coreDataStore ).getDefaultTemplateId(
				{
					slug: 'email-general',
				}
			),
		} ) );

	const content = useShallowEqual( editedContent );
	const templateContent = useShallowEqual( editedTemplateContent );

	const contentLink = `<a href='[link:subscription_unsubscribe_url]'>${ __(
		'Unsubscribe',
		'mailpoet'
	) }</a> | <a href='[link:subscription_manage_url]'>${ __(
		'Manage subscription',
		'mailpoet'
	) }</a>`;

	const rules = useMemo( () => {
		const linksParagraphBlock = createBlock( 'core/paragraph', {
			className: 'has-small-font-size',
			content: contentLink,
		} );

		return [
			{
				id: 'missing-unsubscribe-link',
				test: ( emailContent ) =>
					! emailContent.includes(
						'[link:subscription_unsubscribe_url]'
					),
				message: __(
					'All emails must include an "Unsubscribe" link.',
					'mailpoet'
				),
				actions: [
					{
						label: __( 'Insert link', 'mailpoet' ),
						onClick: () => {
							if ( ! hasFooter ) {
								void dispatch( blockEditorStore ).insertBlock(
									linksParagraphBlock,
									undefined,
									contentBlockId
								);
							} else {
								void dispatch( coreDataStore ).editEntityRecord(
									'postType',
									'wp_template',
									defaultTemplateId,
									{
										content: `
                      ${ editedTemplateContent }
                      <!-- wp:paragraph -->
                      ${ contentLink }
                      <!-- /wp:paragraph -->
                    `,
									}
								);
							}
						},
					},
				],
			},
		];
	}, [
		contentBlockId,
		hasFooter,
		contentLink,
		defaultTemplateId,
		editedTemplateContent,
	] );

	const validateContent = useCallback( (): boolean => {
		let isValid = true;
		rules.forEach( ( { id, test, message, actions } ) => {
			// Check both content and template content for the rule.
			if ( test( content + templateContent ) ) {
				addValidationNotice( id, message, actions );
				isValid = false;
			} else if ( hasValidationNotice( id ) ) {
				removeValidationNotice( id );
			}
		} );
		return isValid;
	}, [
		content,
		templateContent,
		addValidationNotice,
		removeValidationNotice,
		hasValidationNotice,
		rules,
	] );

	// Subscribe to updates so notices can be dismissed once resolved.
	subscribe( () => {
		if ( ! hasValidationNotice() ) {
			return;
		}
		validateContent();
	}, emailEditorStore );

	return {
		isValid: hasValidationNotice(),
		validateContent,
	};
};
