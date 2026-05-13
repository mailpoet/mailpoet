import { ChangeEvent, Component, useEffect } from 'react';
import { MailPoet } from 'mailpoet';
import { Hooks } from 'wp-js-hooks';
import { __ } from '@wordpress/i18n';

import { DateTime } from 'newsletters/send/date-time';
import { SenderField } from 'newsletters/send/sender-address-field.jsx';
import { GATrackingField } from 'newsletters/send/ga-tracking';
import { Toggle } from 'common/form/toggle/toggle';
import { Radio } from 'common/form/radio/radio';
import { withBoundary } from 'common';
import { NewsLetter, NewsletterStatus } from 'common/newsletter';
import {
  getExcludeFromArchiveOptionValue,
  isNewsletterShownInArchive,
} from 'common/newsletter-archive-visibility';
import { Field } from 'form/types';
import { SendToFieldWithCount } from './send-to-field';

const tomorrowLocalDateTime = `${window.mailpoet_tomorrow_date} 08:00:00`;
const tomorrowDateTime = MailPoet.Date.toGmtDatetimeString(
  tomorrowLocalDateTime,
);
const timeOfDayItems = window.mailpoet_schedule_time_of_day as unknown as {
  [key: string]: string;
};
const dateDisplayFormat = window.mailpoet_date_format;
const dateStorageFormat = window.mailpoet_date_storage_format;

type StandardSchedulingProps = {
  item?: NewsLetter;
  onValueChange: (targetWrap: {
    target: {
      name: string;
      value: string | Partial<NewsLetter['options']>;
    };
  }) => void;
  field: Field;
};

class StandardScheduling extends Component<StandardSchedulingProps> {
  getCurrentValue = () => {
    const schedulingOptions = {
      isScheduled: '0',
      scheduledAt: tomorrowDateTime,
    };

    return {
      ...schedulingOptions,
      ...(this.props.item?.[this.props.field.name] ?? {}),
    };
  };

  getDateValidation = () => ({
    'data-parsley-required': true,
    'data-parsley-required-message': __(
      'Please enter the scheduled date.',
      'mailpoet',
    ),
    'data-parsley-errors-container': '#mailpoet_scheduling',
  });

  isScheduled = () => this.getCurrentValue().isScheduled === '1';

  handleCheckboxChange = (_, event: ChangeEvent<HTMLInputElement>): void => {
    const changeEvent = { ...event };
    changeEvent.target.value = event.target.checked ? '1' : '0';
    this.handleValueChange(changeEvent);
  };

  handleValueChange = (event: ChangeEvent<HTMLInputElement>): void => {
    const oldValue = this.getCurrentValue();
    const newValue = {};
    newValue[event.target.name] = event.target.value;

    this.props.onValueChange({
      target: {
        name: this.props.field.name,
        value: { ...oldValue, ...newValue },
      },
    });
  };

  render() {
    let schedulingOptions;

    const maxDate = new Date();
    maxDate.setFullYear(maxDate.getFullYear() + 5);

    if (this.isScheduled()) {
      schedulingOptions = (
        <>
          <span className="mailpoet-form-schedule-time">
            {__('Your website’s time is', 'mailpoet')}{' '}
            {MailPoet.Date.time(new Date())}
          </span>
          <div className="mailpoet-gap" />
          <div id="mailpoet_scheduling">
            <DateTime
              name="scheduledAt"
              value={this.getCurrentValue().scheduledAt}
              onChange={this.handleValueChange}
              disabled={this.props.field.disabled}
              dateValidation={this.getDateValidation()}
              defaultDateTime={tomorrowDateTime}
              timeOfDayItems={timeOfDayItems}
              dateDisplayFormat={dateDisplayFormat}
              dateStorageFormat={dateStorageFormat}
              maxDate={maxDate}
            />
          </div>
        </>
      );
    }
    return (
      <div>
        <Toggle
          checked={this.isScheduled()}
          disabled={this.props.field.disabled}
          name="isScheduled"
          onCheck={this.handleCheckboxChange}
          automationId="email-schedule-checkbox"
        />

        {schedulingOptions}
      </div>
    );
  }
}

