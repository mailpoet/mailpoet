import classnames from 'classnames';
import { Button as WpButton } from '@wordpress/components';
import { ButtonAsAnchorProps } from '@wordpress/components/build-types/button/types';

type ExtendedProps = {
  variant: ButtonAsAnchorProps['variant'] | 'sidebar-primary';
  centered?: boolean;
};

type UpdateUnionProps<T, U> = T extends unknown ? Omit<T, keyof U> & U : never;

type Props = UpdateUnionProps<
  React.ComponentProps<typeof WpButton>,
  ExtendedProps
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
