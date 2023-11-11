import { useRef } from '@wordpress/element';
import {
  Button,
  Dropdown,
  VisuallyHidden,
  __experimentalText as Text,
  TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronDown } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { storeName } from '../../store';

// @see https://github.com/WordPress/gutenberg/blob/5e0ffdbc36cb2e967dfa6a6b812a10a2e56a598f/packages/edit-post/src/components/header/document-actions/index.js

export function DocumentActions() {
  const { showIconLabels } = useSelect(
    (select) => ({
      showIconLabels: select(storeName).isFeatureActive('showIconLabels'),
      postId: select(storeName).getEmailPostId(),
    }),
    [],
  );

  const [emailTitle = '', setTitle] = useEntityProp(
    'postType',
    'mailpoet_email',
    'title',
  );

  const titleRef = useRef(null);
  return (
    <div ref={titleRef} className="mailpoet-email-editor-document-actions">
      <Dropdown
        popoverProps={{
          placement: 'bottom',
          anchor: titleRef.current,
        }}
        contentClassName="mailpoet-email-editor-document-actions__dropdown"
        renderToggle={({ isOpen, onToggle }) => (
          <>
            <Button
              onClick={onToggle}
              className="mailpoet-email-document-actions__link"
            >
              <Text size="body" as="h1">
                <VisuallyHidden as="span">
                  {__('Editing email:', 'mailpoet')}
                </VisuallyHidden>
                {emailTitle}
              </Text>
            </Button>
            <Button
              className="mailpoet-email-document-actions__toggle"
              icon={chevronDown}
              aria-expanded={isOpen}
              aria-haspopup="true"
              onClick={onToggle}
              label={__('Change campaign name', 'mailpoet')}
            >
              {showIconLabels && __('Rename', 'mailpoet')}
            </Button>
          </>
        )}
        renderContent={() => (
          <div className="mailpoet-email-editor-email-title-edit">
            <div className="mailpoet-email-editor-dropdown-name-edit-title">
              {__('Campaign name', 'mailpoet')}
            </div>
            <TextControl
              value={emailTitle}
              onChange={(newTitle) => {
                setTitle(newTitle);
              }}
              name="campaign_name"
              help={__(
                `Name your email campaign to indicate its purpose. This would only be visible to you and not shown to your subscribers.`,
                'mailpoet',
              )}
            />
          </div>
        )}
      />
    </div>
  );
}
