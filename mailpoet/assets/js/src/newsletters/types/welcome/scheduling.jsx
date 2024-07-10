import _ from 'underscore';
import { Component } from 'react';
import { __ } from '@wordpress/i18n';
import { FormFieldSelect as Select } from 'form/fields/select.jsx';
import { Selection } from 'form/fields/selection.jsx';
import { FormFieldText } from 'form/fields/text.jsx';
import { timeDelayValues } from 'newsletters/scheduling/common.jsx';
import PropTypes from 'prop-types';

const availableRoles = window.mailpoet_roles || {};
const availableSegments = _.filter(
  window.mailpoet_segments || [],
  (segment) => segment.type === 'default',
);

const events = {
  name: 'event',
  values: {
    segment: __('When someone subscribes to the list...', 'mailpoet'),
    user: __('When a new WordPress user is added to your site...', 'mailpoet'),
  },
};

const segmentField = {
  name: 'segment',
  placeholder: __('Select a list', 'mailpoet'),
  forceSelect2: true,
  values: availableSegments,
  getCount: (segment) => parseInt(segment.subscribers, 10).toLocaleString(),
  getLabel: (segment) => segment.name,
  getValue: (segment) => segment.id,
};

const roleField = {
  name: 'role',
  values: availableRoles,
};

const afterTimeNumberField = {
  name: 'afterTimeNumber',
  size: 3,
  validation: {
    'data-parsley-required': true,
    'data-parsley-errors-container': '.mailpoet-form-errors',
    'data-parsley-scheduled-at': __(
      'An email can only be scheduled up to 5 years in the future. Please choose a shorter period.',
      'mailpoet',
    ),
  },
};

const afterTimeTypeField = {
  name: 'afterTimeType',
  values: timeDelayValues,
};

export class WelcomeScheduling extends Component {
  getCurrentValue = () => this.props.item[this.props.field.name] || {};

  handleValueChange = (name, value) => {
    const oldValue = this.getCurrentValue();
    const newValue = {};

    let newFieldValue = value;
    if (name === 'afterTimeNumber') {
      newFieldValue = parseInt(value, 10);
      newFieldValue = Number.isNaN(newFieldValue) ? '' : newFieldValue;
    }
    newValue[name] = newFieldValue;

    return this.props.onValueChange({
      target: {
        name: this.props.field.name,
        value: _.extend({}, oldValue, newValue),
      },
    });
  };

  handleEventChange = (event) =>
    this.handleValueChange('event', event.target.value);

  handleSegmentChange = (event) =>
    this.handleValueChange('segment', event.target.value);

  handleRoleChange = (event) =>
    this.handleValueChange('role', event.target.value);

  handleAfterTimeNumberChange = (event) =>
    this.handleValueChange('afterTimeNumber', event.target.value);

  handleAfterTimeTypeChange = (event) =>
    this.handleValueChange('afterTimeType', event.target.value);

  render() {
    const value = this.getCurrentValue();
    let roleSegmentSelection;
    let timeNumber;

    if (value.event === 'user') {
      roleSegmentSelection = (
        <Select
          field={roleField}
          item={this.getCurrentValue()}
          onValueChange={this.handleRoleChange}
        />
      );
    } else {
      roleSegmentSelection = (
        <Selection
          field={segmentField}
          item={this.getCurrentValue()}
          onValueChange={this.handleSegmentChange}
        />
      );
    }
    if (value.afterTimeType !== 'immediate') {
      timeNumber = (
        <FormFieldText
          field={afterTimeNumberField}
          item={this.getCurrentValue()}
          onValueChange={this.handleAfterTimeNumberChange}
        />
      );
    }

    return (
      <div>
        <Select
          field={events}
          item={this.getCurrentValue()}
          onValueChange={this.handleEventChange}
        />
        <div className="mailpoet-gap" />

        {roleSegmentSelection}
        <div className="mailpoet-gap" />

        <div className="mailpoet-grid-column mailpoet-flex">
          {timeNumber}
          <Select
            field={afterTimeTypeField}
            item={this.getCurrentValue()}
            onValueChange={this.handleAfterTimeTypeChange}
          />
        </div>
        <div className="mailpoet-form-errors" />
        <div className="mailpoet-gap" />
      </div>
    );
  }
}

WelcomeScheduling.propTypes = {
  item: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  field: PropTypes.shape({
    name: PropTypes.string,
  }).isRequired,
  onValueChange: PropTypes.func.isRequired,
};
WelcomeScheduling.displayName = 'WelcomeScheduling';
