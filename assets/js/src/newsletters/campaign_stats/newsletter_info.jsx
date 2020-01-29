import MailPoet from 'mailpoet';
import React from 'react';
import PropTypes from 'prop-types';

function formatAddress(address, name) {
  let addressString = '';
  if (address) {
    addressString = (name) ? `${name} <${address}>` : address;
  }
  return addressString;
}

function NewsletterStatsInfo(props) {
  const { newsletter } = props;

  const newsletterDate = newsletter.queue.scheduled_at || newsletter.queue.created_at;

  const senderAddress = formatAddress(
    newsletter.sender_address || '',
    newsletter.sender_name || ''
  );
  const replyToAddress = formatAddress(
    newsletter.reply_to_address || '',
    newsletter.reply_to_name || ''
  );

  const segments = (newsletter.segments || []).map((segment) => segment.name).join(', ');

  return (
    <div>
      <div className="mailpoet_stat_spaced">
        <a
          href={newsletter.preview_url}
          className="button-secondary"
          target="_blank"
          rel="noopener noreferrer"
        >
          {MailPoet.I18n.t('statsPreviewNewsletter')}
        </a>
      </div>

      <p>
        {MailPoet.I18n.t('statsDateSent')}
:
        {' '}
        {MailPoet.Date.format(newsletterDate)}
      </p>

      { segments && (
        <p>
          {MailPoet.I18n.t('statsToSegments')}
:
          {' '}
          { segments }
        </p>
      ) }

      <p>
        {MailPoet.I18n.t('statsFromAddress')}
:
        {' '}
        { senderAddress }
      </p>

      {replyToAddress && (
        <p>
          {MailPoet.I18n.t('statsReplyToAddress')}
:
          {' '}
          { replyToAddress }
        </p>
      ) }
    </div>
  );
}

NewsletterStatsInfo.propTypes = {
  newsletter: PropTypes.shape({
    queue: PropTypes.shape({
      scheduled_at: PropTypes.string,
      created_at: PropTypes.string,
    }).isRequired,
    sender_address: PropTypes.string,
    sender_name: PropTypes.string,
    reply_to_address: PropTypes.string,
    preview_url: PropTypes.string,
    reply_to_name: PropTypes.string,
    segments: PropTypes.array,
  }).isRequired,
};

export default NewsletterStatsInfo;
