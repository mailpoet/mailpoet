import {
  __experimentalText as Text,
  ExternalLink,
  TextareaControl,
  Tooltip,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { Icon, help } from '@wordpress/icons';
import ReactStringReplace from 'react-string-replace';
import { MailPoetEmailData } from '../types';

export function DetailsPanel() {
  const mailpoetData = useSelect(
    (select) =>
      (select(editorStore).getEditedPostAttribute(
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        // The getEditedPostAttribute accepts an attribute but typescript thinks it doesn't
        'mailpoet_data',
      ) as MailPoetEmailData) ?? null,
  );

  const { editPost } = useDispatch(editorStore);

  const handleChange = (name, value) => {
    const mailpoetDataUpdated = {
      mailpoet_data: {
        ...mailpoetData,
        [name]: value,
      },
    };
    editPost(mailpoetDataUpdated);
  };

  let subjectHelp = ReactStringReplace(
    __(
      'Use shortcodes to personalize your email, or learn more about [link1]best practices[/link1] and using [link2]emoji in subject lines[/link2].',
      'mailpoet',
    ),
    /\[link1\](.*?)\[\/link1\]/g,
    (match, i) => (
      <a
        key={i}
        href="https://www.mailpoet.com/blog/17-email-subject-line-best-practices-to-boost-engagement/"
        target="_blank"
        rel="noopener noreferrer"
      >
        {match}
      </a>
    ),
  );
  subjectHelp = ReactStringReplace(
    subjectHelp,
    /\[link2\](.*?)\[\/link2\]/g,
    (match, i) => (
      <a
        key={i}
        href="https://www.mailpoet.com/blog/tips-using-emojis-in-subject-lines/"
        target="_blank"
        rel="noopener noreferrer"
      >
        {match}
      </a>
    ),
  );

  const subjectLabel = (
    <>
      <span>{__('Subject', 'mailpoet')}</span>
      <ExternalLink href="https://kb.mailpoet.com/article/215-personalize-newsletter-with-shortcodes#list">
        {__('Shortcode guide', 'mailpoet')}
      </ExternalLink>
    </>
  );

  const previewTextLabel = (
    <>
      <span>{__('Preview text (recommended)', 'mailpoet')}</span>
      <Tooltip
        text={__(
          'This text will appear in the inbox, underneath the subject line. Max length is 250 characters, but we recommend 80 characters.',
          'mailpoet',
        )}
      >
        <span className="mailpoet-preview-text__help-icon">
          <Icon icon={help} size={20} />
        </span>
      </Tooltip>
    </>
  );

  // Render email details panel using PluginDocumentSettingPanel component
  return (
    <PluginDocumentSettingPanel
      className="mailpoet-email-editor__settings-panel"
      title={__('Details', 'mailpoet')}
      name="mailpoet-email-editor-setting-panel"
    >
      <TextareaControl
        className="mailpoet-settings-panel__subject"
        label={subjectLabel}
        placeholder={__('Eg. The summer sale is here!', 'mailpoet')}
        value={mailpoetData.subject ?? ''}
        onChange={(value) => handleChange('subject', value)}
        data-automation-id="email_subject"
      />
      <div className="mailpoet-settings-panel__subject-help">
        <Text>{subjectHelp}</Text>
      </div>

      <TextareaControl
        className="mailpoet-settings-panel__preview-text"
        label={previewTextLabel}
        placeholder={__(
          "Add a preview text to capture subscribers' attention and increase open rates.",
          'mailpoet',
        )}
        value={mailpoetData.preheader ?? ''}
        onChange={(value) => handleChange('preheader', value)}
        data-automation-id="email_preview_text"
      />
    </PluginDocumentSettingPanel>
  );
}
