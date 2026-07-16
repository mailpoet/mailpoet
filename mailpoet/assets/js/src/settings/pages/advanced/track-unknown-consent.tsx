import { t } from 'common/functions';
import { Radio } from 'common/form/radio/radio';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export function TrackUnknownConsent() {
  const [trackUnknown, setTrackUnknown] = useSetting(
    'tracking',
    'consent',
    'track_unknown',
  );

  return (
    <>
      <Label
        title={t('trackUnknownConsentTitle')}
        description={t('trackUnknownConsentDescription')}
        htmlFor=""
      />
      <Inputs>
        <Radio
          id="track-unknown-consent-yes"
          value="1"
          checked={trackUnknown === '1'}
          onCheck={setTrackUnknown}
          automationId="track-unknown-consent-yes"
        />
        <label htmlFor="track-unknown-consent-yes">{t('yes')}</label>
        <span className="mailpoet-gap" />
        <Radio
          id="track-unknown-consent-no"
          value=""
          checked={trackUnknown === ''}
          onCheck={setTrackUnknown}
          automationId="track-unknown-consent-no"
        />
        <label htmlFor="track-unknown-consent-no">{t('no')}</label>
      </Inputs>
    </>
  );
}
