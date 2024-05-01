import {
  __experimentalNavigatorProvider as NavigatorProvider,
  __experimentalNavigatorScreen as NavigatorScreen,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ComplementaryArea } from '@wordpress/interface';
import { ComponentProps } from 'react';
import { styles } from '@wordpress/icons';
import { storeName, stylesSidebarId } from '../../store';
import {
  ScreenTypography,
  ScreenTypographyElement,
  ScreenLayout,
  ScreenRoot,
  ScreenColors,
} from './screens';

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
      <NavigatorProvider initialPath="/">
        <NavigatorScreen path="/">
          <ScreenRoot />
        </NavigatorScreen>

        <NavigatorScreen path="/typography">
          <ScreenTypography />
        </NavigatorScreen>

        <NavigatorScreen path="/typography/text">
          <ScreenTypographyElement element="text" />
        </NavigatorScreen>

        <NavigatorScreen path="/typography/link">
          <ScreenTypographyElement element="link" />
        </NavigatorScreen>

        <NavigatorScreen path="/typography/heading">
          <ScreenTypographyElement element="heading" />
        </NavigatorScreen>

        <NavigatorScreen path="/typography/button">
          <ScreenTypographyElement element="button" />
        </NavigatorScreen>

        <NavigatorScreen path="/colors">
          <ScreenColors />
        </NavigatorScreen>

        <NavigatorScreen path="/layout">
          <ScreenLayout />
        </NavigatorScreen>
      </NavigatorProvider>
    </ComplementaryArea>
  );
}
