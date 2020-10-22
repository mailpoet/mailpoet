import React from 'react';
import MailPoet from 'mailpoet';
import { Link, useLocation } from 'react-router-dom';
import Heading from 'common/typography/heading/heading';
import { LocationState } from 'subscribers/location_state';

export type PropTypes = {
  email: string
}

export default ({ email }: PropTypes) => {
  const location = useLocation<LocationState>();
  const backUrl = location.state?.backUrl || '/';
  return (
    <Heading level={1} className="mailpoet-title">
      <span>{MailPoet.I18n.t('statsHeading').replace('%s', email)}</span>
      <Link className="mailpoet-button mailpoet-button-small" to={backUrl}>{MailPoet.I18n.t('backToList')}</Link>
    </Heading>
  );
};
