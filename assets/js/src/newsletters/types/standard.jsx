define(
  [
    'react',
    'react-router',
    'mailpoet',
    'newsletters/breadcrumb.jsx'
  ],
  function (
    React,
    Router,
    MailPoet,
    Breadcrumb
  ) {

    var NewsletterStandard = React.createClass({
      contextTypes: {
        router: React.PropTypes.object.isRequired
      },
      showTemplateSelection: function (newsletterId) {
        this.context.router.push(`/template/${newsletterId}`);
      },
      componentDidMount: function () {
        // No options for this type, create a newsletter upon mounting
        MailPoet.Ajax.post({
          api_version: window.mailpoet_api_version,
          endpoint: 'newsletters',
          action: 'create',
          data: {
            type: 'standard'
          }
        }).done((response) => {
          this.showTemplateSelection(response.data.id);
        }).fail((response) => {
          if (response.errors.length > 0) {
            MailPoet.Notice.error(
              response.errors.map(function (error) { return error.message; }),
              { scroll: true }
            );
          }
        });
      },
      render: function () {
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
