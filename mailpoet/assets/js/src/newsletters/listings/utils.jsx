import { __ } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import { MailPoet } from 'mailpoet';
import jQuery from 'jquery';
import { confirmAlert } from 'common/confirm-alert.jsx';
import {
  hasStartedTimezoneBatches,
  isTimezoneCampaignQueue,
} from 'newsletters/timezone-campaign';

export const trackStatsCTAClicked = () => {
  MailPoet.trackEvent('User has clicked a CTA to view detailed stats');
};

export const addStatsCTAAction = (actions, navigate) => {
  actions.unshift({
    id: 'stats',
    label: __('Statistics', 'mailpoet'),
    context: 'single',
    supportsBulk: false,
    isEligible: function isEligible(newsletter) {
      // welcome emails provide explicit total_sent value
      const countProcessed =
        newsletter.queue && newsletter.queue.count_processed;
      return Number(newsletter.total_sent || countProcessed) > 0;
    },
    callback: function callback(targets) {
      const target = targets[0];
      if (!target) {
        return;
      }
      Hooks.applyFilters(
        'mailpoet_newsletters_listing_stats_tracking',
        trackStatsCTAClicked,
      )();
      navigate(`/stats/${target.id}`);
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

  // Render the notice as HTML rather than mounting a React root into the
  // jQuery-managed notice node. The notice system re-renders that node when
  // the check runs again (e.g. on a tab switch), which removes React's
  // content out-of-band and leaves an empty notice.
  const cronPingCheckNotice = __(
    'Oops! There seems to be an issue with the sending on your website. [link]See our guide[/link] to solve this yourself.',
    'mailpoet',
  )
    .replace(
      '[link]',
      '<a href="https://kb.mailpoet.com/article/231-sending-does-not-work" target="_blank" rel="noopener noreferrer">',
    )
    .replace('[/link]', '</a>');

  MailPoet.Notice.error(cronPingCheckNotice, {
    static: true,
    id: 'mailpoet_cron_error',
  });
};

export const newsletterTypesWithActivation = [
  'automatic',
  'welcome',
  'notification',
  're_engagement',
];

export const automationTypes = ['automation', 'automation_transactional'];

export const confirmEdit = (newsletter) => {
  const editorHref = MailPoet.getActiveEmailEditorUrl(newsletter);

  // A subscriber time zone campaign can no longer be edited once one of its
  // batches has started; the backend rejects the save with the same message
  // (canReplaceScheduledCampaign guard), so surface it before opening the
  // editor instead of failing on save.
  if (
    isTimezoneCampaignQueue(newsletter.queue) &&
    hasStartedTimezoneBatches(newsletter.queue)
  ) {
    MailPoet.Notice.error(
      __(
        'This email can no longer be edited because one or more time zone batches have already started.',
        'mailpoet',
      ),
      { scroll: true },
    );
    return;
  }

  // A newsletter that is mid-send (status `sending`, queue not yet paused)
  // must be paused before it can be edited.
  if (
    newsletter.queue &&
    newsletter.status === 'sending' &&
    newsletter.queue.status === null
  ) {
    confirmAlert({
      message: __(
        'Sending is in progress. Do you want to pause sending and edit the newsletter?',
        'mailpoet',
      ),
      onConfirm: () => {
        window.location.href = `${editorHref}&pauseConfirmed=yes`;
      },
    });
    return;
  }

  if (
    newsletterTypesWithActivation.includes(newsletter.type) &&
    newsletter.status === 'active'
  ) {
    confirmAlert({
      message: __(
        'To edit this email, it needs to be deactivated. You can activate it again after you make the changes.',
        'mailpoet',
      ),
      onConfirm: () => {
        window.location.href = `${editorHref}&deactivationConfirmed=yes`;
      },
    });
  } else {
    window.location.href = editorHref;
  }
};
