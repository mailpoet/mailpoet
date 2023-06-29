import { Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Automation } from '../../../../../../editor/components/automation';
import { storeName } from '../../../store';
import { AutomationPlaceholder } from './automation_placeholder';
import { initHooks } from './hooks';
initHooks();
export function AutomationFlow(): JSX.Element {
  const { section } = useSelect(
    (s) => ({
      section: s(storeName).getSection('automation_flow'),
    }),
    [],
  );

  const isLoading = section.data === undefined;

  if (isLoading) {
    return <AutomationPlaceholder />;
  }

  return (
    <>
      {section.data.tree_is_inconsistent && (
        <div className="mailpoet-automation-editor-automation-notices">
          <Notice
            status="warning"
            isDismissible={false}
            className="mailpoet-automation-flow-notice"
          >
            <p>
              {__(
                'In this time period, the automation structure did change and therefore some numbers in the flow chart might not be accurate.',
                'mailpoet',
              )}
            </p>
          </Notice>
        </div>
      )}
      <Automation context="view" />
    </>
  );
}
