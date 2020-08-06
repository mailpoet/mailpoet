import React from 'react';
import MailPoet from 'mailpoet';
import Heading from 'common/typography/heading/heading';

export type PropTypes = {
  email: string
}

export default ({ email }: PropTypes) => (
  <Heading level={0}>
    {MailPoet.I18n.t('statsHeading').replace('%s', email)}
  </Heading>
);
