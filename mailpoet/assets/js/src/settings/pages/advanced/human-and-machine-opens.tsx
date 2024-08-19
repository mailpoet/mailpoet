import { ReactElement } from 'react';
import { t } from 'common/functions';
import { Radio } from 'common/form/radio/radio';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export function HumanAndMachineOpens(): ReactElement {
  const [opens, setOpensMode] = useSetting('tracking', 'opens');

  return (
    <>
      <Label
        title={t('humanAndMachineOpensTitle')}
        description={t('humanAndMachineOpensDescription')}
        htmlFor="opens_mode"
      />
      <Inputs>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="opens-merged"
            value="merged"
            checked={opens === 'merged'}
            onCheck={setOpensMode}
            automationId="opens-merged-radio"
          />
          <label htmlFor="opens-merged">
            {t('humanAndMachineOpensMerged')}
          </label>
        </div>
        <div className="mailpoet-settings-inputs-row">
          <Radio
            id="opens-separated"
            value="separated"
            checked={opens === 'separated'}
            onCheck={setOpensMode}
            automationId="opens-separated-radio"
          />
          <label htmlFor="opens-separated">
            {t('humanAndMachineOpensSeparated')}
          </label>
        </div>
      </Inputs>
    </>
  );
}
