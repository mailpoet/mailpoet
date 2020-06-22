import React from 'react';
import Heading from 'common/typography/heading/heading';
import MailPoet from 'mailpoet';

export default () => (
  <div className="template-selection">
    <Heading level={1}>{MailPoet.I18n.t('heading')}</Heading>
  </div>
);
