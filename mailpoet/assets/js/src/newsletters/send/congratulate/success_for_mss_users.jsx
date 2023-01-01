import { useState } from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Heading } from 'common/typography/heading/heading';
import { Button } from 'common';

function getSuccessMessage(newsletter) {
  if (newsletter.type === 'welcome') {
    return __('Your Welcome Email is now active.', 'mailpoet');
  }
  if (newsletter.type === 'notification') {
    return __('Your Post Notification is now active.', 'mailpoet');
  }
  if (newsletter.type === 'automatic') {
    return __('Your WooCommerce email has been activated.', 'mailpoet');
  }
  if (newsletter.status === 'scheduled') {
    return __('Your newsletter is scheduled to be sent.', 'mailpoet');
  }
  return __('Your newsletter is being sent!', 'mailpoet');
}

function MSSUserSuccess(props) {
  const [isClosing, setIsClosing] = useState(false);
  return (
    <>
      <Heading level={0}>{__('Congratulations!', 'mailpoet')}</Heading>
      <Heading level={3}>{getSuccessMessage(props.newsletter)}</Heading>
      <div className="mailpoet-gap-large" />
      <div className="mailpoet-gap-large" />
      <img src={props.illustrationImageUrl} alt="" width="500" />
      <div className="mailpoet-gap-large" />
      <Button
        type="button"
        dimension="small"
        onClick={() => {
          props.successClicked();
          setIsClosing(true);
        }}
        withSpinner={isClosing}
      >
        {__('Close', 'mailpoet')}
      </Button>
    </>
  );
}

MSSUserSuccess.propTypes = {
  successClicked: PropTypes.func.isRequired,
  illustrationImageUrl: PropTypes.string.isRequired,
  newsletter: PropTypes.shape({
    status: PropTypes.string.isRequired,
    type: PropTypes.string.isRequired,
  }).isRequired,
};

export { MSSUserSuccess };
