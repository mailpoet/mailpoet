import { ChangeEvent, Component } from 'react';
import { MailPoet } from 'mailpoet';
import { Hooks } from 'wp-js-hooks';
import Moment from 'moment';

import { DateTime } from 'newsletters/send/date_time.jsx';
import { SenderField } from 'newsletters/send/sender_address_field.jsx';
import { GATrackingField } from 'newsletters/send/ga_tracking';
import { Toggle } from 'common/form/toggle/toggle';
import { withBoundary } from 'common';
import { NewsLetter, NewsletterStatus } from '../models';
import { Field } from '../../form/types';

const currentTime = window.mailpoet_current_time || '00:00';
const tomorrowDateTime = `${window.mailpoet_tomorrow_date} 08:00:00`;
const timeOfDayItems = window.mailpoet_schedule_time_of_day;
const dateDisplayFormat = window.mailpoet_date_display_format;
const dateStorageFormat = window.mailpoet_date_storage_format;

type StandardSchedulingProps = {
  item?: NewsLetter;
  onValueChange: (targetWrap: {
    target: {
      name: string;
      value: string;
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
    'data-parsley-required-message': MailPoet.I18n.t('noScheduledDateError'),
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
            {MailPoet.I18n.t('websiteTimeIs')} {currentTime}
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

let fields: Array<Field> = [
  {
    name: 'email-header',
    label: null,
    tip: null,
    fields: [
      {
        name: 'subject',
        customLabel: MailPoet.I18n.t('subjectLabel'),
        className: 'mailpoet-form-field-subject',
        placeholder: MailPoet.I18n.t('subjectLine'),
        tooltip: MailPoet.I18n.t('subjectLineTip'),
        type: 'text',
        validation: {
          'data-parsley-required': true,
          'data-parsley-required-message': MailPoet.I18n.t(
            'emptySubjectLineError',
          ),
          maxLength: 250,
        },
      },
      {
        name: 'preheader',
        customLabel: MailPoet.I18n.t('preheaderLabel'),
        className: 'mailpoet-form-field-preheader',
        placeholder: MailPoet.I18n.t('preheaderLine'),
        tooltip: `${MailPoet.I18n.t('preheaderLineTip1')} ${MailPoet.I18n.t(
          'preheaderLineTip2',
        )}`,
        type: 'textarea',
        validation: {
          maxLength: 250,
        },
      },
    ],
  },
  {
    name: 'segments',
    label: MailPoet.I18n.t('segments'),
    tip: MailPoet.I18n.t('segmentsTip'),
    type: 'selection',
    placeholder: MailPoet.I18n.t('selectSegmentPlaceholder'),
    id: 'mailpoet_segments',
    api_version: window.mailpoet_api_version,
    endpoint: 'segments',
    multiple: true,
    filter: function filter(segment: { deleted_at: string }): boolean {
      return !segment.deleted_at;
    },
    getLabel: function getLabel(segment: { name: string }): string {
      return segment.name;
    },
    getCount: function getCount(segment: { subscribers: string }): string {
      return parseInt(segment.subscribers, 10).toLocaleString();
    },
    transformChangedValue: function transformChangedValue(
      segmentIds: string[],
    ): unknown[] {
      const allSegments = this.getItems() || [];
      return segmentIds.map((id) =>
        allSegments.find((segment) => segment.id === id),
      );
    },
    validation: {
      'data-parsley-required': true,
      'data-parsley-required-message': MailPoet.I18n.t(
        'noSegmentsSelectedError',
      ),
      'data-parsley-segments-with-subscribers': MailPoet.I18n.t(
        'noSegmentWithSubscribersSelectedError',
      ),
    },
  },
  {
    name: 'options',
    label: MailPoet.I18n.t('scheduleIt'),
    type: 'reactComponent',
    component: withBoundary(StandardScheduling),
  },
  {
    name: 'sender',
    label: MailPoet.I18n.t('sender'),
    tip: MailPoet.I18n.t('senderTip'),
    fields: [
      {
        name: 'sender_name',
        type: 'text',
        placeholder: MailPoet.I18n.t('senderNamePlaceholder'),
        validation: {
          'data-parsley-required': true,
        },
      },
      {
        name: 'sender_address',
        type: 'reactComponent',
        component: withBoundary(SenderField),
        placeholder: MailPoet.I18n.t('senderAddressPlaceholder'),
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
    label: MailPoet.I18n.t('replyTo'),
    tip: MailPoet.I18n.t('replyToTip'),
    inline: true,
    fields: [
      {
        name: 'reply_to_name',
        type: 'text',
        placeholder: MailPoet.I18n.t('replyToNamePlaceholder'),
      },
      {
        name: 'reply_to_address',
        type: 'text',
        placeholder: MailPoet.I18n.t('replyToAddressPlaceholder'),
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
    const currentDateTime = Moment(window.mailpoet_current_date_time);
    const isScheduled =
      typeof newsletter.options === 'object' &&
      newsletter.options?.isScheduled === '1' &&
      MailPoet.Date.isInFuture(
        newsletter.options?.scheduledAt,
        currentDateTime,
      );

    const options: SendButtonOptions = {
      value: isScheduled
        ? MailPoet.I18n.t('schedule')
        : MailPoet.I18n.t('send'),
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
