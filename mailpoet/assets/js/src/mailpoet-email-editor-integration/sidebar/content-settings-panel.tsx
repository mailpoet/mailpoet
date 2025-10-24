import {
  TextareaControl,
  __experimentalHStack as HStack,
} from '@wordpress/components';
import { select, dispatch } from '@wordpress/data';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';
import {
  // @ts-expect-error Type for PluginDocumentSettingPanel is missing in @types/wordpress__editor
  PluginDocumentSettingPanel,
  store as editorStore,
} from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';

const subjectMaxLength = 60;
const previewTextMaxLength = 150;
const previewTextRecommendedLength = 80;

export function ContentSettingsPanel() {
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

  const subjectLength = mailpoetEmailData?.subject?.length ?? 0;
  const previewTextLength = mailpoetEmailData?.preheader?.length ?? 0;

  const subjectHelp = (
    <HStack spacing={2} alignment="top">
      <span className="mailpoet-content-settings-panel__help-text">
        {__(
          'Personalise with tags: [site-title], [customer-firstname], [customer-lastname], [customer-email]',
          'mailpoet',
        )}
      </span>
      <span
        className={classNames('mailpoet-content-settings-panel__text-length', {
          'mailpoet-content-settings-panel__text-length--error':
            subjectLength > subjectMaxLength,
        })}
      >
        {subjectLength}/{subjectMaxLength}
      </span>
    </HStack>
  );

  const preheaderHelp = (
    <HStack spacing={2} alignment="top">
      <span className="mailpoet-content-settings-panel__help-text">
        {__(
          'Shown as a preview in the Inbox, next to the subject line.',
          'mailpoet',
        )}
      </span>
      <span
        className={classNames('mailpoet-content-settings-panel__text-length', {
          'mailpoet-content-settings-panel__text-length--warning':
            previewTextLength > previewTextRecommendedLength,
          'mailpoet-content-settings-panel__text-length--error':
            previewTextLength > previewTextMaxLength,
        })}
      >
        {previewTextLength}/{previewTextMaxLength}
      </span>
    </HStack>
  );

  return (
    <PluginDocumentSettingPanel
      name="mailpoet-content-settings"
      title={__('Content settings', 'mailpoet')}
      className="mailpoet-content-settings-panel"
    >
      <TextareaControl
        label={__('Subject', 'mailpoet')}
        value={mailpoetEmailData?.subject || ''}
        onChange={(value) => updateEmailMailPoetProperty('subject', value)}
        help={subjectHelp}
        placeholder={__('Eg. The summer sale is here!', 'mailpoet')}
        data-automation-id="email_subject"
      />

      <TextareaControl
        label={__('Preview text', 'mailpoet')}
        value={mailpoetEmailData?.preheader || ''}
        onChange={(value) => updateEmailMailPoetProperty('preheader', value)}
        help={preheaderHelp}
        placeholder={__(
          "Add a preview text to capture subscribers' attention and increase open rates.",
          'mailpoet',
        )}
        data-automation-id="email_preheader"
      />
    </PluginDocumentSettingPanel>
  );
}
