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
    window.mailpoet_display_nps_poll
    && window.satismeter
    && window.mailpoet_installed_at_isoFormat
  ) {
    const newUsersPollId = '6L479eVPXk7pBn6S';
    const oldUsersPollId = 'k0aJAsQAWI2ERyGv';
    window.satismeter({
      writeKey: window.mailpoet_is_new_user ? newUsersPollId : oldUsersPollId,
      userId: window.mailpoet_current_wp_user.ID + window.mailpoet_site_url,
      traits: {
        name: window.mailpoet_current_wp_user.user_nicename,
        email: window.mailpoet_current_wp_user.user_email,
        createdAt: window.mailpoet_installed_at_isoFormat,
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
