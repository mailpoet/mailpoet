import PropTypes from 'prop-types';
import { EllipsisMenu, MenuItem } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { WorkflowProps, WorkflowPropsShape } from '../workflow';

export function More({ workflow }: WorkflowProps): JSX.Element {
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

More.propTypes = {
  workflow: PropTypes.shape(WorkflowPropsShape).isRequired,
};
