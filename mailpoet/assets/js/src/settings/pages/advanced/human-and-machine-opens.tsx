import { ReactElement } from 'react';
import { Radio } from 'common/form/radio/radio';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';
import { __ } from '@wordpress/i18n';

export function HumanAndMachineOpens(): ReactElement {
  const [opens, setOpensMode] = useSetting('tracking', 'opens');

  return (
    <>
      <Label
        title={__('Human and machine opens', 'mailpoet')}
        description={__(
          'Choose how human and machine opens should be displayed.',
          'mailpoet',
        )}
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
            {__(
              'Merged – both are counted as total opens. Similar to other email marketing tools.',
              'mailpoet',
            )}
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
            {__(
              'Separated – only human opens are counted as total opens. More accurate, but the numbers tend to be lower.',
              'mailpoet',
            )}
          </label>
        </div>
      </Inputs>
    </>
  );
}
