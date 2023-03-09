import { ErrorBoundary } from 'common';
import { TaskList } from 'homepage/components/task-list';
import { ProductDiscovery } from 'homepage/components/product-discovery';
import { Upsell } from 'homepage/components/upsell';
import { useDispatch, useSelect } from '@wordpress/data';
import { storeName } from 'homepage/store/store';
import { SubscribersStats } from 'homepage/components/subscribers-stats';
import { Resources } from 'homepage/components/resources';

export function HomepageSections(): JSX.Element {
  const {
    isTaskListHidden,
    isProductDiscoveryHidden,
    isUpsellHidden,
    canDisplayUpsell,
  } = useSelect(
    (select) => ({
      isTaskListHidden: select(storeName).getIsTaskListHidden(),
      isProductDiscoveryHidden: select(storeName).getIsProductDiscoveryHidden(),
      isUpsellHidden: select(storeName).getIsUpsellHidden(),
      canDisplayUpsell: select(storeName).getCanDisplayUpsell(),
    }),
    [],
  );
  const { hideTaskList } = useDispatch(storeName);
  const { hideProductDiscovery } = useDispatch(storeName);
  const { hideUpsell } = useDispatch(storeName);
  return (
    <div className="mailpoet-homepage__container">
      {!isTaskListHidden && (
        <ErrorBoundary>
          <TaskList onHide={hideTaskList} />
        </ErrorBoundary>
      )}
      {!isProductDiscoveryHidden && (
        <ErrorBoundary>
          <ProductDiscovery onHide={hideProductDiscovery} />
        </ErrorBoundary>
      )}
      {isTaskListHidden &&
        isProductDiscoveryHidden &&
        canDisplayUpsell &&
        !isUpsellHidden && (
          <ErrorBoundary>
            <Upsell closable onHide={hideUpsell} />
          </ErrorBoundary>
        )}
      <ErrorBoundary>
        <SubscribersStats />
      </ErrorBoundary>
      <ErrorBoundary>
        <Resources />
      </ErrorBoundary>
    </div>
  );
}