function StandardSharingVisibility({
  item,
  field,
  onValueChange,
}: StandardSchedulingProps) {
  const options: Partial<NewsLetter['options']> = item?.options ?? {};
  const savedVisibility = options.shareVisibility;
  const visibility: 'public' | 'private' =
    savedVisibility === 'public' || savedVisibility === 'private'
      ? savedVisibility
      : item?.effective_share_visibility || 'private';
  const changeVisibility = (value: 'public' | 'private') => {
    onValueChange({
      target: {
        name: field.name,
        value: { ...options, shareVisibility: value },
      },
    });
  };

  useEffect(() => {
    if (savedVisibility) {
      return;
    }
    onValueChange({
      target: {
        name: field.name,
        value: { ...options, shareVisibility: visibility },
      },
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps -- seed shareVisibility once on mount when unset; rerunning would clobber user edits
  }, []);

  return (
    <div>
      <p className="mailpoet-form-description">
        {__(
          'Choose whether this email can be opened from a public share link after it is sent.',
          'mailpoet',
        )}
      </p>
      <div className="mailpoet-settings-inputs-row">
        <Radio
          id="mailpoet-share-visibility-public"
          value="public"
          checked={visibility === 'public'}
          onCheck={changeVisibility}
          name="shareVisibility"
        />
        <label htmlFor="mailpoet-share-visibility-public">
          {__('Public', 'mailpoet')}
        </label>
      </div>
      <div className="mailpoet-settings-inputs-row">
        <Radio
          id="mailpoet-share-visibility-private"
          value="private"
          checked={visibility === 'private'}
          onCheck={changeVisibility}
          name="shareVisibility"
        />
        <label htmlFor="mailpoet-share-visibility-private">
          {__('Private', 'mailpoet')}
        </label>
      </div>
    </div>
  );
}

function StandardOptions(props: StandardSchedulingProps) {
  return (
    <>
      <StandardScheduling {...props} />
      <div className="mailpoet-gap" />
      <h4 className="mailpoet-h4">{__('Sharing visibility', 'mailpoet')}</h4>
      <StandardSharingVisibility {...props} />
    </>
  );
}

function ArchiveVisibility({
  item,
  onValueChange,
  field,
}: StandardSchedulingProps) {
  const currentOptions: Partial<NewsLetter['options']> = item?.options ?? {};
  const helperTextId = 'mailpoet-archive-visibility-help';
  const label = __('Show this newsletter in the archive', 'mailpoet');

  return (
    <>
      <Toggle
        checked={isNewsletterShownInArchive(currentOptions.excludeFromArchive)}
        disabled={field.disabled}
        name="excludeFromArchive"
        onCheck={(checked) => {
          onValueChange({
            target: {
              name: 'options',
              value: {
                ...currentOptions,
                excludeFromArchive: getExcludeFromArchiveOptionValue(checked),
              },
            },
          });
        }}
        automationId="archive-visibility-toggle"
        aria-label={label}
        aria-describedby={helperTextId}
      />
      <span className="mailpoet-form-toggle-text">{label}</span>
      <p id={helperTextId} className="mailpoet-form-field-description">
        {__('Shown on pages using the MailPoet archive shortcode.', 'mailpoet')}
      </p>
    </>
  );
}

let fields: Array<Field> = [
  {
    name: 'email-header',
    label: null,
    tip: null,
    fields: [
      {
        name: 'subject',
        customLabel: __('Subject', 'mailpoet'),
        className: 'mailpoet-form-field-subject',
        placeholder: __('Type newsletter subject', 'mailpoet'),
        tooltip: __(
          "Be creative! It's the first thing that your subscribers see. Tempt them to open your email.",
          'mailpoet',
        ),
        type: 'text',
        validation: {
          'data-parsley-required': true,
          'data-parsley-required-message': __(
            'Please specify a subject',
            'mailpoet',
          ),
          maxLength: 250,
        },
      },
      {
        name: 'preheader',
        customLabel: __('Preview text', 'mailpoet'),
        className: 'mailpoet-form-field-preheader',
        placeholder: __(
          'Type preview text (usually displayed underneath the subject line in the inbox)',
          'mailpoet',
        ),
        tooltip: `${__(
          "This optional text will appear in your subscribers' inboxes, beside the subject line. Write something enticing!",
          'mailpoet',
        )} ${__(
          'Max length is 250 characters, however, we recommend 80 characters on a single line.',
          'mailpoet',
        )}`,
        type: 'textarea',
        validation: {
          maxLength: 250,
        },
      },
    ],
  },
  SendToFieldWithCount,
  {
    name: 'options',
    label: __('Schedule it', 'mailpoet'),
    type: 'reactComponent',
    component: withBoundary(StandardOptions),
  },
  {
    name: 'archive-visibility',
    label: __('Archive', 'mailpoet'),
    type: 'reactComponent',
    component: withBoundary(ArchiveVisibility),
  },
  {
    name: 'sender',
    label: __('Sender', 'mailpoet'),
    tip: __('Your name and email', 'mailpoet'),
    fields: [
      {
        name: 'sender_name',
        type: 'text',
        placeholder: __('John Doe', 'mailpoet'),
        validation: {
          'data-parsley-required': true,
        },
      },
      {
        name: 'sender_address',
        type: 'reactComponent',
        component: withBoundary(SenderField),
        placeholder: __('john.doe@email.com', 'mailpoet'),
        validation: {
          'data-parsley-required': true,
          'data-parsley-type': 'email',
        },
      },
    ],
  },
  GATrackingField,
  {
    name: 'reply-to',
    label: __('Reply-to', 'mailpoet'),
    tip: __(
      'When your subscribers reply to your emails, their emails will go to this address.',
      'mailpoet',
    ),
    inline: true,
    fields: [
      {
        name: 'reply_to_name',
        type: 'text',
        placeholder: __('John Doe', 'mailpoet'),
      },
      {
        name: 'reply_to_address',
        type: 'text',
        placeholder: __('john.doe@email.com', 'mailpoet'),
        validation: {
          'data-parsley-type': 'email',
        },
      },
    ],
  },
];

fields = Hooks.applyFilters('mailpoet_newsletters_3rd_step_fields', fields);

type SendButtonOptions = {
  value: string;
  disabled?: 'disabled';
};

export const StandardNewsletterFields = {
  getFields: (): typeof fields => fields,
  getSendButtonOptions: (
    newsletter: Partial<NewsLetter> = {},
  ): SendButtonOptions => {
    const isScheduled =
      typeof newsletter.options === 'object' &&
      newsletter.options?.isScheduled === '1' &&
      MailPoet.Date.isInFuture(newsletter.options?.scheduledAt, new Date());

    const options: SendButtonOptions = {
      value: isScheduled ? __('Schedule', 'mailpoet') : __('Send', 'mailpoet'),
    };

    if (
      newsletter.status === NewsletterStatus.Sent ||
      newsletter.status === NewsletterStatus.Sending
    ) {
      options.disabled = 'disabled';
    }

    return options;
  },
};
