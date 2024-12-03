import { PanelBody, Button } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { store as editorStore } from '@wordpress/editor';

import { storeName } from '../../store';

export function TemplatesPanel() {
	const { onNavigateToEntityRecord, template, hasHistory } = useSelect(
		( sel ) => {
			// eslint-disable-next-line @typescript-eslint/naming-convention
			const { getEditorSettings } = sel( editorStore );
			const editorSettings = getEditorSettings();
			return {
				onNavigateToEntityRecord:
					// @ts-expect-error onNavigateToEntityRecord type is not defined
					editorSettings.onNavigateToEntityRecord,
				// @ts-expect-error onNavigateToPreviousEntityRecord type is not defined
				hasHistory: !! editorSettings.onNavigateToPreviousEntityRecord,
				template: sel( storeName ).getEditedPostTemplate(),
			};
		},
		[]
	);

	const { revertAndSaveTemplate } = useDispatch( storeName );

	return (
		<PanelBody
			title={ __( 'Templates Experiment', 'mailpoet' ) }
			className="mailpoet-email-editor__settings-panel"
		>
			<p>
				Components from this Panel will be placed in different areas of
				the UI. They are place here in one place just to simplify the
				experiment.
			</p>
			<hr />
			<h3>Edit template toggle</h3>
			{ template && ! hasHistory && (
				<Button
					variant="primary"
					onClick={ () => {
						onNavigateToEntityRecord( {
							postId: template.id,
							postType: 'wp_template',
						} );
					} }
					disabled={ ! template.id }
				>
					{ __( 'Edit template', 'mailpoet' ) }
				</Button>
			) }
			<hr />
			<h3>Revert Template</h3>
			<Button
				variant="primary"
				onClick={ () => {
					void revertAndSaveTemplate( template );
				} }
			>
				{ __( 'Revert customizations', 'mailpoet' ) }
			</Button>
		</PanelBody>
	);
}
