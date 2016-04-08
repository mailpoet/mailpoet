define(
  [
    'underscore',
    'react',
    'react-router',
    'mailpoet',
    'form/fields/select.jsx',
    'form/fields/text.jsx',
    'newsletters/breadcrumb.jsx'
  ],
  function(
    _,
    React,
    Router,
    MailPoet,
    Select,
    Text,
    Breadcrumb
  ) {

    var availableRoles = window.mailpoet_roles || {};
    var availableSegments = window.mailpoet_segments || {};

    var events = {
      name: 'event',
      values: {
        'segment': MailPoet.I18n.t('onSubscriptionToList'),
        'user': MailPoet.I18n.t('onWordpressUserRegistration'),
      }
    };

    var availableSegmentValues = _.object(_.map(
      availableSegments,
      function(segment) {
        var name = segment.name;
        if (segment.subscribers > 0) {
          name += ' (%$1d)'.replace('%$1d', segment.subscribers);
        }
        return [segment.id, name];
      }
    ));
    var segmentField = {
      name: 'segment',
      values: availableSegmentValues,
    };

    var roleField = {
      name: 'role',
      values: availableRoles,
    };

    var afterTimeNumberField = {
      name: 'afterTimeNumber',
      size: 3,
    };

    var afterTimeTypeField = {
      name: 'afterTimeType',
      values: {
        'immediate': MailPoet.I18n.t('delayImmediately'),
        'hours': MailPoet.I18n.t('delayHoursAfter'),
        'days': MailPoet.I18n.t('delayDaysAfter'),
        'weeks': MailPoet.I18n.t('delayWeeksAfter'),
      }
    };

    var WelcomeScheduling = React.createClass({
      _getCurrentValue: function() {
        return this.props.item[this.props.field.name] || {};
      },
      handleValueChange: function(name, value) {
        var oldValue = this._getCurrentValue(),
            newValue = {};
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
            options: this.state,
          },
        }).done(function(response) {
          if(response.result && response.newsletter.id) {
            this.showTemplateSelection(response.newsletter.id);
          } else {
            if(response.errors.length > 0) {
              response.errors.map(function(error) {
                MailPoet.Notice.error(error);
              });
            }
          }
        }.bind(this));
      },
      showTemplateSelection: function(newsletterId) {
        this.history.pushState(null, `/template/${newsletterId}`);
      },
      render: function() {
        var value = this._getCurrentValue(),
            roleSegmentSelection, timeNumber;

        if (value.event === 'user') {
          roleSegmentSelection = (
            <Select
              field={roleField}
              item={this._getCurrentValue()}
              onValueChange={this.handleRoleChange} />
          );
        } else {
          roleSegmentSelection = (
            <Select
              field={segmentField}
              item={this._getCurrentValue()}
              onValueChange={this.handleSegmentChange} />
          );
        }
        if (value.afterTimeType !== 'immediate') {
          timeNumber = (
            <Text
              field={afterTimeNumberField}
              item={this._getCurrentValue()}
              onValueChange={this.handleAfterTimeNumberChange} />
          );
        }

        return (
          <div>
            <Select
              field={events}
              item={this._getCurrentValue()}
              onValueChange={this.handleEventChange} />

            {roleSegmentSelection}

            {timeNumber}

            <Select
              field={afterTimeTypeField}
              item={this._getCurrentValue()}
              onValueChange={this.handleAfterTimeTypeChange}/>
          </div>
        );
      },
    });

    return WelcomeScheduling;
  }
);

