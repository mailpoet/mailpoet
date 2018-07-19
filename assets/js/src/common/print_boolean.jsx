import React from 'react';
import MailPoet from 'mailpoet';

const PrintBoolean = props => (
  <span>
    {(props.children === true && props.truthy) ||
      (props.children === false && props.falsy) ||
      (props.unknown)}
  </span>
);

PrintBoolean.propTypes = {
  truthy: React.PropTypes.string,
  falsy: React.PropTypes.string,
  unknown: React.PropTypes.string,
  children: React.PropTypes.bool,
};

PrintBoolean.defaultProps = {
  truthy: MailPoet.I18n.t('yes'),
  falsy: MailPoet.I18n.t('no'),
  unknown: MailPoet.I18n.t('unknown'),
  children: null,
};

module.exports = PrintBoolean;
