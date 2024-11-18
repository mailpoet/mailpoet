import { __, _n, sprintf } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';

type ReviewRequestTypes = {
  installedDaysAgo: number;
  reviewRequestIllustrationUrl: string;
  username: string;
};

function ReviewRequest({
  installedDaysAgo,
  reviewRequestIllustrationUrl,
  username,
}: ReviewRequestTypes) {
  const numberOfMonths = Math.round(installedDaysAgo / 30);
  const usingForPhrase =
    installedDaysAgo > 30
      ? sprintf(
          _n(
            'You’ve been using MailPoet for %d month now, and we would love to read your own review.',
            'You’ve been using MailPoet for %d months now, and we would love to read your own review.',
            numberOfMonths,
            'mailpoet',
          ),
          numberOfMonths,
        )
      : sprintf(
          _n(
            'You’ve been using MailPoet for %d day now, and we would love to read your own review.',
            'You’ve been using MailPoet for %d days now, and we would love to read your own review.',
            installedDaysAgo,
            'mailpoet',
          ),
          installedDaysAgo,
        );

  return (
    <div className="mailpoet_review_request">
      <img src={reviewRequestIllustrationUrl} height="280" width="280" alt="" />
      {/* translators: After a user gives us positive feedback via the NPS poll, we ask them to review our plugin on WordPress.org. */}
      <h2>{__('Thank you! Time to tell the world?', 'mailpoet')}</h2>
      <p>
        {sprintf(
          __(
            '%s, did you know that hundreds of WordPress users read the reviews on the plugin repository? They’re also a source of inspiration for our team.',
            'mailpoet',
          ),
          escapeHTML(username),
        )}
      </p>
      <p>{usingForPhrase}</p>
      <p>
        <a
          href="http://bit.ly/2Bi124o"
          target="_blank"
          rel="noopener noreferrer"
          className="button button-primary"
        >
          {/* translators: Review our plugin on WordPress.org. */}
          {__('Rate us now', 'mailpoet')}
        </a>
      </p>
      <p>
        <a id="mailpoet_review_request_not_now">{__('Not now')}</a>
      </p>
    </div>
  );
}

export { ReviewRequest };
