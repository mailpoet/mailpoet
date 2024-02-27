import {
  Panel,
  __experimentalNavigatorProvider as NavigatorProvider,
  __experimentalNavigatorScreen as NavigatorScreen,
  __experimentalNavigatorToParentButton as NavigatorToParentButton,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ComplementaryArea } from '@wordpress/interface';
import { ComponentProps } from 'react';
import { styles } from '@wordpress/icons';
import { storeName, stylesSidebarId } from '../../store';
import { ScreenRoot } from './screen-root';

type Props = ComponentProps<typeof ComplementaryArea>;

export function StylesSidebar(props: Props): JSX.Element {
  return (
    <ComplementaryArea
      identifier={stylesSidebarId}
      className="mailpoet-email-editor__styles-panel"
      header={__('Styles', 'mailpoet')}
      icon={styles}
      scope={storeName}
      smallScreenTitle={__('No title', 'mailpoet')}
      {...props}
    >
      <Panel>
        <NavigatorProvider initialPath="/">
          <NavigatorScreen path="/">
            <ScreenRoot />
          </NavigatorScreen>

          <NavigatorScreen path="/typography">
            <NavigatorToParentButton>Back</NavigatorToParentButton>
            <div>TODO: Typography screen</div>
          </NavigatorScreen>

          <NavigatorScreen path="/colors">
            <NavigatorToParentButton>Back</NavigatorToParentButton>
            <div>TODO: Typography screen</div>
          </NavigatorScreen>

          <NavigatorScreen path="/layout">
            <NavigatorToParentButton>Back</NavigatorToParentButton>
            <div>TODO: Typography screen</div>
          </NavigatorScreen>
        </NavigatorProvider>
      </Panel>
    </ComplementaryArea>
  );
}
