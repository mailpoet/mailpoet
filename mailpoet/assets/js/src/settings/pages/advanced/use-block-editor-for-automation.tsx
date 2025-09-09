import { t } from 'common/functions';
import { Radio } from 'common/form/radio/radio';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export function UseBlockEditorForAutomation() {
  const [enabled, setEnabled] = useSetting(
    'use_block_email_editor_for_automation_emails',
    'enabled',
  );

  return (
    <>
      <Label
        title={t('useBlockEditorForAutomationTitle')}
        description={t('useBlockEditorForAutomationDescription')}
        htmlFor=""
      />
      <Inputs>
        <Radio
          id="use-block-editor-for-automation-enabled"
          value="1"
          checked={enabled === '1'}
          onCheck={setEnabled}
          automationId="block-editor-for-automation-enabled"
        />
        <label htmlFor="use-block-editor-for-automation-enabled">
          {t('yes')}
        </label>
        <span className="mailpoet-gap" />
        <Radio
          id="use-block-editor-for-automation-disabled"
          value=""
          checked={enabled === ''}
          onCheck={setEnabled}
          automationId="block-editor-for-automation-disabled"
        />
        <label htmlFor="use-block-editor-for-automation-disabled">
          {t('no')}
        </label>
      </Inputs>
    </>
  );
}
