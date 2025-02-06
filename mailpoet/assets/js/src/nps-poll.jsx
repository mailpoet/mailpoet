import { useLayoutEffect } from 'react';
import { MailPoet } from 'mailpoet';
import ReactDOMServer from 'react-dom/server';
import satismeter from 'satismeter-loader';
import { ReviewRequest } from 'review-request';
import { getTrackingData } from 'analytics.js';

export const initializeSatismeterSurvey = (writeId = null) => {
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

  const callSatismeter = (trackingData, customWriteId) => {
    const newUsersPollId = '6L479eVPXk7pBn6S';
    const oldUsersPollId = 'k0aJAsQAWI2ERyGv';
    const formPollId = 'EqOgKsgZd832Sz9w';
    let writeKey;
    if (customWriteId) {
      writeKey = customWriteId;
    } else if (window.mailpoet_display_nps_form) {
      writeKey = formPollId;
    } else if (window.mailpoet_is_new_user) {
      writeKey = newUsersPollId;
    } else {
      writeKey = oldUsersPollId;
    }
    const traits = {
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
      woocommerceVersion: trackingData.woocommerceVersion,
      WordPressVersion: trackingData.WordPressVersion,
      blockTheme: trackingData.blockTheme,
      themeVersion: trackingData.themeVersion,
      theme: trackingData.theme,
    };
    if (trackingData.gutenbergVersion) {
      traits.gutenbergVersion = trackingData.gutenbergVersion;
    }
    if (trackingData.wooCommerceVersion) {
      traits.wooCommerceVersion = trackingData.wooCommerceVersion;
    }
    satismeter({
      writeKey,
      userId: window.mailpoet_current_wp_user.ID + window.mailpoet_site_url,
      traits,
      events: {
        submit: (response) => {
          if (response.rating >= 9 && response.completed) {
            showReviewRequestModal();
          }
        },
      },
    });
  };

  return new Promise((resolve, reject) => {
    if (
      window.mailpoet_display_nps_poll &&
      window.mailpoet_3rd_party_libs_enabled
    ) {
      getTrackingData().then(({ data }) => {
        callSatismeter(data, writeId);
        resolve();
      });
    } else {
      reject();
    }
  });
};

const useNpsPoll = () => {
  useLayoutEffect(() => {
    // Survey may fail to initialize when 3rd party libs are not allowed. It is OK. We don't need to react.
    initializeSatismeterSurvey().catch(() => {});
  }, []);

  return null;
};

export const withNpsPoll = (Component) =>
  function useNpsPollWithComponent(props) {
    useNpsPoll();
    return <Component {...props} />;
  };
