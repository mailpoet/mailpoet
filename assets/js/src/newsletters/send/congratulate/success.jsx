import React from 'react';
import PropTypes from 'prop-types';

import { GlobalContext } from 'context/index.jsx';
import MSSUserSuccess from './success_for_mss_users.jsx';
import PitchMss from './success_pitch_mss.jsx';


function SuccessContent(props) {
  if (!window.has_mss_key_specified && props.isSupported('display-mss-pitch')) {
    return (
      <PitchMss
        MSSPitchIllustrationUrl={props.MSSPitchIllustrationUrl}
        onFinish={props.successClicked}
        subscribersCount={props.subscribersCount}
        mailpoetAccountUrl={props.mailpoetAccountUrl}
      />
    );
  }
  return (
    <MSSUserSuccess
      successClicked={props.successClicked}
      illustrationImageUrl={props.illustrationImageUrl}
      newsletter={props.newsletter}
    />
  );
}

function Success(props) {
  const { features } = React.useContext(GlobalContext);
  return (
    <SuccessContent
      {...props}
      isSupported={features.isSupported}
    />
  );
}

Success.propTypes = {
  successClicked: PropTypes.func.isRequired,
  illustrationImageUrl: PropTypes.string.isRequired,
  MSSPitchIllustrationUrl: PropTypes.string.isRequired,
  newsletter: PropTypes.shape({
    status: PropTypes.string.isRequired,
    type: PropTypes.string.isRequired,
  }).isRequired,
  subscribersCount: PropTypes.number.isRequired,
  mailpoetAccountUrl: PropTypes.string.isRequired,
};

SuccessContent.propTypes = {
  successClicked: PropTypes.func.isRequired,
  illustrationImageUrl: PropTypes.string.isRequired,
  MSSPitchIllustrationUrl: PropTypes.string.isRequired,
  newsletter: PropTypes.shape({
    status: PropTypes.string.isRequired,
    type: PropTypes.string.isRequired,
  }).isRequired,
  subscribersCount: PropTypes.number.isRequired,
  mailpoetAccountUrl: PropTypes.string.isRequired,
  isSupported: PropTypes.func.isRequired,
};

export default Success;
