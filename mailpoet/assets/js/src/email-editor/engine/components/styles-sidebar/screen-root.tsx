import {
  __experimentalVStack as VStack,
  Card,
  CardBody,
  CardMedia,
} from '@wordpress/components';
import RootMenu from './root-menu';
import { Preview } from './preview';

export function ScreenRoot(): JSX.Element {
  return (
    <Card
      size="small"
      className="edit-site-global-styles-screen-root"
      variant="primary"
    >
      <CardBody>
        <VStack spacing={4}>
          <Card>
            <CardMedia>
              <Preview />
            </CardMedia>
          </Card>
          <RootMenu />
        </VStack>
      </CardBody>
    </Card>
  );
}
