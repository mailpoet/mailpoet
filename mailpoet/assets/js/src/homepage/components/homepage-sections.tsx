import { ErrorBoundary } from 'common';
import { TaskList } from 'homepage/components/task-list';
import { ProductDiscovery } from 'homepage/components/product-discovery';
import { useDispatch, useSelect } from '@wordpress/data';
import { storeName } from 'homepage/store/store';

export function HomepageSections(): JSX.Element {
  const { isTaskListHidden, isProductDiscoveryHidden } = useSelect(
    (select) => ({
      isTaskListHidden: select(storeName).getIsTaskListHidden(),
      isProductDiscoveryHidden: select(storeName).getIsProductDiscoveryHidden(),
    }),
    [],
  );
  const { hideTaskList } = useDispatch(storeName);
  const { hideProductDiscovery } = useDispatch(storeName);
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
    </div>
  );
}
