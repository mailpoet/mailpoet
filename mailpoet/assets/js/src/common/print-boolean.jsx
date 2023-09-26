import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

function PrintBoolean(props) {
  return (
    <span>
      {(props.children === true && props.truthy) ||
        (props.children === false && props.falsy) ||
        props.unknown}
    </span>
  );
}

PrintBoolean.propTypes = {
  truthy: PropTypes.string,
  falsy: PropTypes.string,
  unknown: PropTypes.string,
  children: PropTypes.bool,
};

PrintBoolean.defaultProps = {
  truthy: __('Yes', 'mailpoet'),
  falsy: __('No', 'mailpoet'),
  unknown: __('Unknown', 'mailpoet'),
  children: null,
};

export { PrintBoolean };
