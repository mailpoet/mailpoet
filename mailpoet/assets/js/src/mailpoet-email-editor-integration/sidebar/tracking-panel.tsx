import { TextControl } from '@wordpress/components';
import { select, dispatch } from '@wordpress/data';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';
import {
  // @ts-expect-error Type for PluginDocumentSettingPanel is missing in @types/wordpress__editor
  PluginDocumentSettingPanel,
  store as editorStore,
} from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

export function TrackingPanel() {
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );

  const updateEmailMailPoetProperty = (name: string, value: string) => {
    const postId = select(editorStore).getCurrentPostId();
    const currentPostType = 'mailpoet_email'; // only for mailpoet_email post-type

    const editedPost = select(coreDataStore).getEditedEntityRecord(
      'postType',
      currentPostType,
      postId,
    );

    // @ts-expect-error Property 'mailpoet_data' does not exist on type 'Updatable<Attachment<any>>'.
    const mailpoetData = editedPost?.mailpoet_data || {};
    void dispatch(coreDataStore).editEntityRecord(
      'postType',
      currentPostType,
      postId,
      {
        mailpoet_data: {
          ...mailpoetData,
          [name]: value,
        },
      },
    );
  };

  return (
    <PluginDocumentSettingPanel
      name="mailpoet-tracking"
      title={__('Tracking', 'mailpoet')}
    >
      <TextControl
        label={__('utm_campaign', 'mailpoet')}
        value={mailpoetEmailData?.utm_campaign || ''}
        onChange={(value) => updateEmailMailPoetProperty('utm_campaign', value)}
        help={__(
          'Add a tracking code to use with Google Analytics',
          'mailpoet',
        )}
      />
    </PluginDocumentSettingPanel>
  );
}
