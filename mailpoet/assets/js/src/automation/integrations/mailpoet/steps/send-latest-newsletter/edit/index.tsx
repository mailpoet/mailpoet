import { Notice, PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { storeName } from '../../../../../editor/store';
import { AutomationEditorWindow } from '../../../../../editor/store/types';
import { automationHasTriggerList } from '../helper';

const invalidPlacementMessage = __(
  'This action needs a trigger list, such as "Someone subscribes".',
  'mailpoet',
);

export function Edit(): JSX.Element {
  const { automation, registry } = useSelect(
    (select) => ({
      automation: select(storeName).getAutomationData(),
      registry:
        select(storeName).getRegistry() ??
        (window as unknown as AutomationEditorWindow)
          .mailpoet_automation_registry,
    }),
    [],
  );

  const hasTriggerList = automationHasTriggerList(automation, registry);

  return (
    <PanelBody>
      {!hasTriggerList && (
        <Notice isDismissible={false} status="warning">
          {invalidPlacementMessage}
        </Notice>
      )}
      <p>
        {__(
          'MailPoet will choose the latest regular newsletter already sent to the list that triggered this automation when this step runs. The newsletter is sent with the normal MailPoet sending process.',
          'mailpoet',
        )}
      </p>
    </PanelBody>
  );
}
