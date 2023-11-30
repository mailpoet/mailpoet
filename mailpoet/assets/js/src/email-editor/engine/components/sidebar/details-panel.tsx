import {
  __experimentalText as Text,
  ExternalLink,
  PanelBody,
  TextareaControl,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import ReactStringReplace from 'react-string-replace';
import { storeName } from '../../store';

const previewTextMaxLength = 150;

export function DetailsPanel() {
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );

  const { updateEmailProperty } = useDispatch(storeName);

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

  const previewTextLength = mailpoetEmailData?.preheader?.length ?? 0;

  return (
    <PanelBody
      title={__('Details', 'mailpoet')}
      className="mailpoet-email-editor__settings-panel"
    >
      <TextareaControl
        className="mailpoet-settings-panel__subject"
        label={subjectLabel}
        placeholder={__('Eg. The summer sale is here!', 'mailpoet')}
        value={mailpoetEmailData?.subject ?? ''}
        onChange={(value) => updateEmailProperty('subject', value)}
        data-automation-id="email_subject"
      />
      <div className="mailpoet-settings-panel__help">
        <Text>{subjectHelp}</Text>
      </div>

      <TextareaControl
        className="mailpoet-settings-panel__preview-text"
        label={<span>{__('Preview text (recommended)', 'mailpoet')}</span>}
        placeholder={__(
          "Add a preview text to capture subscribers' attention and increase open rates.",
          'mailpoet',
        )}
        value={mailpoetEmailData?.preheader ?? ''}
        onChange={(value) => updateEmailProperty('preheader', value)}
        data-automation-id="email_preview_text"
      />
      {previewTextLength}
      {'/'}
      {previewTextMaxLength}
      <div className="mailpoet-settings-panel__help">
        <Text>
          {__(
            'This text will appear in the inbox, underneath the subject line.',
          )}{' '}
          {__(
            'We recommend to keep it short, and to use it to complement the subject line.',
          )}
        </Text>
      </div>
    </PanelBody>
  );
}
