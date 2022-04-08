import { useState } from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import classNames from 'classnames';

const SingleWarning = ({ translation, subscribers }) => {
  let warning = '';
  if (subscribers.length) {
    warning = ReactStringReplace(
      translation.replace('%2$s', subscribers.join(', ')),
      '%1$s',
      () => (
        <strong key={translation}>{subscribers.length.toLocaleString()}</strong>
      ),
    );
    warning = <p>{warning}</p>;
  }
  return warning;
};

SingleWarning.propTypes = {
  translation: PropTypes.string.isRequired,
  subscribers: PropTypes.arrayOf(PropTypes.string).isRequired,
};

function Warnings({ stepMethodSelectionData }) {
  const { invalid, duplicate, role } = stepMethodSelectionData;

  const [detailsShown, setDetailsShown] = useState(false);

  const detailClasses = classNames(
    'mailpoet_subscribers_data_parse_results_details',
    { mailpoet_hidden: !detailsShown },
  );

  const invalidWarning = (
    <SingleWarning
      translation={MailPoet.I18n.t('importNoticeInvalid')}
      subscribers={invalid}
    />
  );

  const duplicateWarning = (
    <SingleWarning
      translation={MailPoet.I18n.t('importNoticeDuplicate')}
      subscribers={duplicate}
    />
  );

  let roleBasedWarning = '';
  if (role.length) {
    roleBasedWarning = ReactStringReplace(
      MailPoet.I18n.t('importNoticeRoleBased'),
      /(%1\$s|\[link\].*\[\/link\]|%2\$s)/,
      (match) => {
        if (match === '%1$s')
          return (
            <strong key="role-length">{role.length.toLocaleString()}</strong>
          );
        if (match === '%2$s') return role.join(', ');
        return (
          <a
            href="https://kb.mailpoet.com/article/270-role-based-email-addresses-are-not-allowed"
            data-beacon-article="5d0a1da404286318cac46fe5"
            target="_blank"
            rel="noopener noreferrer"
            key={match}
          >
            {match.replace('[link]', '').replace('[/link]', '')}
          </a>
        );
      },
    );
    roleBasedWarning = <p>{roleBasedWarning}</p>;
  }

  if (invalid.length || duplicate.length || role.length) {
    const allWarningsCount = invalid.length + duplicate.length + role.length;
    return (
      <div className="error">
        <p>
          {ReactStringReplace(
            MailPoet.I18n.t('importNoticeSkipped'),
            '%1$s',
            () => (
              <strong key="lengths">{allWarningsCount.toLocaleString()}</strong>
            ),
          )}{' '}
          <a
            className="mailpoet_subscribers_data_parse_results_details_show"
            data-automation-id="show-more-details"
            onClick={() => setDetailsShown(!detailsShown)}
            role="button"
            tabIndex={0}
            onKeyDown={(event) => {
              if (
                ['keydown', 'keypress'].includes(event.type) &&
                ['Enter', ' '].includes(event.key)
              ) {
                event.preventDefault();
                setDetailsShown(!detailsShown);
              }
            }}
          >
            {MailPoet.I18n.t('showMoreDetails')}
          </a>
        </p>
        <div className={detailClasses}>
          <hr />
          {invalidWarning}
          {duplicateWarning}
          {roleBasedWarning}
        </div>
      </div>
    );
  }
  return null;
}

Warnings.propTypes = {
  stepMethodSelectionData: PropTypes.shape({
    duplicate: PropTypes.arrayOf(PropTypes.string),
    invalid: PropTypes.arrayOf(PropTypes.string),
    role: PropTypes.arrayOf(PropTypes.string),
  }),
};

Warnings.defaultProps = {
  stepMethodSelectionData: {
    invalid: [],
    duplicate: [],
    role: [],
  },
};

export default Warnings;
