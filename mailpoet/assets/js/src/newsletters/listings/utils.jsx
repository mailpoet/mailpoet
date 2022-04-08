import ReactDOM from 'react-dom';
import { Link } from 'react-router-dom';
import ReactStringReplace from 'react-string-replace';
import Hooks from 'wp-js-hooks';
import MailPoet from 'mailpoet';
import jQuery from 'jquery';
import confirmAlert from 'common/confirm_alert.jsx';

export const trackStatsCTAClicked = () => {
  MailPoet.trackEvent('User has clicked a CTA to view detailed stats');
};

export const addStatsCTAAction = (actions) => {
  actions.unshift({
    name: 'stats',
    link: function link(newsletter) {
      return (
        <Link
          to={`/stats/${newsletter.id}`}
          onClick={Hooks.applyFilters(
            'mailpoet_newsletters_listing_stats_tracking',
            trackStatsCTAClicked,
          )}
        >
          {MailPoet.I18n.t('statsListingActionTitle')}
        </Link>
      );
    },
    display: function display(newsletter) {
      // welcome emails provide explicit total_sent value
      const countProcessed =
        newsletter.queue && newsletter.queue.count_processed;
      return Number(newsletter.total_sent || countProcessed) > 0;
    },
  });
  return actions;
};

export const checkMailerStatus = (state) => {
  if (
    state.meta.mta_log.error &&
    state.meta.mta_log.error.operation === 'authorization'
  ) {
    MailPoet.Notice.hide('mailpoet_notice_being_sent');
    if (
      state.meta.mta_log.error.error_message.indexOf(
        'mailpoet-js-button-resume-sending',
      ) >= 0
    ) {
      jQuery('.mailpoet-js-error-unauthorized-emails-notice').hide(); // prevent duplicate notices
    }
    MailPoet.Notice.error(state.meta.mta_log.error.error_message, {
      static: true,
      id: 'mailpoet_authorization_error',
    });
  }
};

export const checkCronStatus = (state) => {
  if (state.meta.cron_accessible !== false) {
    MailPoet.Notice.hide('mailpoet_cron_error');
    return;
  }

  const cronPingCheckNotice = ReactStringReplace(
    MailPoet.I18n.t('cronNotAccessibleNotice'),
    /\[link\](.*?)\[\/link\]/g,
    (match) => (
      <a
        href="https://kb.mailpoet.com/article/231-sending-does-not-work"
        data-beacon-article="5a0257ac2c7d3a272c0d7ad6"
        target="_blank"
        rel="noopener noreferrer"
        key="check-cron"
      >
        {match}
      </a>
    ),
  );

  MailPoet.Notice.error('', { static: true, id: 'mailpoet_cron_error' });

  ReactDOM.render(
    <div>
      <p>{cronPingCheckNotice}</p>
    </div>,
    jQuery('[data-id="mailpoet_cron_error"]')[0],
  );
};

export const newsletterTypesWithActivation = [
  'automatic',
  'welcome',
  'notification',
  're_engagement',
];

export const confirmEdit = (newsletter) => {
  const redirectToEditing = () => {
    window.location.href = `?page=mailpoet-newsletter-editor&id=${newsletter.id}`;
  };

  if (
    newsletterTypesWithActivation.includes(newsletter.type) &&
    newsletter.status === 'active'
  ) {
    confirmAlert({
      message: MailPoet.I18n.t('confirmAutomaticNewsletterEdit'),
      onConfirm: redirectToEditing,
    });
  } else {
    redirectToEditing();
  }
};
