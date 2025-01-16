/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { __experimentalConfirmDialog as ConfirmDialog } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { recordEvent, recordEventOnce } from '../../../events'; // eslint-disable-line

/**
 * Component that:
 *
 * - Displays a 'Edit your template to edit this block' dialog when the user
 *   is focusing on editing email content and double clicks on a disabled
 *   template block.
 *
 *   @see https://github.com/WordPress/gutenberg/blob/c754c783a9004db678fcfebd9a21a22820f2115c/packages/editor/src/components/visual-editor/edit-template-blocks-notification.js
 *
 * @param {Object}                                 props
 * @param {import('react').RefObject<HTMLElement>} props.contentRef Ref to the block
 *                                                                  editor iframe canvas.
 */
export default function EditTemplateBlocksNotification( { contentRef } ) {
	const { onNavigateToEntityRecord, templateId } = useSelect( ( select ) => {
		// @ts-expect-error getCurrentTemplateId is missing in types.
		const { getEditorSettings, getCurrentTemplateId } =
			select( editorStore );

		return {
			// onNavigateToEntityRecord is missing in EditorSettings.
			// prettier-ignore
			onNavigateToEntityRecord: // @ts-expect-error onNavigateToEntityRecord is not typed on EditorSettings.
				getEditorSettings().onNavigateToEntityRecord,
			templateId: getCurrentTemplateId(),
		};
	}, [] );

	const [ isDialogOpen, setIsDialogOpen ] = useState( false );

	useEffect( () => {
		const handleDblClick = ( event ) => {
			if ( ! event.target.classList.contains( 'is-root-container' ) ) {
				return;
			}
			setIsDialogOpen( true );
			recordEventOnce( 'edit_template_blocks_notification_opened' );
		};

		const canvas = contentRef.current;
		canvas?.addEventListener( 'dblclick', handleDblClick );
		return () => {
			canvas?.removeEventListener( 'dblclick', handleDblClick );
		};
	}, [ contentRef ] );

	return (
		<ConfirmDialog
			isOpen={ isDialogOpen }
			confirmButtonText={ __( 'Edit template', 'mailpoet' ) }
			onConfirm={ () => {
				setIsDialogOpen( false );
				onNavigateToEntityRecord( {
					postId: templateId,
					postType: 'wp_template',
				} );
				recordEvent(
					'edit_template_blocks_notification_edit_template_button_clicked',
					{ templateId }
				);
			} }
			onCancel={ () => {
				setIsDialogOpen( false );
				recordEvent(
					'edit_template_blocks_notification_cancel_button_clicked',
					{ templateId }
				);
			} }
			size="medium"
		>
			{ __(
				'The block you’ve selected is part of a template that might be used in other emails. Are you sure you want to edit the template?',
				'mailpoet'
			) }
		</ConfirmDialog>
	);
}
