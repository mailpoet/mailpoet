import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import Loader from 'common/loader/loader';
import Heading from 'common/typography/heading/heading';

function Loading(props) {
  return (
    <div className="mailpoet_newsletter_loading">
      {props.showRichLoadingScreen && (
        <div>
          <Heading level={0}>{MailPoet.I18n.t('congratulationsLoadingHeader')}</Heading>
          <p className="mailpoet_newsletter_loading_body">{MailPoet.I18n.t('congratulationsLoadingBody')}</p>
          <Loader size={64} />
        </div>
      )}
    </div>
  );
}

Loading.propTypes = {
  showRichLoadingScreen: PropTypes.bool.isRequired,
};

export default Loading;
