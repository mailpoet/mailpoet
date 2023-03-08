import { ErrorBoundary } from 'common';
import { TaskList } from 'homepage/components/task-list';
import { ProductDiscovery } from 'homepage/components/product-discovery';
import { Upsell } from 'homepage/components/upsell';
import { useDispatch, useSelect } from '@wordpress/data';
import { store } from 'homepage/store/store';
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
      isTaskListHidden: select(store).getIsTaskListHidden(),
      isProductDiscoveryHidden: select(store).getIsProductDiscoveryHidden(),
      isUpsellHidden: select(store).getIsUpsellHidden(),
      canDisplayUpsell: select(store).getCanDisplayUpsell(),
    }),
    [],
  );
  const { hideTaskList } = useDispatch(store);
  const { hideProductDiscovery } = useDispatch(store);
  const { hideUpsell } = useDispatch(store);
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
