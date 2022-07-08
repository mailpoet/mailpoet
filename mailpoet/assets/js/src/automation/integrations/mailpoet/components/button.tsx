import classnames from 'classnames';
import { Button as WpButton } from '@wordpress/components';

type ExtendedProps = {
  variant: WpButton.ButtonVariant | 'sidebar-primary';
  centered?: boolean;
};

type Props =
  | (Omit<WpButton.ButtonProps, keyof ExtendedProps> & ExtendedProps)
  | (Omit<WpButton.AnchorProps, keyof ExtendedProps> & ExtendedProps);

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
