import React from 'react';
import classnames from 'classnames';

type Props = {
  children?: React.ReactNode,
  dimension?: 'small',
};

const Column = ({
  children,
  dimension,
}: Props) => (
  <div
    className={
      classnames(
        'mailpoet-grid-column',
        {
          [`mailpoet-grid-column-${dimension}`]: dimension,
        }
      )
    }
  >
    {children}
  </div>
);

export default Column;
