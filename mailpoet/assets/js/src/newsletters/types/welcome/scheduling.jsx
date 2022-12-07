import _ from 'underscore';
import { Component } from 'react';
import { MailPoet } from 'mailpoet';
import { FormFieldSelect as Select } from 'form/fields/select.jsx';
import { Selection } from 'form/fields/selection.jsx';
import { FormFieldText } from 'form/fields/text.jsx';
import { timeDelayValues } from 'newsletters/scheduling/common.jsx';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';

const availableRoles = window.mailpoet_roles || {};
const availableSegments = _.filter(
  window.mailpoet_segments || [],
  (segment) => segment.type === 'default',
);

const events = {
  name: 'event',
  values: {
    segment: MailPoet.I18n.t('onSubscriptionToList'),
    user: MailPoet.I18n.t('onWPUserRegistration'),
  },
};

const segmentField = {
  name: 'segment',
  placeholder: MailPoet.I18n.t('selectSegmentPlaceholder'),
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
    'data-parsley-scheduled-at': MailPoet.I18n.t(
      'emailCanBeScheduledUpToFiveYears',
    ),
  },
};

const afterTimeTypeField = {
  name: 'afterTimeType',
  values: timeDelayValues,
};

class WelcomeSchedulingComponent extends Component {
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

WelcomeSchedulingComponent.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
  item: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  field: PropTypes.shape({
    name: PropTypes.string,
  }).isRequired,
  onValueChange: PropTypes.func.isRequired,
};
WelcomeSchedulingComponent.displayName = 'WelcomeScheduling';
export const WelcomeScheduling = withRouter(WelcomeSchedulingComponent);
