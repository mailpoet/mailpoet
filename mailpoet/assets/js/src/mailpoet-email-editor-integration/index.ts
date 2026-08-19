// This file acts as a way of adding JS integration support for the email editor package
// We have something similar for the PHP package in `mailpoet/lib/EmailEditor/Integrations`
// Here, we can expose MailPoet specific components for use in the Email editor.

import { addFilter, addAction } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { select, dispatch } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { store as noticesStore } from '@wordpress/notices';
import { registerPlugin } from '@wordpress/plugins';
import { MailPoet } from 'mailpoet';
import type {
  EmailContentValidationRule,
  RecentEmailsQuery,
} from '@woocommerce/email-editor/build-types/store';
import { registerTranslations } from 'common';
import { withSatismeterSurvey } from './satismeter-survey';
import './index.scss';
import { emailValidationRule } from './validate-email-content';
import { registerCouponCodeRestrictToSubscriberExtension } from './coupon-code-restrict-to-subscriber-control';
import { registerOrderProductCollectionsWhenAvailable } from './order-product-collections';
import { store as emailEditorIntegrationStore } from './store';
import { MAILPOET_EMAIL_POST_TYPE } from './constants';

registerTranslations();
registerCouponCodeRestrictToSubscriberExtension();
registerOrderProductCollectionsWhenAvailable();

// MailPoet's shared Modal portals into a `#mailpoet-modal` container that the
// legacy admin layout provides. The block editor has no such layout, so create
// the container here to support modals like the sender authorization flow.
function ensureMailPoetModalContainer(): void {
  if (document.getElementById('mailpoet-modal')) {
    return;
  }
  const container = document.createElement('div');
  container.id = 'mailpoet-modal';
  document.body.appendChild(container);
}

addFilter(
  'woocommerce_email_editor_wrap_editor_component',
  'mailpoet/email-editor-integration',
  (editor) => withSatismeterSurvey(editor),
);

// validate email editor content using the defined validation rules
// content is first validated when the "Send" button is clicked and revalidated on "Save Draft"
addFilter(
  'woocommerce_email_editor_content_validation_rules',
  'mailpoet/email-editor-integration',
  (rules: EmailContentValidationRule[]) => [
    ...(rules || []),
    emailValidationRule,
  ],
);

const isAutomationNewsletter = window?.mailpoet_is_automation_newsletter;

addFilter(
  'woocommerce_email_editor_send_button_label',
  'mailpoet/email-editor-integration',
  () => {
    if (isAutomationNewsletter) {
      return __('Return back to the Automation', 'mailpoet');
    }

    // For regular campaign emails, check if scheduled
    const postId = select(editorStore).getCurrentPostId();
    const editedPost = select(coreDataStore).getEditedEntityRecord(
      'postType',
      MAILPOET_EMAIL_POST_TYPE,
      postId,
    );

    // @ts-expect-error Property 'mailpoet_data' does not exist on type 'Updatable<Attachment<any>>'.
    const scheduledAt = editedPost?.mailpoet_data?.scheduled_at;

    if (scheduledAt) {
      return __('Review & schedule', 'mailpoet');
    }

    return __('Review & send', 'mailpoet');
  },
);

// Open the in-editor send panel instead of navigating away. Automation
// emails keep the default action (returning to the automation editor).
addFilter(
  'woocommerce_email_editor_send_action_callback',
  'mailpoet/email-editor-integration',
  (defaultAction: () => void) => {
    if (isAutomationNewsletter) {
      return defaultAction;
    }

    return () => {
      const postId = select(editorStore).getCurrentPostId();
      if (postId && select(editorStore).isEditedPostDirty()) {
        void dispatch(coreDataStore)
          .saveEditedEntityRecord(
            'postType',
            MAILPOET_EMAIL_POST_TYPE,
            postId,
            {
              throwOnError: true,
            },
          )
          .then(() => dispatch(emailEditorIntegrationStore).openSendPanel())
          .catch((error: { message?: string }) => {
            void dispatch(noticesStore).createErrorNotice(
              error?.message ||
                __(
                  'The email could not be saved. Please try again.',
                  'mailpoet',
                ),
              { type: 'snackbar' },
            );
          });
        return;
      }
      void dispatch(emailEditorIntegrationStore).openSendPanel();
    };
  },
);

// Keep the send button enabled when there are unsaved changes — the
// send button action saves before opening the send panel.
// Empty and already-sent emails stay disabled.
addFilter(
  'woocommerce_email_editor_send_button_disabled',
  'mailpoet/email-editor-integration',
  (
    isDisabled: boolean,
    flags: { hasEmptyContent: boolean; isEmailSent: boolean },
  ) => {
    if (isAutomationNewsletter) {
      return isDisabled;
    }
    return flags.hasEmptyContent || flags.isEmailSent;
  },
);

