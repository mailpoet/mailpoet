define(
  [
    'underscore',
    'react',
    'react-router',
    'mailpoet',
    'form/form.jsx',
    'form/fields/select.jsx',
    'form/fields/selection.jsx',
    'form/fields/text.jsx',
    'newsletters/breadcrumb.jsx'
  ],
  function(
    _,
    React,
    Router,
    MailPoet,
    Form,
    Select,
    Selection,
    Text,
    Breadcrumb
  ) {

    var availableRoles = window.mailpoet_roles || {};
    var availableSegments = window.mailpoet_segments || {};

    var events = {
      name: 'event',
      values: {
        'segment': MailPoetI18n.whenSomeoneSubscribesToList,
        'user': MailPoetI18n.whenWordpressUserIsAdded,
      }
    };

    var availableSegmentValues = _.object(_.map(
      availableSegments,
      function(segment) {
        return [segment.id, segment.name];
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
        'immediate': MailPoetI18n.delayImmediately,
        'hours': MailPoetI18n.delayHoursAfter,
        'days': MailPoetI18n.delayDaysAfter,
        'weeks': MailPoetI18n.delayWeeksAfter,
      }
    };

    var NewsletterWelcome = React.createClass({
      mixins: [
        Router.History
      ],
      getInitialState: function() {
        return {
          event: 'segment',
          segment: 1,
          role: 'subscriber',
          afterTimeNumber: 1,
          afterTimeType: 'immediate',
        };
      },
      handleEventChange: function(event) {
        this.setState({
          event: event.target.value,
        });
      },
      handleSegmentChange: function(event) {
        this.setState({
          segment: event.target.value,
        });
      },
      handleRoleChange: function(event) {
        this.setState({
          role: event.target.value,
        });
      },
      handleAfterTimeNumberChange: function(event) {
        this.setState({
          afterTimeNumber: event.target.value,
        });
      },
      handleAfterTimeTypeChange: function(event) {
        this.setState({
          afterTimeType: event.target.value,
        });
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
        var roleSegmentSelection, timeNumber;
        if (this.state.event === 'user') {
          roleSegmentSelection = (
            <Select
              field={roleField}
              item={this.state}
              onValueChange={this.handleRoleChange} />
          );
        } else {
          roleSegmentSelection = (
            <Select
              field={segmentField}
              item={this.state}
              onValueChange={this.handleSegmentChange} />
          );
        }
        if (this.state.afterTimeType !== 'immediate') {
          timeNumber = (
            <Text
              field={afterTimeNumberField}
              item={this.state}
              onValueChange={this.handleAfterTimeNumberChange} />
          );
        }
        return (
          <div>
            <h1>{MailPoetI18n.welcomeEmail}</h1>
            <Breadcrumb step="type" />

            <Select
              field={events}
              item={this.state}
              onValueChange={this.handleEventChange} />

            {roleSegmentSelection}

            {timeNumber}

            <Select
              field={afterTimeTypeField}
              item={this.state}
              onValueChange={this.handleAfterTimeTypeChange}/>

            <p className="submit">
              <input
                className="button button-primary"
                type="button"
                onClick={ this.handleNext }
                value={MailPoetI18n.next} />
            </p>
          </div>
        );
      },
    });

    return NewsletterWelcome;
  }
);
