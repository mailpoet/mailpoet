// This file acts as a way of adding JS integration support for the email editor package
// We have something similar for the PHP package in `mailpoet/lib/EmailEditor/Integrations`
// Here, we can expose MailPoet specific components for use in the Email editor.

import { addFilter, addAction } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { select } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { MailPoet } from 'mailpoet';
import { initializeEditor } from '@woocommerce/email-editor';
import { EmailContentValidationRule } from '@woocommerce/email-editor/build-types/store';
import { withSatismeterSurvey } from './satismeter-survey';
import { EmailSidebarExtension } from './email-sidebar-extension';
import './index.scss';
import { emailValidationRule } from './validate-email-content';
import { initStripPostStatusOnSaveMiddleware } from './middleware/strip-post-status-on-save';

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
    // For automation newsletters, use the same label as the legacy editor
    // See /assets/js/src/newsletter-editor/initializer.jsx#L49
    if (isAutomationNewsletter) {
      return __('Save and continue', 'mailpoet');
    }

    // For regular campaign emails, check if scheduled
    const postId = select(editorStore).getCurrentPostId();
    const editedPost = select(coreDataStore).getEditedEntityRecord(
      'postType',
      'mailpoet_email',
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

// integration point for settings sidebar
// Hide subject and preview text fields for Automation emails.
// This is because the Automation editor has its own subject and preview text fields and there isn't a need to show them again in the email editor.
if (!isAutomationNewsletter) {
  addFilter(
    'woocommerce_email_editor_setting_sidebar_extension_component',
    'mailpoet/email-editor-integration',
    () => EmailSidebarExtension,
  );
}

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

initStripPostStatusOnSaveMiddleware();

initializeEditor('mailpoet-email-editor');
