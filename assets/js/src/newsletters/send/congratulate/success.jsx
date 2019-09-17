import React from 'react';
import PropTypes from 'prop-types';

import MSSUserSuccess from './success_for_mss_users.jsx';
import PitchMss from './success_pitch_mss.jsx';

function Success(props) {
  if (!window.has_premium_key) {
    return (
      <PitchMss />
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

Success.propTypes = {
  successClicked: PropTypes.func.isRequired,
  illustrationImageUrl: PropTypes.string.isRequired,
  newsletter: PropTypes.shape({
    status: PropTypes.string.isRequired,
    type: PropTypes.string.isRequired,
  }).isRequired,
};


export default Success;
