import { ReactNode } from 'react';
import { Flex, FlexBlock } from '@wordpress/components';

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
