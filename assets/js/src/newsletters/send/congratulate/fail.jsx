import React from 'react';
import PropTypes from 'prop-types';
import ReactStringReplace from 'react-string-replace';
import MailPoet from 'mailpoet';

function Fail(props) {
  return (
    <div className="mailpoet_centered">
      <h1>{MailPoet.I18n.t('congratulationsSendFailHeader')}</h1>
      <p>
        { ReactStringReplace(
          MailPoet.I18n.t('congratulationsSendFailExplain'),
          /\[link\](.*?)\[\/link\]/g,
          (match, i) => (
            <a
              key={i}
              target="_blank"
              rel="noopener noreferrer"
              href="https://kb.mailpoet.com/article/231-sending-does-not-work"
              data-beacon-article="5a0257ac2c7d3a272c0d7ad6"
            >
              { match }
            </a>
          )
        )}
      </p>
      <button type="button" className="button" onClick={props.failClicked}>{MailPoet.I18n.t('close')}</button>
    </div>
  );
}

Fail.propTypes = {
  failClicked: PropTypes.func.isRequired,
};

export default Fail;
