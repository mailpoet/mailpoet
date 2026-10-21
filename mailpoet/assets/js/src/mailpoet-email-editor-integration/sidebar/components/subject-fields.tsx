import { TextareaControl } from '@wordpress/components';
import { Stack } from '@wordpress/ui';
import { select, dispatch } from '@wordpress/data';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { AiSubjectSuggestions } from './ai-subject-suggestions';
import { getCampaignNameForSubject } from '../campaign-name';
import { MAILPOET_EMAIL_POST_TYPE } from '../../constants';

const subjectMaxLength = 60;
const previewTextMaxLength = 150;
const previewTextRecommendedLength = 80;

type EmailTextFields = {
  subject?: string;
  preheader?: string;
};

type EditedEmail = {
  title?: string;
  mailpoet_data?: EmailTextFields;
};

function updateEmailTextFields(changes: EmailTextFields) {
  const postId = select(editorStore).getCurrentPostId();
  const editedEmail = select(coreDataStore).getEditedEntityRecord(
    'postType',
    MAILPOET_EMAIL_POST_TYPE,
    postId,
  ) as unknown as EditedEmail | undefined;

  const mailpoetData = editedEmail?.mailpoet_data || {};
  const edits: Record<string, unknown> = {
    mailpoet_data: {
      ...mailpoetData,
      ...changes,
    },
  };

  if (changes.subject !== undefined) {
    const currentName = editedEmail?.title ?? '';
    const newName = getCampaignNameForSubject({
      name: currentName,
      previousSubject: mailpoetData.subject ?? '',
      newSubject: changes.subject,
      defaultName: __('New Email', 'mailpoet'),
    });
    if (newName !== null && newName !== currentName) {
      edits.title = newName;
    }
  }

  void dispatch(coreDataStore).editEntityRecord(
    'postType',
    MAILPOET_EMAIL_POST_TYPE,
    postId,
    edits,
  );
}

export function SubjectFields() {
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    MAILPOET_EMAIL_POST_TYPE,
    'mailpoet_data',
  );

  const subjectLength = mailpoetEmailData?.subject?.length ?? 0;
  const previewTextLength = mailpoetEmailData?.preheader?.length ?? 0;

  const subjectHelp = (
    <Stack gap="lg" align="start" render={<span />}>
      <span className="mailpoet-subject-fields__help-text">
        {__(
          'Personalise with tags: [site-title], [customer-firstname], [customer-lastname], [customer-email]',
          'mailpoet',
        )}
      </span>
      <span
        className={classNames('mailpoet-subject-fields__text-length', {
          'mailpoet-subject-fields__text-length--error':
            subjectLength > subjectMaxLength,
        })}
      >
        {subjectLength}/{subjectMaxLength}
      </span>
    </Stack>
  );

  const preheaderHelp = (
    <Stack gap="lg" align="start" render={<span />}>
      <span className="mailpoet-subject-fields__help-text">
        {__(
          'Shown as a preview in the Inbox, next to the subject line.',
          'mailpoet',
        )}
      </span>
      <span
        className={classNames('mailpoet-subject-fields__text-length', {
          'mailpoet-subject-fields__text-length--warning':
            previewTextLength > previewTextRecommendedLength,
          'mailpoet-subject-fields__text-length--error':
            previewTextLength > previewTextMaxLength,
        })}
      >
        {previewTextLength}/{previewTextMaxLength}
      </span>
    </Stack>
  );

  return (
    <Stack direction="column" gap="lg" className="mailpoet-subject-fields">
      <AiSubjectSuggestions
        onSelect={(suggestion) => {
          updateEmailTextFields({
            subject: suggestion.subject,
            preheader: suggestion.preheader,
          });
        }}
      />
      <TextareaControl
        label={__('Subject', 'mailpoet')}
        value={mailpoetEmailData?.subject || ''}
        onChange={(value) => updateEmailTextFields({ subject: value })}
        help={subjectHelp}
        placeholder={__('Eg. The summer sale is here!', 'mailpoet')}
        data-automation-id="email_subject"
      />
      <TextareaControl
        label={__('Preview text', 'mailpoet')}
        value={mailpoetEmailData?.preheader || ''}
        onChange={(value) => updateEmailTextFields({ preheader: value })}
        help={preheaderHelp}
        placeholder={__(
          "Add a preview text to capture subscribers' attention and increase open rates.",
          'mailpoet',
        )}
        data-automation-id="email_preheader"
      />
    </Stack>
  );
}
