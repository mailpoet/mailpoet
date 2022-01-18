import React from 'react';
import MailPoet from 'mailpoet';
import PremiumBannerWithUpgrade from 'common/premium_banner_with_upgrade/premium_banner_with_upgrade';
import Button from 'common/button/button';
import ReactStringReplace from 'react-string-replace';

const NoAccessInfo: React.FunctionComponent = () => {
  const getBannerMessage: React.FunctionComponent = () => {
    const message = MailPoet.I18n.t('premiumRequired');
    return (
      <p>
        {ReactStringReplace(
          message,
          /\[link](.*?)\[\/link]/g,
          (match) => (
            <a key={match} href={MailPoet.premiumLink}>{match}</a>
          )
        )}
      </p>
    );
  };

  const getCtaButton: React.FunctionComponent = () => (
    <Button
      href={MailPoet.MailPoetComUrlFactory.getFreePlanUrl({
        utm_medium: 'stats',
        utm_campaign: 'signup',
      })}
    >
      {MailPoet.I18n.t('premiumBannerCtaFree')}
    </Button>
  );

  return (
    <table className="mailpoet-listing-table" data-automation-id="subscriber-stats-no-access">
      <thead>
        <tr>
          <th>{MailPoet.I18n.t('email')}</th>
          <th>{MailPoet.I18n.t('columnAction')}</th>
          <th>{MailPoet.I18n.t('columnCount')}</th>
          <th>{MailPoet.I18n.t('columnActionOn')}</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td colSpan={4}>
            <div className="mailpoet-subscriber-stats-no-access-content">
              <PremiumBannerWithUpgrade
                message={getBannerMessage({})}
                actionButton={getCtaButton({})}
              />
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  );
};

export default NoAccessInfo;
