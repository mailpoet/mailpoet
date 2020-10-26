import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import Button from 'common/button/button';
import Heading from 'common/typography/heading/heading';

const OfferMigration = ({
  subscribersCount,
}) => {
  if (subscribersCount < 2000) return null;

  return (
    <>
      <br />
      <Heading level={2}>{MailPoet.I18n.t('offerMigrationHead')}</Heading>
      <p>
        {MailPoet.I18n.t('offerMigrationSubhead')}
        :
      </p>
      <ul className="default-list">
        <li>{MailPoet.I18n.t('offerMigrationList1')}</li>
        <li>{MailPoet.I18n.t('offerMigrationList2')}</li>
        <li>{MailPoet.I18n.t('offerMigrationList3')}</li>
        <li>{MailPoet.I18n.t('offerMigrationList4')}</li>
      </ul>
      <Button
        variant="dark"
        type="button"
        href="https://www.mailpoet.com/concierge-migration/"
        target="_blank"
        rel="noopener noreferrer"
      >
        {MailPoet.I18n.t('offerMigrationCTA')}
      </Button>
    </>
  );
};

OfferMigration.propTypes = {
  subscribersCount: PropTypes.number.isRequired,
};

export default OfferMigration;
