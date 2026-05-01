import { t } from 'common/functions';
import { Radio } from 'common/form/radio/radio';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export function SubscriberTimeZoneData() {
  const [enabled, setEnabled] = useSetting(
    'collect_subscriber_timezones',
    'enabled',
  );

  return (
    <>
      <Label
        title={t('collectSubscriberTimeZonesTitle')}
        description={t('collectSubscriberTimeZonesDescription')}
        htmlFor=""
      />
      <Inputs>
        <Radio
          id="collect-subscriber-timezones-enabled"
          value="1"
          checked={enabled === '1'}
          onCheck={setEnabled}
          automationId="collect-subscriber-timezones-yes"
        />
        <label htmlFor="collect-subscriber-timezones-enabled">{t('yes')}</label>
        <span className="mailpoet-gap" />
        <Radio
          id="collect-subscriber-timezones-disabled"
          value=""
          checked={enabled === ''}
          onCheck={setEnabled}
          automationId="collect-subscriber-timezones-no"
        />
        <label htmlFor="collect-subscriber-timezones-disabled">{t('no')}</label>
      </Inputs>
    </>
  );
}
