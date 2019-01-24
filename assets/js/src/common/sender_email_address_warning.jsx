import React from 'react';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';

/* https://github.com/mailcheck/mailcheck/wiki/List-of-Popular-Domains */
const badDomains = [
  /* Default domains included */
  'aol.com', 'att.net', 'comcast.net', 'facebook.com', 'gmail.com', 'gmx.com', 'googlemail.com',
  'google.com', 'hotmail.com', 'hotmail.co.uk', 'mac.com', 'me.com', 'mail.com', 'msn.com',
  'live.com', 'sbcglobal.net', 'verizon.net', 'yahoo.com', 'yahoo.co.uk',

  /* Other global domains */
  'email.com', 'fastmail.fm', 'games.com' /* AOL */, 'gmx.net', 'hush.com', 'hushmail.com', 'icloud.com',
  'iname.com', 'inbox.com', 'lavabit.com', 'love.com' /* AOL */, 'outlook.com', 'pobox.com', 'protonmail.com',
  'rocketmail.com' /* Yahoo */, 'safe-mail.net', 'wow.com' /* AOL */, 'ygm.com' /* AOL */,
  'ymail.com' /* Yahoo */, 'zoho.com', 'yandex.com',

  /* United States ISP domains */
  'bellsouth.net', 'charter.net', 'cox.net', 'earthlink.net', 'juno.com',

  /* British ISP domains */
  'btinternet.com', 'virginmedia.com', 'blueyonder.co.uk', 'freeserve.co.uk', 'live.co.uk',
  'ntlworld.com', 'o2.co.uk', 'orange.net', 'sky.com', 'talktalk.co.uk', 'tiscali.co.uk',
  'virgin.net', 'wanadoo.co.uk', 'bt.com',

  /* Domains used in Asia */
  'sina.com', 'sina.cn', 'qq.com', 'naver.com', 'hanmail.net', 'daum.net', 'nate.com', 'yahoo.co.jp', 'yahoo.co.kr', 'yahoo.co.id', 'yahoo.co.in', 'yahoo.com.sg', 'yahoo.com.ph', '163.com', '126.com', 'aliyun.com', 'foxmail.com',

  /* French ISP domains */
  'hotmail.fr', 'live.fr', 'laposte.net', 'yahoo.fr', 'wanadoo.fr', 'orange.fr', 'gmx.fr', 'sfr.fr', 'neuf.fr', 'free.fr',

  /* German ISP domains */
  'gmx.de', 'hotmail.de', 'live.de', 'online.de', 't-online.de' /* T-Mobile */, 'web.de', 'yahoo.de',

  /* Italian ISP domains */
  'libero.it', 'virgilio.it', 'hotmail.it', 'aol.it', 'tiscali.it', 'alice.it', 'live.it', 'yahoo.it', 'email.it', 'tin.it', 'poste.it', 'teletu.it',

  /* Russian ISP domains */
  'mail.ru', 'rambler.ru', 'yandex.ru', 'ya.ru', 'list.ru',

  /* Belgian ISP domains */
  'hotmail.be', 'live.be', 'skynet.be', 'voo.be', 'tvcablenet.be', 'telenet.be',

  /* Argentinian ISP domains */
  'hotmail.com.ar', 'live.com.ar', 'yahoo.com.ar', 'fibertel.com.ar', 'speedy.com.ar', 'arnet.com.ar',

  /* Domains used in Mexico */
  'yahoo.com.mx', 'live.com.mx', 'hotmail.es', 'hotmail.com.mx', 'prodigy.net.mx',

  /* Domains used in Brazil */
  'yahoo.com.br', 'hotmail.com.br', 'outlook.com.br', 'uol.com.br', 'bol.com.br', 'terra.com.br', 'ig.com.br', 'itelefonica.com.br', 'r7.com', 'zipmail.com.br', 'globo.com', 'globomail.com', 'oi.com.br',
];

const SenderEmailAddressWarning = ({ emailAddress }) => {
  const emailAddressDomain = emailAddress.split('@').pop().toLowerCase();
  if (badDomains.indexOf(emailAddressDomain) > -1) {
    const userHostDomain = window.location.hostname.replace('www.', '');
    return (<React.Fragment>
      <p className="sender_email_address_warning">{MailPoet.I18n.t('senderEmailAddressWarning1')}</p>
      <p className="sender_email_address_warning">
        {ReactStringReplace(
          MailPoet.I18n.t('senderEmailAddressWarning2'),
          /(%suggested|%originalSender|<em>.*<\/em>)/,
          (match) => {
            if (match === '%suggested') return `info@${userHostDomain}`;
            if (match === '%originalSender') return <em key="sender-email">{ emailAddress }</em>;
            return <em key="reply-to">{match.replace(/<\/?em>/g, '')}</em>;
          }
        )}
      </p>
      <p className="sender_email_address_warning">
        <a
          href="https://kb.mailpoet.com/article/259-your-from-address-cannot-be-yahoo-com-gmail-com-outlook-com"
          target="_blank"
          rel="noopener noreferrer"
        >
          {MailPoet.I18n.t('senderEmailAddressWarning3')}
        </a>
      </p>
    </React.Fragment>);
  }
  return null;
};

SenderEmailAddressWarning.propTypes = {
  emailAddress: PropTypes.string.isRequired,
};

export default SenderEmailAddressWarning;
