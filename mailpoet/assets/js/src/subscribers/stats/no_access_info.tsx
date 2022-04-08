import { FunctionComponent } from 'react';
import MailPoet from 'mailpoet';
import PremiumRequired from 'common/premium_required/premium_required';
import Button from 'common/button/button';
import ReactStringReplace from 'react-string-replace';

type Props = {
  limitReached: boolean;
  limitValue: number;
  subscribersCountTowardsLimit: number;
  premiumActive: boolean;
  hasValidApiKey: boolean;
  hasPremiumSupport: boolean;
};

export default function NoAccessInfo({
  limitReached,
  limitValue,
  subscribersCountTowardsLimit,
  premiumActive,
  hasValidApiKey,
  hasPremiumSupport,
}: Props): JSX.Element {
  const getBannerMessage: FunctionComponent = () => {
    let message = MailPoet.I18n.t('premiumRequired');
    if (!premiumActive) {
      return (
        <p>
          {ReactStringReplace(message, /\[link](.*?)\[\/link]/g, (match) => (
            <a key={match} href={MailPoet.premiumLink}>
              {match}
            </a>
          ))}
        </p>
      );
    }
    // Covers premium with paid plan
    if (hasPremiumSupport) {
      message = MailPoet.I18n.t('planLimitReached');
    } else {
      // Covers premium without apikey and premium with free plan api key
      message = MailPoet.I18n.t('freeLimitReached');
    }
    return (
      <p>
        {ReactStringReplace(
          message,
          /(\[subscribersCount]|\[subscribersLimit])/g,
          (match) =>
            match === '[subscribersCount]'
              ? subscribersCountTowardsLimit
              : limitValue,
        )}
      </p>
    );
  };

  const getCtaButton: FunctionComponent = () =>
    premiumActive && limitReached ? (
      <Button
        href={
          hasValidApiKey
            ? MailPoet.MailPoetComUrlFactory.getUpgradeUrl()
            : MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
                subscribersCountTowardsLimit + 1,
              )
        }
      >
        {MailPoet.I18n.t('premiumBannerCtaUpgrade')}
      </Button>
    ) : (
      <Button
        href={MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
          MailPoet.subscribersCount,
          MailPoet.currentWpUserEmail,
          null,
          { utm_medium: 'stats', utm_campaign: 'signup' },
        )}
      >
        {MailPoet.I18n.t('premiumBannerCtaFree')}
      </Button>
    );

  return (
    <table
      className="mailpoet-listing-table"
      data-automation-id="subscriber-stats-no-access"
    >
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
                title={
                  premiumActive && limitReached
                    ? MailPoet.I18n.t('upgradeRequired')
                    : MailPoet.I18n.t('premiumFeature')
                }
                message={getBannerMessage({})}
                actionButton={getCtaButton({})}
              />
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  );
}
