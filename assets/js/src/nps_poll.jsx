import MailPoet from 'mailpoet';
import ReactDOMServer from 'react-dom/server';
import ReviewRequest from 'review_request.jsx';

const showReviewRequestModal = () => MailPoet.Modal.popup({
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

function displayPoll() {
  if (
    (window.mailpoet_display_nps_poll || true)
    && window.satismeter
    && window.mailpoet_installed_at_isoFormat
    && window.mailpoet_analytics_tracking_data
  ) {
    const newUsersPollId = '6L479eVPXk7pBn6S';
    const oldUsersPollId = 'k0aJAsQAWI2ERyGv';
    window.satismeter({
      forceSurvey: true,
      writeKey: window.mailpoet_is_new_user ? newUsersPollId : oldUsersPollId,
      userId: window.mailpoet_current_wp_user.ID + window.mailpoet_site_url,
      traits: {
        name: window.mailpoet_current_wp_user.user_nicename,
        email: window.mailpoet_current_wp_user.user_email,
        createdAt: window.mailpoet_installed_at_isoFormat,
        mailpoetVersion: window.mailpoet_version,
        mailpoetPremiumIsActive: window.mailpoet_premium_active,
        newslettersSent: window.mailpoet_analytics_tracking_data.newslettersSent,
        welcomeEmails: window.mailpoet_analytics_tracking_data.welcomeEmails,
        postnotificationEmails: window.mailpoet_analytics_tracking_data.postnotificationEmails,
        woocommerceEmails: window.mailpoet_analytics_tracking_data.woocommerceEmails,
        subscribers: window.mailpoet_analytics_tracking_data.subscribers,
        lists: window.mailpoet_analytics_tracking_data.lists,
        sendingMethod: window.mailpoet_analytics_tracking_data.sendingMethod,
        woocommerceIsInstalled: window.mailpoet_analytics_tracking_data.woocommerceIsInstalled,
      },
      events: {
        submit: (response) => {
          if (response.rating >= 9 && response.completed) {
            showReviewRequestModal();
          }
        },
      },
    });
  }
}

setImmediate(displayPoll);
