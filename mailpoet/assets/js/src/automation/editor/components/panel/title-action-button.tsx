import { Button } from '@wordpress/components';
import { ComponentProps } from 'react';

type Props = ComponentProps<typeof Button>;

export function TitleActionButton(props: Props): JSX.Element {
  return (
    <div className="mailpoet-automation-panel-plain-body-title-action">
      <Button {...props} />
    </div>
  );
}
