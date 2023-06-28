import { Tooltip } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { OrderData } from '../../../../store';
import { MailPoet } from '../../../../../../../../mailpoet';
import { storeName as editorStoreName } from '../../../../../../../editor/store';

export function EmailCell({ order }: { order: OrderData }): JSX.Element {
  const { automation } = useSelect((s) => ({
    automation: s(editorStoreName).getAutomationData(),
  }));

  return (
    <Tooltip text={__('View in automation', 'mailpoet')}>
      <a
        href={addQueryArgs(MailPoet.urls.automationEditor, {
          id: automation.id,
        })}
      >
        {`${order.email.subject}`}
      </a>
    </Tooltip>
  );
}
