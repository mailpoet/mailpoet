import React from 'react';
import MailPoet from 'mailpoet';
import ReactDOMServer from 'react-dom/server';
import ReviewRequest from 'review_request.jsx';
import getTrackingData from 'analytics.js';

const withNpsPoll = function withNpsPoll(Component) {
  return class extends React.Component {
    constructor(props) {
      super(props);
      this.state = {
        pollShown: false,
        modalShown: false,
      };
      this.displayPoll = this.displayPoll.bind(this);
      this.showReviewRequestModal = this.showReviewRequestModal.bind(this);
      this.callSatismeter = this.callSatismeter.bind(this);
    }

    showReviewRequestModal() {
      if (this.state.modalShown) {
        return;
      }
      this.setState({ modalShown: true });
      MailPoet.Modal.popup({
        width: 800,
        template: ReactDOMServer.renderToString(
          ReviewRequest({
            username: window.mailpoet_current_wp_user_firstname
              || window.mailpoet_current_wp_user.user_login,
            reviewRequestIllustrationUrl: window.mailpoet_review_request_illustration_url,
            installedDaysAgo: window.mailpoet_installed_days_ago,
          })
        ),
        onInit: () => {
          document
            .getElementById('mailpoet_review_request_not_now')
            .addEventListener('click', () => MailPoet.Modal.close());
        },
      });
    }

    displayPoll() {
      if (!this.state.pollShown) {
        this.setState({ pollShown: true });
        getTrackingData().then(this.callSatismeter);
      }
    }

    callSatismeter(trackingData) {
      const newUsersPollId = '6L479eVPXk7pBn6S';
      const oldUsersPollId = 'k0aJAsQAWI2ERyGv';
      window.satismeter({
        writeKey: window.mailpoet_is_new_user ? newUsersPollId : oldUsersPollId,
        userId: window.mailpoet_current_wp_user.ID + window.mailpoet_site_url,
        traits: {
          name: window.mailpoet_current_wp_user.user_nicename,
          email: window.mailpoet_current_wp_user.user_email,
          mailpoetVersion: window.mailpoet_version,
          mailpoetPremiumIsActive: window.mailpoet_premium_active,
          createdAt: trackingData.installedAtIso,
          newslettersSent: trackingData.newslettersSent,
          welcomeEmails: trackingData.welcomeEmails,
          postnotificationEmails: trackingData.postnotificationEmails,
          woocommerceEmails: trackingData.woocommerceEmails,
          subscribers: trackingData.subscribers,
          lists: trackingData.lists,
          sendingMethod: trackingData.sendingMethod,
          woocommerceIsInstalled: trackingData.woocommerceIsInstalled,
        },
        events: {
          submit: (response) => {
            if (response.rating >= 9 && response.completed) {
              this.showReviewRequestModal();
            }
          },
        },
      });
    }

    render() {
      if (!window.mailpoet_display_nps_poll) {
        return null;
      }
      if (window.satismeter) {
        this.displayPoll();
      } else {
        const s = document.createElement('script');
        s.type = 'text/javascript';
        s.src = 'https://app.satismeter.com/satismeter.js';
        s.onload = () => this.displayPoll();
        document.getElementsByTagName('body')[0].appendChild(s);
      }
      return (<Component {...this.props} />);
    }
  };
};

export default withNpsPoll;
