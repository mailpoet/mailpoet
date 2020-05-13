import React from 'react';
import classnames from 'classnames';
import Select, { Props as ReactSelectProps } from 'react-select';

type Props = ReactSelectProps & {
  dimension?: 'small',
  isFullWidth?: boolean,
  iconStart?: JSX.Element,
};

const ReactSelect = ({
  dimension,
  isFullWidth,
  iconStart,
  ...props
}: Props) => (
  <div
    className={
      classnames(
        'mailpoet-form-input',
        'mailpoet-form-select',
        {
          [`mailpoet-form-input-${dimension}`]: dimension,
          'mailpoet-full-width': isFullWidth,
        }
      )
    }
  >
    {iconStart}
    <Select
      className="mailpoet-form-react-select"
      classNamePrefix="mailpoet-form-react-select"
      {...props} // eslint-disable-line react/jsx-props-no-spreading
    />
  </div>
);

export default ReactSelect;
