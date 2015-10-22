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
        'list': 'When someone subscribes to the list...',
        'user': 'When a new Wordrpess user is added to your site...',
      }
    };

    var availableSegmentValues = _.object(_.map(
      availableSegments,
      function(segment) {
        return [segment.id, segment.name];
      }
    ));
    var segmentField = {
      name: 'list',
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
        'immediate': 'immediately',
        'hours': 'hour(s) after',
        'days': 'day(s) after',
        'weeks': 'week(s) after',
      }
    };

    var NewsletterWelcome = React.createClass({
      mixins: [
        Router.History
      ],
      getInitialState: function() {
        return {
          event: 'segment',
          list: 1,
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
          list: event.target.value,
        });
      },
      handleRoleChange: function(event) {
        this.setState({
          role: event.target.value,
        })
      },
      handleAfterTimeNumberChange: function(event) {
        this.setState({
          afterTimeNumber: event.target.value,
        })
      },
      handleAfterTimeTypeChange: function(event) {
        this.setState({
          afterTimeType: event.target.value,
        })
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
          if(response.id !== undefined) {
            this.showTemplateSelection(response.id);
          } else {
            response.map(function(error) {
              MailPoet.Notice.error(error);
            });
          }
        }.bind(this));
      },
      showTemplateSelection: function(newsletterId) {
        this.history.pushState(null, `/template/${newsletterId}`);
      },
      render: function() {
        var roleListSelection, timeNumber;
        if (this.state.event === 'user') {
          roleListSelection = (
            <Select
              field={roleField}
              item={this.state}
              onValueChange={this.handleRoleChange} />
          );
        } else {
          roleListSelection = (
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
            <h1>Welcome email</h1>
            <Breadcrumb step="type" />

            <Select
              field={events}
              item={this.state}
              onValueChange={this.handleEventChange} />

            {roleListSelection}

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
                value="Next" />
            </p>
          </div>
        );
      },
    });

    return NewsletterWelcome;
  }
);
