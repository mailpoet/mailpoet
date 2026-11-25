import { createInterpolateElement } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import type { ReactNode } from 'react';
import { MailPoet } from 'mailpoet';

const FILTER_NAMESPACE = 'mailpoet/email-editor-integration';

const utmParameters = (postType: string): string =>
  `utm_source=woocommerce_email_editor&utm_medium=plugin&utm_source_platform=${postType}`;

function getSendingServiceNote(postType: string): JSX.Element {
  return createInterpolateElement(
    __(
      'Or, sign up for MailPoet Sending Service to easily send emails. <link>Sign up for free</link>',
      'mailpoet',
    ),
    {
      link: (
        // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label -- createInterpolateElement fills in the link text.
        <a
          href={`https://account.mailpoet.com/?s=1&g=1&${utmParameters(
            postType,
          )}`}
          target="_blank"
          rel="noopener noreferrer"
          onClick={() => {
            MailPoet.trackEvent(
              'email_editor_events_send_preview_email_modal_sign_up_for_mailpoet_sending_service_link_clicked',
            );
          }}
        />
      ),
    },
  );
}

// Help shown in the email editor when sending a test email fails.
export function registerSendPreviewHelp(): void {
  addFilter(
    'woocommerce_email_editor_check_sending_method_configuration_link',
    FILTER_NAMESPACE,
    (link: string, postType: string) =>
      link ||
      `https://www.mailpoet.com/blog/mailpoet-smtp-plugin/?${utmParameters(
        postType,
      )}`,
  );
  addFilter(
    'woocommerce_email_editor_send_preview_help_items',
    FILTER_NAMESPACE,
    (items: ReactNode[], postType: string) => [
      ...items,
      getSendingServiceNote(postType),
    ],
  );
}
