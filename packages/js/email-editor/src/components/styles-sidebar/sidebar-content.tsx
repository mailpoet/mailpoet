/**
 * External dependencies
 */
import {
	__experimentalNavigatorProvider as NavigatorProvider,
	__experimentalNavigatorScreen as NavigatorScreen,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	ScreenColors,
	ScreenLayout,
	ScreenRoot,
	ScreenTypography,
	ScreenTypographyElement,
} from './screens';

export function StylesSidebarContent() {
	return (
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
	);
}
