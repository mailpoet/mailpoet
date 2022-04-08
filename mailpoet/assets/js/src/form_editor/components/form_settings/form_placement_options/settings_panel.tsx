import BelowPostsSettings from './settings_panels/below_posts_settings';
import PopUpSettings from './settings_panels/popup_settings';
import OtherSettings from './settings_panels/other_settings';
import FixedBarSettings from './settings_panels/fixed_bar_settings';
import SlideInSettings from './settings_panels/slide_in_settings';

type Props = {
  activePanel: string;
};

function SettingsPanel({ activePanel }: Props): JSX.Element {
  return (
    <div className="mailpoet-styles-settings">
      {activePanel === 'others' && <OtherSettings />}
      {activePanel === 'below_post' && <BelowPostsSettings />}
      {activePanel === 'fixed_bar' && <FixedBarSettings />}
      {activePanel === 'popup' && <PopUpSettings />}
      {activePanel === 'slide_in' && <SlideInSettings />}
    </div>
  );
}

export default SettingsPanel;
