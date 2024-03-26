/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
  __experimentalItemGroup as ItemGroup,
  __experimentalItem as Item,
  __experimentalVStack as VStack,
  __experimentalHStack as HStack,
  __experimentalHeading as Heading,
  __experimentalNavigatorButton as NavigatorButton,
  FlexItem,
  Card,
  CardBody,
} from '@wordpress/components';
import { useEmailStyles } from '../../../hooks';

function ElementItem({ element, label }: { element: string; label: string }) {
  const { styles } = useEmailStyles();

  const { fontFamily, fontStyle, fontWeight, letterSpacing } =
    styles.typography;

  const extraStyles =
    element === 'link'
      ? {
          textDecoration: 'underline',
        }
      : {};

  const navigationButtonLabel = sprintf(
    // translators: %s: is a subset of Typography, e.g., 'text' or 'links'.
    __('Typography %s styles'),
    label,
  );

  return (
    <Item>
      <NavigatorButton
        path={`/typography/${element}`}
        aria-label={navigationButtonLabel}
      >
        <HStack justify="flex-start">
          <FlexItem
            className="edit-site-global-styles-screen-typography__indicator"
            style={{
              fontFamily: fontFamily ?? 'serif',
              fontStyle,
              fontWeight,
              letterSpacing,
              ...extraStyles,
            }}
          >
            {__('Aa')}
          </FlexItem>
          <FlexItem>{label}</FlexItem>
        </HStack>
      </NavigatorButton>
    </Item>
  );
}

export function TypographyPanel() {
  return (
    <Card size="small" variant="primary" isBorderless>
      <CardBody>
        <VStack spacing={3}>
          <Heading level={3} className="edit-site-global-styles-subtitle">
            {__('Elements')}
          </Heading>
          <ItemGroup isBordered isSeparated size="small">
            <ElementItem element="text" label={__('Text', 'mailpoet')} />
            <ElementItem element="link" label={__('Link', 'mailpoet')} />
            <ElementItem element="heading" label={__('Heading', 'mailpoet')} />
            <ElementItem element="button" label={__('Button', 'mailpoet')} />
          </ItemGroup>
        </VStack>
      </CardBody>
    </Card>
  );
}

export default TypographyPanel;
