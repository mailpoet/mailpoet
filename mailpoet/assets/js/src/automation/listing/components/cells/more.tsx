import { EllipsisMenu, MenuItem } from '@woocommerce/components/build';
import { __ } from '@wordpress/i18n';
import { Workflow } from '../../workflow';

type Props = {
  workflow: Workflow;
};

export function More({ workflow }: Props): JSX.Element {
  return (
    <EllipsisMenu
      label={`Actions for ${workflow.name}`}
      renderContent={() => (
        <div>
          <MenuItem onInvoke={() => {}}>
            <p>{__('Rename automation', 'mailpoet')}</p>
          </MenuItem>
          <MenuItem onInvoke={() => {}}>
            <p>{__('Statistics', 'mailpoet')}</p>
          </MenuItem>
          <MenuItem onInvoke={() => {}}>
            <p>{__('Duplicate', 'mailpoet')}</p>
          </MenuItem>
          <MenuItem onInvoke={() => {}}>
            <p>{__('Move to trash', 'mailpoet')}</p>
          </MenuItem>
        </div>
      )}
    />
  );
}
