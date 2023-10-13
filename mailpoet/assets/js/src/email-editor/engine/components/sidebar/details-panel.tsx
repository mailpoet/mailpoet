import {
  __experimentalText as Text,
  ExternalLink,
  PanelBody,
  TextareaControl,
  Tooltip,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { Icon, help } from '@wordpress/icons';
import ReactStringReplace from 'react-string-replace';
import { storeName } from '../../store';

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
        value={mailpoetEmailData?.preheader ?? ''}
        onChange={(value) => updateEmailProperty('preheader', value)}
        data-automation-id="email_preview_text"
      />
    </PanelBody>
  );
}
