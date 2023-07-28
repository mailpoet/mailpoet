import { Tooltip } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { OrderData, storeName } from '../../../../store';
import { MailPoet } from '../../../../../../../../mailpoet';
import { storeName as editorStoreName } from '../../../../../../../editor/store';

type Props = {
  order: OrderData;
  isSample?: boolean;
};

export function EmailCell({ order, isSample }: Props): JSX.Element {
  const { automation } = useSelect((s) => ({
    automation: s(editorStoreName).getAutomationData(),
  }));
  const { openPremiumModalForSampleData } = useDispatch(storeName);

  return (
    <Tooltip text={__('View in automation', 'mailpoet')}>
      <a
        onClick={(event) => {
          if (isSample) {
            event.preventDefault();
            void openPremiumModalForSampleData();
          }
        }}
        href={
          isSample
            ? ''
            : addQueryArgs(MailPoet.urls.automationEditor, {
                id: automation.id,
              })
        }
      >
        {`${order.email.subject}`}
      </a>
    </Tooltip>
  );
}
