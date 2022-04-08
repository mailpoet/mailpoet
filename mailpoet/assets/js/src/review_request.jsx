import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

function ReviewRequest(props) {
  const days = props.installedDaysAgo;
  const usingForPhrase =
    days > 30
      ? MailPoet.I18n.t('reviewRequestUsingForMonths').replace(
          '[months]',
          Math.round(days / 30),
        )
      : MailPoet.I18n.t('reviewRequestUsingForDays').replace('[days]', days);

  return (
    <div className="mailpoet_review_request">
      <img
        src={props.reviewRequestIllustrationUrl}
        height="280"
        width="280"
        alt=""
      />
      <h2>{MailPoet.I18n.t('reviewRequestHeading')}</h2>
      <p>
        {MailPoet.I18n.t('reviewRequestDidYouKnow').replace(
          '[username]',
          props.username,
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
          {MailPoet.I18n.t('reviewRequestRateUsNow')}
        </a>
      </p>
      <p>
        <a id="mailpoet_review_request_not_now">
          {MailPoet.I18n.t('reviewRequestNotNow')}
        </a>
      </p>
    </div>
  );
}

ReviewRequest.propTypes = {
  installedDaysAgo: PropTypes.number.isRequired,
  reviewRequestIllustrationUrl: PropTypes.string.isRequired,
  username: PropTypes.string.isRequired,
};

export default ReviewRequest;
