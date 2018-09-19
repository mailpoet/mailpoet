import React from 'react';
import ReactDOMServer from 'react-dom/server';
import classNames from 'classnames';
import MailPoet from 'mailpoet';

const InAppAnnouncementDot = props => (
  <span
    role="button"
    tabIndex="-1"
    className={classNames('mailpoet_in_app_announcement_pulsing_dot', props.className)}
    onClick={() => {
      MailPoet.Modal.popup({
        template: ReactDOMServer.renderToString(props.children),
        width: props.width,
        height: props.height,
      });
    }}
  />
);

InAppAnnouncementDot.propTypes = {
  width: React.PropTypes.string,
  height: React.PropTypes.string,
  className: React.PropTypes.string,
  children: React.PropTypes.element.isRequired,
};

InAppAnnouncementDot.defaultProps = {
  width: 'auto',
  height: 'auto',
  className: null,
};

module.exports = InAppAnnouncementDot;
