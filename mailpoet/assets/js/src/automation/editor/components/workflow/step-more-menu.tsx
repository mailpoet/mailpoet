import { DropdownMenu } from '@wordpress/components';
import { moreVertical, trash } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { Step as StepData } from './types';

type Props = {
  step: StepData;
};

export function StepMoreMenu({ step }: Props): JSX.Element {
  return (
    <div className="mailpoet-automation-step-more-menu">
      <DropdownMenu
        label={__('More', 'mailpoet')}
        icon={moreVertical}
        controls={[
          {
            title: __('Delete step', 'mailpoet'),
            icon: trash,
          },
        ]}
        popoverProps={{ position: 'bottom right' }}
        toggleProps={{ isSmall: true }}
      />
    </div>
  );
}
