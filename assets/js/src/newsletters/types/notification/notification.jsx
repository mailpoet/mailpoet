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
      type: 'reactComponent',
      component: Scheduling,
    };

    var NewsletterNotification = React.createClass({
      contextTypes: {
        router: React.PropTypes.object.isRequired
      },
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
            type: 'notification',
            subject: MailPoet.I18n.t('draftNewsletterTitle'),
          }),
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
        this.context.router.push(`/template/${newsletterId}`);
      },
      render: function() {
        return (
          <div>
            <h1>{MailPoet.I18n.t('postNotificationNewsletterTypeTitle')}</h1>
            <Breadcrumb step="type" />

            <h3>{MailPoet.I18n.t('selectFrequency')}</h3>

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
