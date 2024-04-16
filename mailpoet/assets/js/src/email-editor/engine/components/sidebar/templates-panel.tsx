import { PanelBody, Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import {
  store as editorStore,
  // @ts-expect-error Our current version of packages doesn't have EntitiesSavedStates export
  EntitiesSavedStates,
} from '@wordpress/editor';

import { storeName } from '../../store';

import { unlock } from '../../../lock-unlock';

export function TemplatesPanel() {
  const { onNavigateToEntityRecord, template, hasHistory } = useSelect(
    (select) => {
      // eslint-disable-next-line @typescript-eslint/naming-convention
      const { getEditorSettings: _getEditorSettings } = unlock(
        select(editorStore),
      );
      const editorSettings = _getEditorSettings();
      return {
        onNavigateToEntityRecord: editorSettings.onNavigateToEntityRecord,
        hasHistory: !!editorSettings.onNavigateToPreviousEntityRecord,
        template: select(storeName).getEditedPostTemplate(),
      };
    },
    [],
  );

  return (
    <PanelBody
      title={__('Templates Experiment', 'mailpoet')}
      className="mailpoet-email-editor__settings-panel"
    >
      <p>
        Components from this Panel will be placed in different areas of the UI.
        They are place here in one place just to simplify the experiment.
      </p>
      <h3>Edit template toggle</h3>
      {template && !hasHistory && (
        <Button
          variant="primary"
          onClick={() => {
            onNavigateToEntityRecord({
              // @ts-expect-error template type is not defined
              postId: template.id,
              postType: 'wp_template',
            });
          }}
          // @ts-expect-error template type is not defined
          disabled={!template.id}
        >
          {__('Edit template', 'mailpoet')}
        </Button>
      )}
      <hr />
      <h3>Save panel</h3>
      <EntitiesSavedStates close={() => {}} />
    </PanelBody>
  );
}
