import React from 'react';
import classnames from 'classnames';
import Select, { Props as ReactSelectProps } from 'react-select';

type Props = ReactSelectProps & {
  dimension?: 'small',
  isFullWidth?: boolean,
  iconStart?: JSX.Element,
};

const LabelRenderer = (data: any) => (
  <div className="mailpoet-form-react-select-option">
    {data.tag && <span className="mailpoet-form-react-select-tag">{data.tag}</span>}
    <span className="mailpoet-form-react-select-text"><span>{data.label}</span></span>
    {data.count && <span className="mailpoet-form-react-select-count">{data.count}</span>}
  </div>
);

const Option = (props: any) => (
  <div
    ref={props.innerRef}
    {...props.innerProps} // eslint-disable-line react/jsx-props-no-spreading
    className={
      classnames({
        'mailpoet-form-react-select__option': true,
        'mailpoet-form-react-select__option--is-disabled': props.isDisabled,
        'mailpoet-form-react-select__option--is-focused': props.isFocused,
        'mailpoet-form-react-select__option--is-selected': props.isSelected,
      })
    }
  >
    {LabelRenderer(props.data)}
  </div>
);

const SingleValue = (props: any) => (
  <div
    {...props.innerProps} // eslint-disable-line react/jsx-props-no-spreading
    className={
      classnames({
        'mailpoet-form-react-select__single-value': true,
        'mailpoet-form-react-select__single-value--is-disabled': props.isDisabled,
      })
    }
  >
    {LabelRenderer(props.data)}
  </div>
);

const MultiValueLabel = (props: any) => (
  <div
    {...props.innerProps} // eslint-disable-line react/jsx-props-no-spreading
    className="mailpoet-form-react-select__multi-value__label"
  >
    {LabelRenderer(props.data)}
  </div>
);

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
          'mailpoet-disabled': props.disabled,
          'mailpoet-full-width': isFullWidth,
        }
      )
    }
  >
    {iconStart}
    <Select
      className="mailpoet-form-react-select"
      classNamePrefix="mailpoet-form-react-select"
      components={{ Option, SingleValue, MultiValueLabel }}
      {...props} // eslint-disable-line react/jsx-props-no-spreading
    />
  </div>
);

export default ReactSelect;
