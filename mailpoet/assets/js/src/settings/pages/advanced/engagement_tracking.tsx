import { ReactElement } from 'react';
import { t } from 'common/functions';
import Radio from 'common/form/radio/radio';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export function EngagementTracking(): ReactElement {
  const [tracking, setTrackingLevel] = useSetting('tracking', 'level');

  return (
    <>
      <Label
        title={t('engagementTrackingTitle')}
        description={t('engagementTrackingDescription')}
        htmlFor="engagement_tracking"
      />
      <Inputs>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="tracking-basic"
            value="basic"
            checked={tracking === 'basic'}
            onCheck={setTrackingLevel}
            automationId="tracking-basic-radio"
          />
          <label htmlFor="tracking-basic">{t('engagementTrackingBasic')}</label>
        </div>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="tracking-partial"
            value="partial"
            checked={tracking === 'partial'}
            onCheck={setTrackingLevel}
            automationId="tracking-partial-radio"
          />
          <label htmlFor="tracking-partial">
            {t('engagementTrackingPartial')}
          </label>
        </div>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="tracking-full"
            value="full"
            checked={tracking === 'full'}
            onCheck={setTrackingLevel}
            automationId="tracking-full-radio"
          />
          <label htmlFor="tracking-full">{t('engagementTrackingFull')}</label>
        </div>
      </Inputs>
    </>
  );
}
