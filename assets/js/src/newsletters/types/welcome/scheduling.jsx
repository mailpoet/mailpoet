import _ from 'underscore'
import React from 'react'
import MailPoet from 'mailpoet'
import Select from 'form/fields/select.jsx'
import Text from 'form/fields/text.jsx'
import {
  timeDelayValues,
  intervalValues
} from 'newsletters/scheduling/common.jsx'

const availableRoles = window.mailpoet_roles || {};
const availableSegments = _.filter(
  window.mailpoet_segments || [],
  function (segment) {
    return segment.type === 'default';
  }
);

const events = {
  name: 'event',
  values: {
    'segment': MailPoet.I18n.t('onSubscriptionToList'),
    'user': MailPoet.I18n.t('onWPUserRegistration'),
  }
};

const availableSegmentValues = _.object(_.map(
  availableSegments,
  function(segment) {
    let name = segment.name;
    if (segment.subscribers > 0) {
      name += ' (%$1s)'.replace('%$1s', parseInt(segment.subscribers).toLocaleString());
    }
    return [segment.id, name];
  }
));
const segmentField = {
  name: 'segment',
  values: availableSegmentValues,
  sortBy: (key, value) => value.toLowerCase()
};

const roleField = {
  name: 'role',
  values: availableRoles
};

const afterTimeNumberField = {
  name: 'afterTimeNumber',
  size: 3
};

const afterTimeTypeField = {
  name: 'afterTimeType',
  values: timeDelayValues
};

const WelcomeScheduling = React.createClass({
  contextTypes: {
    router: React.PropTypes.object.isRequired
  },
  _getCurrentValue: function() {
    return (this.props.item[this.props.field.name] || {});
  },
  handleValueChange: function(name, value) {
    const oldValue = this._getCurrentValue();
    let newValue = {};

    newValue[name] = value;

    return this.props.onValueChange({
      target: {
        name: this.props.field.name,
        value: _.extend({}, oldValue, newValue)
      }
    });
  },
  handleEventChange: function(event) {
    return this.handleValueChange(
      'event',
      event.target.value
    );
  },
  handleSegmentChange: function(event) {
    return this.handleValueChange(
      'segment',
      event.target.value
    );
  },
  handleRoleChange: function(event) {
    return this.handleValueChange(
      'role',
      event.target.value
    );
  },
  handleAfterTimeNumberChange: function(event) {
    return this.handleValueChange(
      'afterTimeNumber',
      event.target.value
    );
  },
  handleAfterTimeTypeChange: function(event) {
    return this.handleValueChange(
      'afterTimeType',
      event.target.value
    );
  },
  handleNext: function() {
    MailPoet.Ajax.post({
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type: 'welcome',
        options: this.state
      }
    }).done((response) => {
        this.showTemplateSelection(response.data.id);
      }).fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map(function(error) { return error.message; }),
            { scroll: true }
          );
        }
      });
  },
  showTemplateSelection: function(newsletterId) {
    this.context.router.push(`/template/${ newsletterId }`);
  },
  render: function() {
    const value = this._getCurrentValue();
    let roleSegmentSelection;
    let timeNumber;

    if (value.event === 'user') {
      roleSegmentSelection = (
        <Select
          field={ roleField }
          item={ this._getCurrentValue() }
          onValueChange={ this.handleRoleChange } />
      );
    } else {
      roleSegmentSelection = (
        <Select
          field={ segmentField }
          item={ this._getCurrentValue() }
          onValueChange={ this.handleSegmentChange } />
      );
    }
    if (value.afterTimeType !== 'immediate') {
      timeNumber = (
        <Text
          field={ afterTimeNumberField }
          item={ this._getCurrentValue() }
          onValueChange={ this.handleAfterTimeNumberChange } />
      );
    }

    return (
      <div>
        <Select
          field={ events }
          item={ this._getCurrentValue() }
          onValueChange={ this.handleEventChange } />

        { roleSegmentSelection }

        { timeNumber }

        <Select
          field={ afterTimeTypeField }
          item={ this._getCurrentValue() }
          onValueChange={ this.handleAfterTimeTypeChange } />
      </div>
    );
  },
});

module.exports = WelcomeScheduling;
