import classnames from 'classnames';
import { Button as WpButton } from '@wordpress/components';
import {
  ButtonAsAnchorProps,
  ButtonAsButtonProps,
} from '@wordpress/components/build-types/button/types';
import { WordPressComponentProps } from '@wordpress/components/build-types/ui/context';

type ExtendedProps = {
  variant: ButtonAsAnchorProps['variant'] | 'sidebar-primary';
  centered?: boolean;
};

type Props = (
  | Omit<
      WordPressComponentProps<ButtonAsButtonProps, 'button', false>,
      keyof ExtendedProps
    >
  | Omit<
      WordPressComponentProps<ButtonAsAnchorProps, 'a', false>,
      keyof ExtendedProps
    >
) &
  ExtendedProps;

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
