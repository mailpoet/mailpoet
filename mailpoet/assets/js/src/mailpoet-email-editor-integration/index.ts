// This file acts as a way of adding JS integration support for the email editor package
// We have something similar for the PHP package in `mailpoet/lib/EmailEditor/Integrations`
// Here, we can expose MailPoet specific components for use in the Email editor.

import { addFilter, addAction } from '@wordpress/hooks';
import { MailPoet } from 'mailpoet';
import { withNpsPoll } from '../nps-poll';
import './index.scss';

addFilter('mailpoet_email_editor_wrap_editor_component', 'mailpoet', (editor) =>
  withNpsPoll(editor),
);

addAction('mailpoet_email_editor_events', 'mailpoet', (editorEvents) => {
  const { name, ...data } = editorEvents;
  MailPoet.trackEvent(name, data);
});

// enable email editor event tracking
addFilter(
  'mailpoet_email_editor_events_tracking_enabled',
  'mailpoet',
  () => window.mailpoet_analytics_enabled,
);
