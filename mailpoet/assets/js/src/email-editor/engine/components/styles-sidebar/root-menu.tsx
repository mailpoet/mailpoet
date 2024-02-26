import {
  __experimentalItemGroup as ItemGroup,
  __experimentalItem as Item,
  __experimentalHStack as HStack,
  __experimentalNavigatorButton as NavigatorButton,
  Icon,
  FlexItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { typography, color, layout } from '@wordpress/icons';

function RootMenu() {
  return (
    <ItemGroup>
      <NavigatorButton path="/typography">
        <Item>
          <HStack justify="flex-start">
            <Icon icon={typography} size={24} />
            <FlexItem>{__('Typography', 'mailpoet')}</FlexItem>
          </HStack>
        </Item>
      </NavigatorButton>
      <NavigatorButton path="/colors">
        <Item>
          <HStack justify="flex-start">
            <Icon icon={color} size={24} />
            <FlexItem>{__('Colors', 'mailpoet')}</FlexItem>
          </HStack>
        </Item>
      </NavigatorButton>
      <NavigatorButton path="/layout">
        <Item>
          <HStack justify="flex-start">
            <Icon icon={layout} size={24} />
            <FlexItem>{__('Layout', 'mailpoet')}</FlexItem>
          </HStack>
        </Item>
      </NavigatorButton>
    </ItemGroup>
  );
}

export default RootMenu;
