// This file acts as a way of adding JS integration support for the email editor package
// We have something similar for the PHP package in `mailpoet/lib/EmailEditor/Integrations`
// Here, we can expose MailPoet specific components for use in the Email editor.

import { addFilter } from '@wordpress/hooks';
import { withNpsPoll } from '../nps-poll';
import './index.scss';

addFilter('mailpoet_email_editor_the_editor_component', 'mailpoet', (editor) =>
  withNpsPoll(editor),
);
