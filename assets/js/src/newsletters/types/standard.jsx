define(
  [
    'react',
    'react-router',
    'mailpoet',
    'newsletters/breadcrumb.jsx'
  ],
  function(
    React,
    Router,
    MailPoet,
    Breadcrumb
  ) {

    var NewsletterStandard = React.createClass({
      mixins: [
        Router.History
      ],
      showTemplateSelection: function(newsletterId) {
        this.history.pushState(null, `/template/${newsletterId}`);
      },
      componentDidMount: function() {
        // No options for this type, create a newsletter upon mounting
        MailPoet.Ajax.post({
          endpoint: 'newsletters',
          action: 'create',
          data: {
            type: 'standard',
          }
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
      render: function() {
        return (
          <div>
            <h1>{MailPoet.I18n.t('regularNewsletterTypeTitle')}</h1>
            <Breadcrumb step="type" />
          </div>
        );
      },
    });

    return NewsletterStandard;
  }
);
