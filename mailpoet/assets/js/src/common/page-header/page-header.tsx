import { ComponentType, ReactNode } from 'react';
import { Flex as WpFlex, FlexBlock } from '@wordpress/components';

// direction is typed as string but supports string[], which is needed to make the component responsive
const Flex = WpFlex as ComponentType<
  Omit<WpFlex.Props, 'direction'> & {
    direction: WpFlex.Props['direction'] | WpFlex.Props['direction'][];
  }
>;

type Props = {
  heading: ReactNode;
  headingPrefix?: ReactNode;
  className?: string;
  children?: ReactNode;
};

export function PageHeader({
  heading,
  headingPrefix,
  className,
  children,
}: Props): JSX.Element {
  return (
    <Flex
      className={`mailpoet-page-header ${className}`}
      direction={['column', 'row']}
      gap="16px"
    >
      <FlexBlock>
        <Flex direction="row" gap="4px">
          {headingPrefix}
          <FlexBlock>
            <h1 className="wp-heading-inline">{heading}</h1>
          </FlexBlock>
        </Flex>
      </FlexBlock>
      {children}
    </Flex>
  );
}
