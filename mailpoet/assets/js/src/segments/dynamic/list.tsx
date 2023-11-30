import { withRouter } from 'react-router-dom';
import { TopBarWithBeamer } from 'common/top-bar/top-bar';
import { ListingHeader } from 'segments/dynamic/list/listing-header';
import { ListingTabs } from './list/listing-tabs';

function DynamicSegmentListComponent(): JSX.Element {
  return (
    <>
      <TopBarWithBeamer hideScreenOptions />
      <ListingHeader />
      <ListingTabs />
    </>
  );
}

export const DynamicSegmentList = withRouter(DynamicSegmentListComponent);
