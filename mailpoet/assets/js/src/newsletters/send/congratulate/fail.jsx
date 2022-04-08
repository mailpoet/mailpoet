import { useState } from 'react';
import PropTypes from 'prop-types';
import ReactStringReplace from 'react-string-replace';
import MailPoet from 'mailpoet';

import { Button } from 'common';
import Heading from 'common/typography/heading/heading';

function Fail(props) {
  const [isClosing, setIsClosing] = useState(false);
  return (
    <div>
      <Heading level={1}>
        {MailPoet.I18n.t('congratulationsSendFailHeader')}
      </Heading>
      <Heading level={3}>
        {ReactStringReplace(
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
              {match}
            </a>
          ),
        )}
      </Heading>
      <div className="mailpoet-gap-large" />
      <div className="mailpoet-gap-large" />
      <img
        src={window.mailpoet_congratulations_error_image}
        alt=""
        width="500"
      />
      <div className="mailpoet-gap-large" />
      <Button
        dimension="small"
        type="button"
        onClick={() => {
          props.failClicked();
          setIsClosing(true);
        }}
        withSpinner={isClosing}
      >
        {MailPoet.I18n.t('close')}
      </Button>
    </div>
  );
}

Fail.propTypes = {
  failClicked: PropTypes.func.isRequired,
};

export default Fail;
