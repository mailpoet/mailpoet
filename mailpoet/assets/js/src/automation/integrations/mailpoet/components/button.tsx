import classnames from 'classnames';
import { Button as WpButton } from '@wordpress/components';
import { ButtonAsAnchorProps } from '@wordpress/components/build-types/button/types';

type ExtendedProps = {
  variant: ButtonAsAnchorProps['variant'] | 'sidebar-primary';
  centered?: boolean;
};

type Props = Omit<ButtonAsAnchorProps, keyof ExtendedProps> & ExtendedProps;

export function Button({ centered, variant, ...props }: Props): JSX.Element {
  return (
    <WpButton
      className={classnames([
        variant === 'sidebar-primary'
          ? 'mailpoet-automation-button-sidebar-primary'
          : '',
        centered ? 'mailpoet-automation-button-centered' : '',
      ])}
      variant={variant === 'sidebar-primary' ? 'primary' : variant}
      {...props}
    />
  );
}
