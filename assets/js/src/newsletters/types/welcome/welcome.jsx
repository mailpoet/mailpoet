define(
  [
    'underscore',
    'react',
    'react-router',
    'mailpoet',
    'newsletters/types/welcome/scheduling.jsx',
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
      label: 'Event',
      type: 'reactComponent',
      component: Scheduling,
    };

    var availableSegments = window.mailpoet_segments || {},
        defaultSegment = 1;

    if (_.size(availableSegments) > 0) {
      defaultSegment = _.first(availableSegments).id;
    }

    var NewsletterWelcome = React.createClass({
      contextTypes: {
        router: React.PropTypes.object.isRequired
      },
      getInitialState: function() {
        return {
          options: {
            event: 'segment',
            segment: defaultSegment,
            role: 'subscriber',
            afterTimeNumber: 1,
            afterTimeType: 'immediate',
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
            type: 'welcome',
            subject: MailPoet.I18n.t('draftNewsletterTitle'),
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
        this.context.router.push(`/template/${newsletterId}`);
      },
      render: function() {
        return (
          <div>
            <h1>{MailPoet.I18n.t('welcomeNewsletterTypeTitle')}</h1>
            <Breadcrumb step="type" />

            <h3>{MailPoet.I18n.t('selectEventToSendWelcomeEmail')}</h3>

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

    return NewsletterWelcome;
  }
);
