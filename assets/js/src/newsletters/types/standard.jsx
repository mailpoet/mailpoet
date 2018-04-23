import React from 'react';
import MailPoet from 'mailpoet';
import Breadcrumb from 'newsletters/breadcrumb.jsx';

const NewsletterStandard = React.createClass({
  contextTypes: {
    router: React.PropTypes.object.isRequired,
  },
  showTemplateSelection: function showTemplateSelection(newsletterId) {
    this.context.router.push(`/template/${newsletterId}`);
  },
  componentDidMount: function componentDidMount() {
    // No options for this type, create a newsletter upon mounting
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type: 'standard',
      },
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
  render: function render() {
    return (
      <div>
        <h1>{MailPoet.I18n.t('regularNewsletterTypeTitle')}</h1>
        <Breadcrumb step="type" />
      </div>
    );
  },
});

module.exports = NewsletterStandard;

