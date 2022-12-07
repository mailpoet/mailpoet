import { ErrorBoundary } from 'common';
import { BelowPostsSettings } from './settings_panels/below_posts_settings';
import { PopUpSettings } from './settings_panels/popup_settings';
import { OtherSettings } from './settings_panels/other_settings';
import { FixedBarSettings } from './settings_panels/fixed_bar_settings';
import { SlideInSettings } from './settings_panels/slide_in_settings';

type Props = {
  activePanel: string;
};

export function SettingsPanel({ activePanel }: Props): JSX.Element {
  return (
    <div className="mailpoet-styles-settings">
      {activePanel === 'others' && (
        <ErrorBoundary>
          <OtherSettings />
        </ErrorBoundary>
      )}
      {activePanel === 'below_posts' && (
        <ErrorBoundary>
          <BelowPostsSettings />
        </ErrorBoundary>
      )}
      {activePanel === 'fixed_bar' && (
        <ErrorBoundary>
          <FixedBarSettings />
        </ErrorBoundary>
      )}
      {activePanel === 'popup' && (
        <ErrorBoundary>
          <PopUpSettings />
        </ErrorBoundary>
      )}
      {activePanel === 'slide_in' && (
        <ErrorBoundary>
          <SlideInSettings />
        </ErrorBoundary>
      )}
    </div>
  );
}
