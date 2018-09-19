import React from 'react';
import InAppAnnouncementDot from './in_app_announcement_dot.jsx';

const InAppAnnouncement = (props) => {
  if (props.newUser !== null &&
    window.mailpoet_is_new_user !== props.newUser
  ) {
    return null;
  }

  if (props.validUntil < (new Date().getTime() / 1000)) {
    return null;
  }

  return (
    <InAppAnnouncementDot
      className={props.className}
      width={props.width}
      height={props.height}
    >
      {props.children}
    </InAppAnnouncementDot>);
};

InAppAnnouncement.propTypes = {
  width: React.PropTypes.string,
  height: React.PropTypes.string,
  className: React.PropTypes.string,
  children: React.PropTypes.element.isRequired,
  validUntil: React.PropTypes.number,
  newUser: (props, propName, componentName) => {
    const propValue = props[propName];
    if (propValue !== null && propValue !== true && propValue !== false) {
      return new Error(`Invalid property in ${componentName}. newUser must be of type boolean`);
    }
    if (typeof window.mailpoet_is_new_user === 'undefined') {
      return new Error(`Missing data for evaluation of ${componentName} display condition. ${propName} requires window.mailpoet_is_new_user`);
    }
    return null;
  },
};

InAppAnnouncement.defaultProps = {
  width: '900px',
  height: '600px',
  className: null,
  validUntil: null,
  newUser: null,
};

module.exports = InAppAnnouncement;
