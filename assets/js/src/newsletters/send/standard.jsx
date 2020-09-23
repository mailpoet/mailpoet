import React from 'react';
import _ from 'underscore';
import MailPoet from 'mailpoet';
import Hooks from 'wp-js-hooks';
import PropTypes from 'prop-types';

import DateTime from 'newsletters/send/date_time.jsx';
import SenderField from 'newsletters/send/sender_address_field.jsx';
import GATrackingField from 'newsletters/send/ga_tracking.jsx';
import Toggle from 'common/form/toggle/toggle';

const currentTime = window.mailpoet_current_time || '00:00';
const defaultDateTime = `${window.mailpoet_current_date} 00:00:00`;
const timeOfDayItems = window.mailpoet_schedule_time_of_day;
const dateDisplayFormat = window.mailpoet_date_display_format;
const dateStorageFormat = window.mailpoet_date_storage_format;

class StandardScheduling extends React.Component {
  getCurrentValue = () => {
    const schedulingOptions = {
      isScheduled: '0',
      scheduledAt: defaultDateTime,
    };
    return _.defaults(
      this.props.item[this.props.field.name] || {},
      schedulingOptions
    );
  };

  getDateValidation = () => ({
    'data-parsley-required': true,
    'data-parsley-required-message': MailPoet.I18n.t('noScheduledDateError'),
    'data-parsley-errors-container': '#mailpoet_scheduling',
  });

  isScheduled = () => this.getCurrentValue().isScheduled === '1';

  handleCheckboxChange = (checked, event) => {
    const changeEvent = event;
    changeEvent.target.value = event.target.checked ? '1' : '0';
    return this.handleValueChange(changeEvent);
  };

  handleValueChange = (event) => {
    const oldValue = this.getCurrentValue();
    const newValue = {};
    newValue[event.target.name] = event.target.value;

    return this.props.onValueChange({
      target: {
        name: this.props.field.name,
        value: _.extend({}, oldValue, newValue),
      },
    });
  };

  render() {
    let schedulingOptions;

    if (this.isScheduled()) {
      schedulingOptions = (
        <>
          <span className="mailpoet-form-schedule-time">
            {MailPoet.I18n.t('websiteTimeIs')}
            {' '}
            {currentTime}
          </span>
          <div className="mailpoet-gap" />
          <div id="mailpoet_scheduling">
            <DateTime
              name="scheduledAt"
              value={this.getCurrentValue().scheduledAt}
              onChange={this.handleValueChange}
              disabled={this.props.field.disabled}
              dateValidation={this.getDateValidation()}
              defaultDateTime={defaultDateTime}
              timeOfDayItems={timeOfDayItems}
              dateDisplayFormat={dateDisplayFormat}
              dateStorageFormat={dateStorageFormat}
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

StandardScheduling.propTypes = {
  item: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  field: PropTypes.shape({
    name: PropTypes.string.isRequired,
    disabled: PropTypes.bool,
  }).isRequired,
  onValueChange: PropTypes.func.isRequired,
};

StandardScheduling.defaultProps = {
  item: {},
};

let fields = [
  {
    name: 'email-header',
    label: null,
    tip: null,
    fields: [
      {
        name: 'subject',
        customLabel: MailPoet.I18n.t('subjectLabel'),
        class: 'mailpoet-form-field-subject',
        placeholder: MailPoet.I18n.t('subjectLine'),
        tip: MailPoet.I18n.t('subjectLineTip'),
        type: 'text',
        validation: {
          'data-parsley-required': true,
          'data-parsley-required-message': MailPoet.I18n.t('emptySubjectLineError'),
        },
      },
      {
        name: 'preheader',
        customLabel: MailPoet.I18n.t('preheaderLabel'),
        class: 'mailpoet-form-field-preheader',
        placeholder: MailPoet.I18n.t('preheaderLine'),
        tip: `${MailPoet.I18n.t('preheaderLineTip1')} ${MailPoet.I18n.t('preheaderLineTip2')}`,
        type: 'textarea',
        validation: {
          'data-parsley-maxlength': 250,
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
    filter: function filter(segment) {
      return !segment.deleted_at;
    },
    getLabel: function getLabel(segment) {
      return segment.name;
    },
    getCount: function getCount(segment) {
      return parseInt(segment.subscribers, 10).toLocaleString();
    },
    transformChangedValue: function transformChangedValue(segmentIds) {
      const allSegments = this.getItems();
      return _.map(segmentIds, (id) => _.find(allSegments, (segment) => segment.id === id));
    },
    validation: {
      'data-parsley-required': true,
      'data-parsley-required-message': MailPoet.I18n.t('noSegmentsSelectedError'),
    },
  },
  {
    name: 'options',
    label: MailPoet.I18n.t('scheduleIt'),
    type: 'reactComponent',
    component: StandardScheduling,
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
        component: SenderField,
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

export default {
  getFields: function getFields() {
    return fields;
  },
  getSendButtonOptions: function getSendButtonOptions(newsletter) {
    const newsletterOptions = newsletter || {};

    const isScheduled = (
      typeof newsletterOptions.options === 'object'
      && newsletterOptions.options.isScheduled === '1'
    );
    const options = {
      value: (isScheduled
        ? MailPoet.I18n.t('schedule')
        : MailPoet.I18n.t('send')),
    };

    if (newsletterOptions.status === 'sent'
        || newsletterOptions.status === 'sending') {
      options.disabled = 'disabled';
    }

    return options;
  },
};
