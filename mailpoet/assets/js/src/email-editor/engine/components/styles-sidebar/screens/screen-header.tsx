import {
  __experimentalHStack as HStack,
  __experimentalVStack as VStack,
  __experimentalSpacer as Spacer,
  __experimentalHeading as Heading,
  __experimentalView as View,
  __experimentalNavigatorToParentButton as NavigatorToParentButton,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronLeft } from '@wordpress/icons';

type Props = {
  title: string;
  description?: string;
  onBack?: () => void;
};

/**
 * Component for displaying the screen header and optional description based on site editor component:
 * https://github.com/WordPress/gutenberg/blob/7fa03fafeb421ab4c3604564211ce6007cc38e84/packages/edit-site/src/components/global-styles/header.js
 */
export function ScreenHeader({ title, description, onBack }: Props) {
  return (
    <VStack spacing={0}>
      <View>
        <Spacer marginBottom={0} paddingX={4} paddingY={3}>
          <HStack spacing={2}>
            <NavigatorToParentButton
              style={{ minWidth: 24, padding: 0 }}
              icon={chevronLeft}
              size="small"
              aria-label={__('Navigate to the previous view')}
              onClick={onBack}
            />
            <Spacer>
              {/* @ts-expect-error Heading component it's not typed properly in the current components version. */}
              <Heading
                className="mailpoet-email-editor__styles-header"
                level={2}
                size={13}
              >
                {title}
              </Heading>
            </Spacer>
          </HStack>
        </Spacer>
      </View>
      {description && (
        <p className="mailpoet-email-editor__styles-header-description">
          {description}
        </p>
      )}
    </VStack>
  );
}
