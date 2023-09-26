import { Spinner } from '@wordpress/components';
import { CSSProperties, ReactNode } from 'react';
import classnames from 'classnames';
import Select, {
  OptionProps,
  Props as ReactSelectProps,
  components,
} from 'react-select';

export type Props = ReactSelectProps & {
  dimension?: 'small';
  disabled?: boolean;
  isFullWidth?: boolean;
  iconStart?: JSX.Element;
  automationId?: string;
  isLoadingMore?: boolean;
};

type LabelRendererProps = {
  label: ReactNode;
  count?: ReactNode;
  tag?: ReactNode;
};

function LabelRenderer(data: LabelRendererProps) {
  return (
    <div className="mailpoet-form-react-select-option">
      {data.tag && (
        <span className="mailpoet-form-react-select-tag">{data.tag}</span>
      )}
      <span>{data.label}</span>
      {data.count !== undefined && (
        <span className="mailpoet-form-react-select-count">{data.count}</span>
      )}
    </div>
  );
}

type OptionData = {
  style: CSSProperties;
  label: ReactNode;
  count?: ReactNode;
  tag?: ReactNode;
};

function Option(props: OptionProps<OptionData>) {
  let style = {};
  if (props.data?.style) {
    style = props.data.style;
  }
  return (
    <div
      style={style}
      ref={props.innerRef}
      {...props.innerProps}
      className={classnames({
        'mailpoet-form-react-select__option': true,
        'mailpoet-form-react-select__option--is-disabled': props.isDisabled,
        'mailpoet-form-react-select__option--is-focused': props.isFocused,
        'mailpoet-form-react-select__option--is-selected': props.isSelected,
      })}
    >
      {LabelRenderer(props.data)}
    </div>
  );
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function SingleValue(props: any) {
  return (
    <div
      {...props.innerProps}
      className={classnames({
        'mailpoet-form-react-select__single-value': true,
        'mailpoet-form-react-select__single-value--is-disabled':
          props.isDisabled,
      })}
    >
      {LabelRenderer(props.data as LabelRendererProps)}
    </div>
  );
}

type MultiValueLabelProps = {
  data: {
    style: CSSProperties;
    label: ReactNode;
    count?: ReactNode;
    tag?: ReactNode;
  };
  // eslint-disable-next-line @typescript-eslint/ban-types -- we need to match react-select
  innerProps: object;
};

function MultiValueLabel(props: MultiValueLabelProps) {
  return (
    <div
      {...props.innerProps}
      className="mailpoet-form-react-select__multi-value__label"
    >
      {LabelRenderer(props.data)}
    </div>
  );
}

function LoadingIndicator() {
  return (
    <div className="mailpoet-form-react-select__option mailpoet_centered">
      <Spinner />
    </div>
  );
}

export function ReactSelect({
  dimension,
  isFullWidth,
  iconStart,
  automationId,
  isLoadingMore,
  ...props
}: Props) {
  return (
    <div
      className={classnames('mailpoet-form-input', 'mailpoet-form-select', {
        [`mailpoet-form-input-${dimension}`]: dimension,
        'mailpoet-disabled': props.disabled,
        'mailpoet-full-width': isFullWidth,
      })}
      data-automation-id={automationId}
    >
      {iconStart}
      <Select
        className="mailpoet-form-react-select"
        classNamePrefix="mailpoet-form-react-select"
        components={{
          Option,
          SingleValue,
          MultiValueLabel,
          MenuList: (menuProps) => (
            <components.MenuList {...menuProps}>
              {menuProps.children}
              {isLoadingMore && <LoadingIndicator />}
            </components.MenuList>
          ),
        }}
        {...props}
      />
    </div>
  );
}
