import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

const SendingStatus = props => {
  const newsletterId = props.match.params.id
  return <h1>{MailPoet.I18n.t('sendingStatusTitle')}</h1>
}

export default SendingStatus