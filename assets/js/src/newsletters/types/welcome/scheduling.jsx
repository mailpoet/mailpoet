import _ from 'underscore';
import React from 'react';
import MailPoet from 'mailpoet';
import Select from 'form/fields/select.jsx';
import Text from 'form/fields/text.jsx';
import { timeDelayValues } from 'newsletters/scheduling/common.jsx';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';

const availableRoles = window.mailpoet_roles || {};
const availableSegments = _.filter(
  window.mailpoet_segments || [],
  (segment) => segment.type === 'default'
);

const events = {
  name: 'event',
  values: {
    segment: MailPoet.I18n.t('onSubscriptionToList'),
    user: MailPoet.I18n.t('onWPUserRegistration'),
  },
};

const availableSegmentValues = _.object(_.map(
  availableSegments,
  (segment) => {
    const name = `${segment.name} (${parseInt(segment.subscribers, 10).toLocaleString()})`;
    return [segment.id, name];
  }
));
const segmentField = {
  name: 'segment',
  values: availableSegmentValues,
  sortBy: (key, value) => value.toLowerCase(),
};

const roleField = {
  name: 'role',
  values: availableRoles,
};

const afterTimeNumberField = {
  name: 'afterTimeNumber',
  size: 3,
};

const afterTimeTypeField = {
  name: 'afterTimeType',
  values: timeDelayValues,
};

class WelcomeScheduling extends React.Component {
  getCurrentValue = () => this.props.item[this.props.field.name] || {};

  handleValueChange = (name, value) => {
    const oldValue = this.getCurrentValue();
    const newValue = {};

    newValue[name] = value;

    return this.props.onValueChange({
      target: {
        name: this.props.field.name,
        value: _.extend({}, oldValue, newValue),
      },
    });
  };

  handleEventChange = (event) => this.handleValueChange('event', event.target.value);

  handleSegmentChange = (event) => this.handleValueChange('segment', event.target.value);

  handleRoleChange = (event) => this.handleValueChange('role', event.target.value);

  handleAfterTimeNumberChange = (event) => this.handleValueChange('afterTimeNumber', event.target.value);

  handleAfterTimeTypeChange = (event) => this.handleValueChange('afterTimeType', event.target.value);

  handleNext = () => {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type: 'welcome',
        options: this.state,
      },
    }).done((response) => {
      this.showTemplateSelection(response.data.id);
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => error.message),
          { scroll: true }
        );
      }
    });
  };

  showTemplateSelection = (newsletterId) => {
    this.props.history.push(`/template/${newsletterId}`);
  };

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
        <Select
          field={segmentField}
          item={this.getCurrentValue()}
          onValueChange={this.handleSegmentChange}
        />
      );
    }
    if (value.afterTimeType !== 'immediate') {
      timeNumber = (
        <Text
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

        { roleSegmentSelection }
        <div className="mailpoet-gap" />

        { timeNumber }
        { timeNumber && <div className="mailpoet-gap" /> }

        <Select
          field={afterTimeTypeField}
          item={this.getCurrentValue()}
          onValueChange={this.handleAfterTimeTypeChange}
        />
        <div className="mailpoet-gap" />
      </div>
    );
  }
}

WelcomeScheduling.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
  item: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  field: PropTypes.shape({
    name: PropTypes.string,
  }).isRequired,
  onValueChange: PropTypes.func.isRequired,
};

export default withRouter(WelcomeScheduling);
