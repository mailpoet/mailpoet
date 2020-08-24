import React from 'react';
import MailPoet from 'mailpoet';
import PremiumRequired from 'common/premium_required/premium_required';
import Button from 'common/button/button';

type Props = {
  limitReached: boolean
  limitValue: number
  subscribersCount: number
  premiumActive: boolean
  hasValidApiKey: boolean
}

const NoAccessInfo = ({
  limitReached,
  limitValue,
  subscribersCount,
  premiumActive,
  hasValidApiKey,
}: Props) => {
  const getBannerMessage = () => {
    if (!premiumActive) {
      return MailPoet.I18n.t('premiumRequired');
    }
    return MailPoet.I18n.t('planLimitReached')
      .replace('[subscribersCount]', subscribersCount)
      .replace('[subscribersCount]', limitValue);
  };

  const getCtaButton = () => (
    limitReached ? (
      <Button
        href={
          hasValidApiKey
            ? MailPoet.MailPoetComUrlFactory.getUpgradeUrl()
            : MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(subscribersCount + 1)
        }
      >
        {MailPoet.I18n.t('upgrade')}
      </Button>
    ) : (
      <Button
        href={MailPoet.MailPoetComUrlFactory.getFreePlanUrl({
          utm_medium: 'stats',
          utm_campaign: 'signup',
        })}
      >
        {MailPoet.I18n.t('premiumBannerCtaFree')}
      </Button>
    )
  );

  return (
    <table className="mailpoet-listing-table wp-list-table widefat">
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
              <PremiumRequired
                title={limitReached ? MailPoet.I18n.t('upgradeRequired') : MailPoet.I18n.t('premiumFeature')}
                /* eslint-disable-next-line react/no-danger */
                message={(<p dangerouslySetInnerHTML={{ __html: getBannerMessage() }} />)}
                actionButton={getCtaButton()}
              />
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  );
};

export default NoAccessInfo;
