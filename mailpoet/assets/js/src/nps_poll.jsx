import { useLayoutEffect } from 'react';
import MailPoet from 'mailpoet';
import ReactDOMServer from 'react-dom/server';
import satismeter from 'satismeter-loader';
import ReviewRequest from 'review_request.jsx';
import getTrackingData from 'analytics.js';

const useNpsPoll = () => {
  useLayoutEffect(() => {
    const showReviewRequestModal = () => {
      MailPoet.Modal.popup({
        width: 800,
        template: ReactDOMServer.renderToString(
          ReviewRequest({
            username:
              window.mailpoet_current_wp_user_firstname ||
              window.mailpoet_current_wp_user.user_login,
            reviewRequestIllustrationUrl:
              window.mailpoet_review_request_illustration_url,
            installedDaysAgo: window.mailpoet_installed_days_ago,
          }),
        ),
        onInit: () => {
          document
            .getElementById('mailpoet_review_request_not_now')
            .addEventListener('click', () => MailPoet.Modal.close());
        },
      });
    };

    const callSatismeter = (trackingData) => {
      const newUsersPollId = '6L479eVPXk7pBn6S';
      const oldUsersPollId = 'k0aJAsQAWI2ERyGv';
      const formPollId = 'EqOgKsgZd832Sz9w';
      let writeKey;
      if (window.mailpoet_display_nps_form) {
        writeKey = formPollId;
      } else if (window.mailpoet_is_new_user) {
        writeKey = newUsersPollId;
      } else {
        writeKey = oldUsersPollId;
      }
      satismeter({
        writeKey,
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
              showReviewRequestModal();
            }
          },
        },
      });
    };

    if (window.mailpoet_display_nps_poll && MailPoet.libs3rdPartyEnabled) {
      getTrackingData().then(({ data }) => callSatismeter(data));
    }
  }, []);

  return null;
};

const withNpsPoll = (Component) =>
  function useNpsPollWithComponent(props) {
    useNpsPoll();
    return <Component {...props} />;
  };

export default withNpsPoll;
