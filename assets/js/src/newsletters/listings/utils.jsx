import React from 'react';
import ReactDOM from 'react-dom';
import { Link } from 'react-router-dom';
import ReactStringReplace from 'react-string-replace';
import Hooks from 'wp-js-hooks';
import MailPoet from 'mailpoet';
import jQuery from 'jquery';

export const trackStatsCTAClicked = () => {
  MailPoet.trackEvent(
    'User has clicked a CTA to view detailed stats',
    { 'MailPoet Free version': window.mailpoet_version }
  );
};

export const addStatsCTAAction = (actions) => {
  actions.unshift({
    name: 'stats',
    link: function link(newsletter) {
      return (
        <Link
          to={`/stats/${newsletter.id}`}
          onClick={Hooks.applyFilters('mailpoet_newsletters_listing_stats_tracking', trackStatsCTAClicked)}
        >
          {MailPoet.I18n.t('statsListingActionTitle')}
        </Link>
      );
    },
    display: function display(newsletter) {
      // welcome emails provide explicit total_sent value
      const countProcessed = newsletter.queue && newsletter.queue.count_processed;
      return Number(newsletter.total_sent || countProcessed) > 0;
    },
  });
  return actions;
};

export const checkMailerStatus = (state) => {
  if (state.meta.mta_log.error && state.meta.mta_log.error.operation === 'authorization') {
    MailPoet.Notice.hide('mailpoet_notice_being_sent');
    MailPoet.Notice.error(
      state.meta.mta_log.error.error_message,
      { static: true, id: 'mailpoet_authorization_error' }
    );
    jQuery('.js-button-resume-sending').on('click', () => {
      jQuery('[data-id="mailpoet_authorization_error"]').slideUp();
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
        { match }
      </a>
    )
  );

  MailPoet.Notice.error(
    '',
    { static: true, id: 'mailpoet_cron_error' }
  );

  ReactDOM.render(
    (
      <div>
        <p>{cronPingCheckNotice}</p>
      </div>
    ),
    jQuery('[data-id="mailpoet_cron_error"]')[0]
  );
};
