import {
  __experimentalText as Text,
  ExternalLink,
  TextareaControl,
} from '@wordpress/components';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import ReactStringReplace from 'react-string-replace';

export function DetailsPanel() {
  const [subject, setSubject] = useState('');

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

  // Render email details panel using PluginDocumentSettingPanel component
  return (
    <PluginDocumentSettingPanel
      className="mailpoet-email-editor__settings-panel"
      title={__('Details', 'mailpoet')}
      name="mailpoet-email-editor-setting-panel"
    >
      <TextareaControl
        className="settings-panel__subject"
        label={subjectLabel}
        placeholder={__('Eg. The summer sale is here!', 'mailpoet')}
        value={subject}
        onChange={(value) => setSubject(value)}
      />
      <div className="settings-panel__subject-help">
        <Text>{subjectHelp}</Text>
      </div>
    </PluginDocumentSettingPanel>
  );
}
