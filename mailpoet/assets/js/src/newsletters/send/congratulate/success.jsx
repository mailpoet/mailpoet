import PropTypes from 'prop-types';

import { MSSUserSuccess } from './success-for-mss-users.jsx';
import { PitchMss } from './success-pitch-mss.tsx';

function Success(props) {
  if (!window.has_mss_key_specified) {
    return (
      <PitchMss
        MSSPitchIllustrationUrl={props.MSSPitchIllustrationUrl}
        onFinish={props.successClicked}
        subscribersCount={props.subscribersCount}
        purchaseUrl={props.purchaseUrl}
        newsletter={props.newsletter}
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

Success.propTypes = {
  successClicked: PropTypes.func.isRequired,
  illustrationImageUrl: PropTypes.string.isRequired,
  MSSPitchIllustrationUrl: PropTypes.string.isRequired,
  newsletter: PropTypes.shape({
    status: PropTypes.string.isRequired,
    type: PropTypes.string.isRequired,
  }).isRequired,
  subscribersCount: PropTypes.number.isRequired,
  purchaseUrl: PropTypes.string.isRequired,
};

export { Success };
