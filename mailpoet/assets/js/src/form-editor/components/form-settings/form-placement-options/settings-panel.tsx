import { ErrorBoundary } from 'common';
import { BelowPostsSettings } from './settings-panels/below-posts-settings';
import { PopUpSettings } from './settings-panels/popup-settings';
import { OtherSettings } from './settings-panels/other-settings';
import { FixedBarSettings } from './settings-panels/fixed-bar-settings';
import { SlideInSettings } from './settings-panels/slide-in-settings';

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
