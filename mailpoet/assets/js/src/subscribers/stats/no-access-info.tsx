import { FunctionComponent } from 'react';
import { MailPoet } from 'mailpoet';
import { PremiumBannerWithUpgrade } from 'common/premium-banner-with-upgrade/premium-banner-with-upgrade';
import { Button } from 'common/button/button';
import ReactStringReplace from 'react-string-replace';

export function NoAccessInfo(): JSX.Element {
  const getBannerMessage: FunctionComponent = () => {
    const message = MailPoet.I18n.t('premiumRequired');
    return (
      <p>
        {ReactStringReplace(message, /\[link](.*?)\[\/link]/g, (match) => (
          <a
            key={match}
            href={MailPoet.premiumLink}
            target="_blank"
            rel="noopener noreferrer"
          >
            {match}
          </a>
        ))}
      </p>
    );
  };
  const getCtaButton: FunctionComponent = () => (
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
              <PremiumBannerWithUpgrade
                message={getBannerMessage({})}
                actionButton={getCtaButton({})}
                capabilityName="detailedAnalytics"
              />
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  );
}
