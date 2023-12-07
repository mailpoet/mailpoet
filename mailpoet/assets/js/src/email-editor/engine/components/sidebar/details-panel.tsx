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
import { createInterpolateElement } from '@wordpress/element';
import classnames from 'classnames';
import { storeName } from '../../store';

const previewTextMaxLength = 150;
const previewTextRecommendedLength = 80;

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

  const preheaderLabel = (
    <>
      <span>{__('Preview text', 'mailpoet')}</span>
      <span
        className={classnames('mailpoet-settings-panel__preview-text-length', {
          'mailpoet-settings-panel__preview-text-length-warning':
            previewTextLength > previewTextRecommendedLength,
          'mailpoet-settings-panel__preview-text-length-error':
            previewTextLength > previewTextMaxLength,
        })}
      >
        {previewTextLength}/{previewTextMaxLength}
      </span>
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
      <div className="mailpoet-settings-panel__help">
        <Text>{subjectHelp}</Text>
      </div>

      <TextareaControl
        className="mailpoet-settings-panel__preview-text"
        label={preheaderLabel}
        placeholder={__(
          "Add a preview text to capture subscribers' attention and increase open rates.",
          'mailpoet',
        )}
        value={mailpoetEmailData?.preheader ?? ''}
        onChange={(value) => updateEmailProperty('preheader', value)}
        data-automation-id="email_preview_text"
      />
      <div className="mailpoet-settings-panel__help">
        <Text>
          {createInterpolateElement(
            __(
              '<link>This text</link> will appear in the inbox, underneath the subject line.',
              'mailpoet',
            ),
            {
              link: (
                // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
                <a
                  href={new URL(
                    'article/418-preview-text',
                    'https://kb.mailpoet.com/',
                  ).toString()}
                  key="preview-text-kb"
                  target="_blank"
                  rel="noopener noreferrer"
                />
              ),
            },
          )}
        </Text>
      </div>
    </PanelBody>
  );
}
