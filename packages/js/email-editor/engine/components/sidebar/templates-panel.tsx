import { PanelBody, Button } from '@wordpress/components';
import { useSelect, useDispatch, dispatch, select } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { store as editorStore } from '@wordpress/editor';
import { decodeEntities } from '@wordpress/html-entities';
import { store as coreStore } from '@wordpress/core-data';
import { store as noticesStore } from '@wordpress/notices';
import apiFetch from '@wordpress/api-fetch';
import {
  parse,
  // @ts-expect-error No types available for this yet.
  __unstableSerializeAndClean,
  BlockInstance,
} from '@wordpress/blocks';
import { addQueryArgs } from '@wordpress/url';
import { storeName } from '../../store';
import { unlock } from '../../../lock-unlock';

// Todo: This is not available yet. Replace when possible.
async function revertTemplate(template) {
  const templateEntityConfig = select(coreStore).getEntityConfig(
    'postType',
    template.type as string,
  );

  const fileTemplatePath = addQueryArgs(
    `${templateEntityConfig.baseURL as string}/${template.id as string}`,
    { context: 'edit', source: 'theme' },
  );

  const fileTemplate = await apiFetch({ path: fileTemplatePath });

  const serializeBlocks = ({ blocks: blocksForSerialization = [] }) =>
    __unstableSerializeAndClean(blocksForSerialization) as BlockInstance[];

  // @ts-expect-error template type is not defined
  const blocks = parse(fileTemplate?.content?.raw as string);
  void dispatch(coreStore).editEntityRecord(
    'postType',
    template.type as string,
    // @ts-expect-error template type is not defined
    fileTemplate.id as string,
    {
      content: serializeBlocks,
      blocks,
      source: 'theme',
    },
  );
}

export function TemplatesPanel() {
  const { onNavigateToEntityRecord, template, hasHistory } = useSelect(
    (sel) => {
      // eslint-disable-next-line @typescript-eslint/naming-convention
      const { getEditorSettings: _getEditorSettings } = unlock(
        sel(editorStore),
      );
      const editorSettings = _getEditorSettings();
      return {
        onNavigateToEntityRecord: editorSettings.onNavigateToEntityRecord,
        hasHistory: !!editorSettings.onNavigateToPreviousEntityRecord,
        template: sel(storeName).getEditedPostTemplate(),
      };
    },
    [],
  );

  const { saveEditedEntityRecord } = useDispatch(coreStore);
  const { createSuccessNotice, createErrorNotice } = useDispatch(noticesStore);
  async function revertAndSaveTemplate() {
    try {
      await revertTemplate(template);
      await saveEditedEntityRecord('postType', template.type, template.id, {});
      void createSuccessNotice(
        sprintf(
          /* translators: The template/part's name. */
          __('"%s" reset.', 'mailpoet'),
          decodeEntities(template.title),
        ),
        {
          type: 'snackbar',
          id: 'edit-site-template-reverted',
        },
      );
    } catch (error) {
      void createErrorNotice(
        __('An error occurred while reverting the template.', 'mailpoet'),
        {
          type: 'snackbar',
        },
      );
    }
  }

  return (
    <PanelBody
      title={__('Templates Experiment', 'mailpoet')}
      className="mailpoet-email-editor__settings-panel"
    >
      <p>
        Components from this Panel will be placed in different areas of the UI.
        They are place here in one place just to simplify the experiment.
      </p>
      <hr />
      <h3>Edit template toggle</h3>
      {template && !hasHistory && (
        <Button
          variant="primary"
          onClick={() => {
            onNavigateToEntityRecord({
              postId: template.id,
              postType: 'wp_template',
            });
          }}
          disabled={!template.id}
        >
          {__('Edit template', 'mailpoet')}
        </Button>
      )}
      <hr />
      <h3>Revert Template</h3>
      <Button
        variant="primary"
        onClick={() => {
          void revertAndSaveTemplate();
        }}
      >
        {__('Revert customizations', 'mailpoet')}
      </Button>
    </PanelBody>
  );
}
