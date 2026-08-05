import { ReactElement } from 'react';
import { t } from 'common/functions';
import { Radio } from 'common/form/radio/radio';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export function SubscriberChoice(): ReactElement {
  const [choice, setChoice] = useSetting(
    'tracking',
    'consent',
    'subscriber_choice',
  );

  return (
    <>
      <Label
        title={t('subscriberChoiceTitle')}
        description={t('subscriberChoiceDescription')}
        htmlFor="subscriber_choice"
      />
      <Inputs>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="subscriber-choice-track-all"
            value="track_all"
            checked={choice === 'track_all'}
            onCheck={setChoice}
            automationId="subscriber-choice-track-all-radio"
          />
          <label htmlFor="subscriber-choice-track-all">
            {t('subscriberChoiceTrackAll')}
          </label>
        </div>
        <p className="description">
          {t('subscriberChoiceTrackAllDescription')}
        </p>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="subscriber-choice-ask-new"
            value="ask_new"
            checked={choice === 'ask_new'}
            onCheck={setChoice}
            automationId="subscriber-choice-ask-new-radio"
          />
          <label htmlFor="subscriber-choice-ask-new">
            {t('subscriberChoiceAskNew')}
          </label>
        </div>
        <p className="description">{t('subscriberChoiceAskNewDescription')}</p>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="subscriber-choice-ask-all"
            value="ask_all"
            checked={choice === 'ask_all'}
            onCheck={setChoice}
            automationId="subscriber-choice-ask-all-radio"
          />
          <label htmlFor="subscriber-choice-ask-all">
            {t('subscriberChoiceAskAll')}
          </label>
        </div>
        <p className="description">{t('subscriberChoiceAskAllDescription')}</p>
      </Inputs>
    </>
  );
}
