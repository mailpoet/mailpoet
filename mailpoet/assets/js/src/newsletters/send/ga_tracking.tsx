import _ from 'underscore';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import { Field } from '../../form/types';

// Track once per page load
const trackCampaignNameTyped = _.once(() =>
  MailPoet.trackEvent('User has typed a GA campaign name'),
);
const tipLink =
  'https://kb.mailpoet.com/article/187-track-your-newsletters-subscribers-in-google-analytics';
const tip = ReactStringReplace(
  MailPoet.I18n.t('gaCampaignTip'),
  /\[link\](.*?)\[\/link\]/g,
  (match, i) => (
    <span key={i}>
      <br />
      <a
        href={tipLink}
        data-beacon-article="58b597d12c7d3a576d359144"
        target="_blank"
        rel="noopener noreferrer"
        className="mailpoet-link"
      >
        {match}
      </a>
    </span>
  ),
);

const field: Field = {
  name: 'ga_campaign',
  label: MailPoet.I18n.t('gaCampaignLine'),
  tip,
  type: 'text',
  onBeforeChange: trackCampaignNameTyped,
};
export default field;
