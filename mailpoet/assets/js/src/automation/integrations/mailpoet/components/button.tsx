import classnames from 'classnames';
import { Button as WpButton } from '@wordpress/components';
import {
  ButtonAsAnchorProps,
  ButtonAsButtonProps,
} from '@wordpress/components/src/button/types';
import { WordPressComponentProps } from '@wordpress/components/build-types/ui/context';

type ExtendedProps = {
  variant: ButtonAsAnchorProps['variant'] | 'sidebar-primary';
  centered?: boolean;
};

type Props =
  | WordPressComponentProps<
      Omit<ButtonAsButtonProps, keyof ExtendedProps> & ExtendedProps,
      'button',
      false
    >
  | WordPressComponentProps<
      Omit<ButtonAsAnchorProps, keyof ExtendedProps> & ExtendedProps,
      'a',
      false
    >;

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
