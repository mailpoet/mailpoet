import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode,
  align?: 'center',
  dimension?: 'small',
};

const Column = ({
  children,
  align,
  dimension,
}: Props) => (
  <div
    className={
      classnames(
        'mailpoet-grid-column',
        {
          [`mailpoet-grid-column-${dimension}`]: dimension,
          [`mailpoet-grid-column-${align}`]: align,
        }
      )
    }
  >
    {children}
  </div>
);

export default Column;
