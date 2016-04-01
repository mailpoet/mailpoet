define(
  [
    'underscore',
    'react',
    'react-router',
    'mailpoet',
    'newsletters/types/notification/scheduling.jsx',
    'newsletters/breadcrumb.jsx'
  ],
  function(
    _,
    React,
    Router,
    MailPoet,
    Scheduling,
    Breadcrumb
  ) {

    var field = {
      name: 'options',
      label: 'Periodicity',
      type: 'reactComponent',
      component: Scheduling,
    };

    var NewsletterNotification = React.createClass({
      mixins: [
        Router.History
      ],
      getInitialState: function() {
        return {
          options: {
            intervalType: 'daily',
            timeOfDay: 0,
            weekDay: 1,
            monthDay: 0,
            nthWeekDay: 1,
          }
        };
      },
      handleValueChange: function(event) {
        var state = this.state;
        state[event.target.name] = event.target.value;
        this.setState(state);
      },
      handleNext: function() {
        MailPoet.Ajax.post({
          endpoint: 'newsletters',
          action: 'create',
          data: _.extend({}, this.state, {
            type: 'notification'
          }),
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
        return (
          <div>
            <h1>{MailPoet.I18n.t('postNotificationNewsletterTypeTitle')}</h1>
            <Breadcrumb step="type" />

            <h3>{MailPoet.I18n.t('selectPeriodicity')}</h3>

            <Scheduling
              item={this.state}
              field={field}
              onValueChange={this.handleValueChange} />

            <p className="submit">
              <input
                className="button button-primary"
                type="button"
                onClick={ this.handleNext }
                value={MailPoet.I18n.t('next')} />
            </p>
          </div>
        );
      },
    });

    return NewsletterNotification;
  }
);
