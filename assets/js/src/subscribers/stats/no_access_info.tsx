import React from 'react';
import MailPoet from 'mailpoet';
import PremiumRequired from 'common/premium_required/premium_required';
import Button from 'common/button/button';

type Props = {
  limitReached: boolean
  limitValue: number
  subscribersCountTowardsLimit: number
  premiumActive: boolean
  hasValidApiKey: boolean
  hasPremiumSupport: boolean
}

const NoAccessInfo = ({
  limitReached,
  limitValue,
  subscribersCountTowardsLimit,
  premiumActive,
  hasValidApiKey,
  hasPremiumSupport,
}: Props) => {
  const getBannerMessage = () => {
    if (!premiumActive) {
      return MailPoet.I18n.t('premiumRequired');
    }
    // Covers premium with paid plan
    if (hasPremiumSupport) {
      return MailPoet.I18n.t('planLimitReached')
        .replace('[subscribersCount]', subscribersCountTowardsLimit)
        .replace('[subscribersCount]', limitValue);
    }
    // Covers premium without apikey and premium with free plan api key
    return MailPoet.I18n.t('freeLimitReached')
      .replace('[subscribersCount]', subscribersCountTowardsLimit)
      .replace('[subscribersCount]', limitValue);
  };

  const getCtaButton = () => (
    limitReached ? (
      <Button
        href={
          hasValidApiKey
            ? MailPoet.MailPoetComUrlFactory.getUpgradeUrl()
            : MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(subscribersCountTowardsLimit + 1)
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
    <table className="mailpoet-listing-table wp-list-table widefat" data-automation-id="subscriber-stats-no-access">
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
