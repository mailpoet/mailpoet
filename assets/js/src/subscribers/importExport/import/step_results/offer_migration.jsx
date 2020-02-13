import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const OfferMigration = ({
  subscribersCount,
}) => {
  if (subscribersCount < 2000) return null;

  return (
    <div className="mailpoet_offer_migration">
      <h2>{MailPoet.I18n.t('offerMigrationHead')}</h2>
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
      <a
        type="button"
        className="button-primary wysija"
        href="https://www.mailpoet.com/concierge-migration/"
        target="_blank"
        rel="noopener noreferrer"
      >
        {MailPoet.I18n.t('offerMigrationCTA')}
      </a>
    </div>
  );
};

OfferMigration.propTypes = {
  subscribersCount: PropTypes.number.isRequired,
};

export default OfferMigration;
