import {
  __experimentalText as Text,
  DropdownMenu,
  ExternalLink,
  MenuGroup,
  MenuItem,
  PanelBody,
  TextareaControl,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { Icon, textHorizontal } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import classnames from 'classnames';
import { useState } from 'react';
import { storeName } from '../../store';

const previewTextMaxLength = 150;
const previewTextRecommendedLength = 80;

export function DetailsPanel() {
  const [cursorPosition, setCursorPosition] = useState(0);
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );
  const { updateEmailMailPoetProperty } = useDispatch(storeName);

  // Handle insertion of text at the current cursor position
  const insertAtCursor = async (textToInsert: string) => {
    const currentValue: string = mailpoetEmailData?.subject ?? '';
    const newValue: string =
      currentValue.slice(0, cursorPosition) +
      textToInsert +
      currentValue.slice(cursorPosition);

    // Update the value in your custom logic
    await updateEmailMailPoetProperty('subject', newValue);

    // Update the cursor position after insertion
    // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
    setCursorPosition(cursorPosition + textToInsert.length);
  };

  const subjectHelp = createInterpolateElement(
    __(
      'Use shortcodes to personalize your email, or learn more about <bestPracticeLink>best practices</bestPracticeLink> and using <emojiLink>emoji in subject lines</emojiLink>.',
      'mailpoet',
    ),
    {
      bestPracticeLink: (
        // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
        <a
          href="https://www.mailpoet.com/blog/17-email-subject-line-best-practices-to-boost-engagement/"
          target="_blank"
          rel="noopener noreferrer"
        />
      ),
      emojiLink: (
        // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
        <a
          href="https://www.mailpoet.com/blog/tips-using-emojis-in-subject-lines/"
          target="_blank"
          rel="noopener noreferrer"
        />
      ),
    },
  );

  // eslint-disable-next-line react/no-unstable-nested-components
  function DropdownPlaceholderMenu({ insert }) {
    return (
      <DropdownMenu
        icon="shortcode"
        label="Insert Placeholder"
        className="mailpoet-settings-panel__placeholder-shortcodes-dropdown"
      >
        {({ onClose }) => (
          <MenuGroup>
            <MenuItem
              onClick={() => {
                insert('[subscriber:firstname]');
                onClose();
              }}
            >
              First Name
            </MenuItem>
            <MenuItem
              onClick={async () => {
                await insertAtCursor('[subscriber:lastname]');
                onClose();
              }}
            >
              Last Name
            </MenuItem>
          </MenuGroup>
        )}
      </DropdownMenu>
    );
  }

  // eslint-disable-next-line react/no-unstable-nested-components
  function DropDownBitsMenu({ insert }) {
    return (
      <DropdownMenu
        icon={<Icon icon={textHorizontal} />}
        label="Insert Bit"
        className="mailpoet-settings-panel__placeholder-shortcodes-dropdown"
      >
        {({ onClose }) => (
          <MenuGroup>
            <MenuItem
              onClick={() => {
                insert('<//wp-bit:mailpoet/firstname>');
                onClose();
              }}
            >
              First Name
            </MenuItem>
            <MenuItem
              onClick={async () => {
                await insertAtCursor('<//wp-bit:mailpoet/lastname>');
                onClose();
              }}
            >
              Last Name
            </MenuItem>
          </MenuGroup>
        )}
      </DropdownMenu>
    );
  }

  const subjectLabel = (
    <>
      <span>{__('Subject', 'mailpoet')}</span>
      <DropdownPlaceholderMenu insert={insertAtCursor} />
      <DropDownBitsMenu insert={insertAtCursor} />
      <ExternalLink href="https://kb.mailpoet.com/article/215-personalize-newsletter-with-shortcodes#list">
        {__('Shortcode guide', 'mailpoet')}
      </ExternalLink>
    </>
  );

  const handleCursorChange = (event) => {
    // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
    setCursorPosition(event.target.selectionStart);
  };

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
        onChange={(value) => updateEmailMailPoetProperty('subject', value)}
        data-automation-id="email_subject"
        onClick={handleCursorChange} // Capture cursor position when clicked
        onKeyUp={handleCursorChange} // Capture cursor position when keys are pressed
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
        onChange={(value) => updateEmailMailPoetProperty('preheader', value)}
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