const EVENTS_TO_TRACK = [
  'email_editor_events_editor_layout_loaded', // email editor was opened
  'email_editor_events_template_select_modal_template_selected', // a template was selected from the template-select modal
  'email_editor_events_template_select_modal_start_from_scratch_clicked', // start from scratch
  'email_editor_events_header_campaign_name_title_updated', // campaign title was used
  'email_editor_events_header_preview_dropdown_mobile_selected', // preview option - mobile
  'email_editor_events_header_preview_dropdown_desktop_selected', // preview option - desktop
  'email_editor_events_header_preview_dropdown_send_test_email_selected', // preview option - send test email
  'email_editor_events_sent_preview_email', // preview email sent
  'email_editor_events_header_preview_dropdown_preview_in_new_tab_selected', // preview option - in new tab
  'email_editor_events_rich_text_with_button_personalization_tags_shortcode_icon_clicked', // personalization_tags modal opened
  'email_editor_events_personalization_tags_modal_tag_insert_button_clicked', // personalization_tags inserted
  'email_editor_events_rich_text_with_button_input_field_updated', // either subject or preheader updated
  'email_editor_events_styles_sidebar_screen_typography_opened', // styles sidebar-typography was seen
  'email_editor_events_styles_sidebar_screen_colors_opened', // styles sidebar-colors was seen
  'email_editor_events_styles_sidebar_screen_layout_opened', // styles sidebar-layout was seen
  'email_editor_events_header_send_button_clicked', // Send button clicked
  'email_editor_events_trash_modal_move_to_trash_button_clicked', // Move to trash button was clicked
];

addAction('woocommerce_email_editor_events', 'mailpoet', (editorEvents) => {
  const { name, ...data } = editorEvents;
  // To prevent going over mixpanel quota, we will limit the number of email editor events we track with mixpanel
  // Tracks will log all events. This will be done in MAILPOET-5995
  if (EVENTS_TO_TRACK.includes(String(name))) {
    MailPoet.trackEvent(name, data);
  }
});

// enable email editor event tracking
addFilter(
  'woocommerce_email_editor_events_tracking_enabled',
  'mailpoet/email-editor-integration',
  () => !!window.mailpoet_analytics_enabled,
);

// use mailpoet data subject if available
addFilter(
  'woocommerce_email_editor_preferred_template_title',
  'mailpoet/email-editor-integration',
  (...args) => {
    const [, post] = args;
    // eslint-disable-next-line @typescript-eslint/no-unsafe-return
    return post?.mailpoet_data?.subject || ''; // use MailPoet subject as title
  },
);

// Also list draft, scheduled and sending emails. They all use the `draft` post status.
addFilter(
  'woocommerce_email_editor_recent_emails_query',
  'mailpoet/email-editor-integration',
  (query: RecentEmailsQuery, postType: string) => {
    if (postType !== MAILPOET_EMAIL_POST_TYPE) {
      return query;
    }
    return { ...query, status: 'publish,sent,draft' };
  },
);

/**
 * Disables real-time collaboration by removing all sync providers.
 * This forces the editor to fall back to traditional single-user post locking.
 */
addFilter(
  'sync.providers',
  'mailpoet/email-editor-integration-disable-rtc',
  () => [],
);

type EmailEditorModule = typeof import('@woocommerce/email-editor');
type StripPostStatusMiddlewareModule =
  typeof import('./middleware/strip-post-status-on-save');
type EmailSidebarExtensionModule = typeof import('./email-sidebar-extension');
type AutomationSaveButtonModule =
  typeof import('./components/automation-save-button');
type SentEmailNoticeModule = typeof import('./components/sent-email-notice');

/* eslint-disable global-require, @typescript-eslint/no-var-requires -- These imports must run after the MailPoet coupon block extension registers its block-type filter, but must stay in this bundle instead of creating async chunks. */
const initializeMailPoetEmailEditor = (): void => {
  const { initializeEditor } =
    require('@woocommerce/email-editor') as EmailEditorModule;
  const { initStripPostStatusOnSaveMiddleware } =
    require('./middleware/strip-post-status-on-save') as StripPostStatusMiddlewareModule;

  // Register MailPoet sidebar panels component
  if (!isAutomationNewsletter) {
    const { EmailSidebarExtension } =
      require('./email-sidebar-extension') as EmailSidebarExtensionModule;

    registerPlugin('mailpoet-settings-sidebar', {
      render: EmailSidebarExtension,
      scope: 'woocommerce-email-editor',
    });

    // Render a notice for email that has already been sent.
    const { SentEmailNotice } =
      require('./components/sent-email-notice') as SentEmailNoticeModule;

    registerPlugin('mailpoet-sent-email-notice', {
      render: SentEmailNotice,
      scope: 'woocommerce-email-editor',
    });
  }

  // Register save button for automation emails
  // Automation emails use 'private' post status, which WordPress treats as published.
  // The standard "Save Draft" button is not shown for published posts, so we add our own.
  if (isAutomationNewsletter) {
    const { AutomationSaveButton } =
      require('./components/automation-save-button') as AutomationSaveButtonModule;

    registerPlugin('mailpoet-automation-save-button', {
      render: AutomationSaveButton,
      scope: 'woocommerce-email-editor',
    });
  }

  initStripPostStatusOnSaveMiddleware();
  initializeEditor('mailpoet-email-editor');
};
/* eslint-enable global-require, @typescript-eslint/no-var-requires */

ensureMailPoetModalContainer();
initializeMailPoetEmailEditor();
