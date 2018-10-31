import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from '../../../mailpoet';
import LoadingDots from '../../../loading.jsx';

function renderRichData(showRichData, illustrationImageUrl) {
  if (showRichData) {
    return (
      <div>
        <h1 className="mailpoet_newsletter_loading_header">{MailPoet.I18n.t('congratulationsLoadingHeader')}</h1>
        <img src={illustrationImageUrl} alt="" width="800px" height="266px" />
      </div>
    );
  }
  return (<div />);
}

function Loading(props) {
  return (
    <div className="mailpoet_newsletter_loading">
      <LoadingDots />
      {renderRichData(props.showRichLoadingScreen, props.illustrationImageUrl)}
    </div>
  );
}

Loading.propTypes = {
  illustrationImageUrl: PropTypes.string.isRequired,
  showRichLoadingScreen: PropTypes.bool.isRequired,
};

module.exports = Loading;
