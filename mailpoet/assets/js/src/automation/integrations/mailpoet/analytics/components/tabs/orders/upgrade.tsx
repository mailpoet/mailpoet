import { useCallback, useEffect, useState } from 'react';
import { Notice } from '@wordpress/components/build';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import {
  UpgradeInfo,
  useUpgradeInfo,
} from '../../../../../../../common/premium-modal/upgrade-info';

// This duplicates some functionality of the PremiumModal component.
// We could consider extracting it to a more reusable logic.
type State = undefined | 'busy' | 'success' | 'error';

const getCta = (state: State, upgradeInfo: UpgradeInfo): string => {
  const { action, cta } = upgradeInfo;
  if (typeof action === 'string') {
    return cta;
  }
  if (state === 'busy') {
    return action.busy;
  }
  if (state === 'success') {
    return action.success;
  }
  return cta;
};

export function Upgrade(): JSX.Element {
  const upgradeInfo = useUpgradeInfo(
    { capabilities: { detailedAnalytics: true } },
    {
      utm_medium: 'upsell_modal',
      utm_campaign: 'automation-analytics',
    },
  );

  const [state, setState] = useState<State>();

  useEffect(() => {
    setState(undefined);
  }, [upgradeInfo]);

  const handleClick = useCallback(async () => {
    if (typeof upgradeInfo.action === 'string') {
      return;
    }

    if (state === 'success') {
      upgradeInfo.action.successHandler();
      return;
    }

    setState('busy');
    try {
      await upgradeInfo.action.handler();
      setState('success');
    } catch (_) {
      setState('error');
    }
  }, [state, upgradeInfo.action]);

  return (
    <Notice
      className="mailpoet-analytics-upgrade-banner"
      status="warning"
      isDismissible={false}
    >
      <span className="mailpoet-analytics-upgrade-banner__inner">
        <span>
          <strong>
            {__("You're previewing a report with sample data.", 'mailpoet')}
          </strong>{' '}
          {upgradeInfo.info}
        </span>

        {typeof upgradeInfo.action === 'string' ? (
          <Button
            variant="primary"
            href={upgradeInfo.action}
            target="_blank"
            rel="noopener noreferrer"
          >
            {upgradeInfo.cta}
          </Button>
        ) : (
          <Button
            variant="primary"
            onClick={handleClick}
            isBusy={state === 'busy'}
            disabled={state === 'busy'}
          >
            {getCta(state, upgradeInfo)}
          </Button>
        )}
      </span>
    </Notice>
  );
}
