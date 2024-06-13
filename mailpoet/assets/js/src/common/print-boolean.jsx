import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

function PrintBoolean({
  truthy = __('Yes', 'mailpoet'),
  falsy = __('No', 'mailpoet'),
  unknown = __('Unknown', 'mailpoet'),
  children = null,
}) {
  return (
    <span>
      {(children === true && truthy) ||
        (children === false && falsy) ||
        unknown}
    </span>
  );
}

PrintBoolean.propTypes = {
  truthy: PropTypes.string,
  falsy: PropTypes.string,
  unknown: PropTypes.string,
  children: PropTypes.bool,
};

export { PrintBoolean };
