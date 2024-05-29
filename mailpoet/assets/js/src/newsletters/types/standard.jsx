import { Component } from 'react';
import PropTypes from 'prop-types';

import { ListingHeadingStepsRoute } from 'newsletters/listings/heading-steps-route';
import { MailPoet } from 'mailpoet';
import { withRouter } from 'react-router-dom';
import { GlobalContext } from 'context';
import { __ } from '@wordpress/i18n';

class NewsletterStandardComponent extends Component {
  componentDidMount() {
    // No options for this type, create a newsletter upon mounting
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type: 'standard',
        subject: __('Subject', 'mailpoet'),
      },
    })
      .done((response) => {
        if (
          window.location.search.includes(
            'loadedvia=woo_multichannel_dashboard',
          )
        ) {
          window.MailPoet.trackEvent(
            'MailPoet - WooCommerce Multichannel Marketing dashboard > Newsletter template selection page',
            {
              'WooCommerce version': window.mailpoet_woocommerce_version,
            },
          );
        }
        this.showTemplateSelection(response.data.id);
      })
      .fail((response) => {
        if (response.errors.length > 0) {
          this.context.notices.apiError(response, { scroll: true });
        }
      });
  }

  showTemplateSelection = (newsletterId) => {
    this.props.history.push(`/template/${newsletterId}`);
  };

  render() {
    return (
      <div>
        <ListingHeadingStepsRoute
          emailType="standard"
          automationId="standard_newsletter_creation_heading"
        />
      </div>
    );
  }
}

NewsletterStandardComponent.contextType = GlobalContext;

NewsletterStandardComponent.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

NewsletterStandardComponent.displayName = 'NewsletterStandard';

export const NewsletterTypeStandard = withRouter(NewsletterStandardComponent);
