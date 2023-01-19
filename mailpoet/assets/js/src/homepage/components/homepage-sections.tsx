import { ErrorBoundary } from 'common';
import { TaskList } from 'homepage/components/task-list';
import { ProductDiscovery } from 'homepage/components/product-discovery';
import { Upsell } from 'homepage/components/upsell';
import { useDispatch, useSelect } from '@wordpress/data';
import { storeName } from 'homepage/store/store';

export function HomepageSections(): JSX.Element {
  const { isTaskListHidden, isProductDiscoveryHidden, isUpsellHidden } =
    useSelect(
      (select) => ({
        isTaskListHidden: select(storeName).getIsTaskListHidden(),
        isProductDiscoveryHidden:
          select(storeName).getIsProductDiscoveryHidden(),
        isUpsellHidden: select(storeName).getIsUpsellHidden(),
      }),
      [],
    );
  const { hideTaskList } = useDispatch(storeName);
  const { hideProductDiscovery } = useDispatch(storeName);
  const { hideUpsell } = useDispatch(storeName);
  return (
    <div className="mailpoet-homepage__container">
      {!isTaskListHidden ? (
        <ErrorBoundary>
          <TaskList onHide={hideTaskList} />
        </ErrorBoundary>
      ) : null}
      {!isProductDiscoveryHidden ? (
        <ErrorBoundary>
          <ProductDiscovery onHide={hideProductDiscovery} />
        </ErrorBoundary>
      ) : null}
      {isTaskListHidden && isProductDiscoveryHidden && !isUpsellHidden ? (
        <ErrorBoundary>
          <Upsell onHide={hideUpsell} />
        </ErrorBoundary>
      ) : null}
    </div>
  );
}
