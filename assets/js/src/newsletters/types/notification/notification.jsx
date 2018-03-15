define(
  [
    'underscore',
    'react',
    'react-router',
    'mailpoet',
    'newsletters/types/notification/scheduling.jsx',
    'newsletters/breadcrumb.jsx',
  ],
  (
    _,
    React,
    Router,
    MailPoet,
    Scheduling,
    Breadcrumb
  ) => {
    const field = {
      name: 'options',
      type: 'reactComponent',
      component: Scheduling,
    };

    const NewsletterNotification = React.createClass({
      contextTypes: {
        router: React.PropTypes.object.isRequired,
      },
      getInitialState: function getInitialState() {
        return {
          options: {
            intervalType: 'daily',
            timeOfDay: 0,
            weekDay: 1,
            monthDay: 0,
            nthWeekDay: 1,
          },
        };
      },
      handleValueChange: function handleValueChange(event) {
        const state = this.state;
        state[event.target.name] = event.target.value;
        this.setState(state);
      },
      handleNext: function handleNext() {
        MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
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
              response.errors.map(error => error.message),
              { scroll: true }
            );
          }
        });
      },
      showTemplateSelection: function showTemplateSelection(newsletterId) {
        this.context.router.push(`/template/${newsletterId}`);
      },
      render: function render() {
        return (
          <div>
            <h1>{MailPoet.I18n.t('postNotificationNewsletterTypeTitle')}</h1>
            <Breadcrumb step="type" />

            <h3>{MailPoet.I18n.t('selectFrequency')}</h3>

            <Scheduling
              item={this.state}
              field={field}
              onValueChange={this.handleValueChange}
            />

            <p className="submit">
              <input
                className="button button-primary"
                type="button"
                onClick={this.handleNext}
                value={MailPoet.I18n.t('next')}
              />
            </p>
          </div>
        );
      },
    });

    return NewsletterNotification;
  }
);
