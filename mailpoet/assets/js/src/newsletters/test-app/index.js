import { createRoot } from 'react-dom/client';
import { createReduxStore, register, useSelect, useDispatch } from '@wordpress/data';

// Define the store name
const STORE_NAME = 'wp-react-data-example/counter';

// Initial state
const DEFAULT_STATE = {
  count: 0,
};

// Actions
const actions = {
  increment() {
    return { type: 'INCREMENT' };
  },
  decrement() {
    return { type: 'DECREMENT' };
  },
};

// Reducer
function reducer(state = DEFAULT_STATE, action) {
  switch (action.type) {
    case 'INCREMENT':
      return {
        ...state,
        count: state.count + 1,
      };
    case 'DECREMENT':
      return {
        ...state,
        count: state.count - 1,
      };
    default:
      return state;
  }
}

// Selectors
const selectors = {
  getCount(state) {
    return state.count;
  },
};

// Create and register the store
const store = createReduxStore(STORE_NAME, {
  reducer,
  actions,
  selectors,
});

register(store);

// Counter component
function Counter() {
  const count = useSelect((select) => select(STORE_NAME).getCount(), []);
  const { increment, decrement } = useDispatch(STORE_NAME);

  return (
    <div>
      <h2>Counter: {count}</h2>
      <button onClick={increment} style={{ marginRight: '5px' }}>
        Increment
      </button>
      <button onClick={decrement}>Decrement</button>
    </div>
  );
}

export function ExampleApp() {
  return (
    <div>
      <Counter />
    </div>
  );
}

//const container = document.getElementById('react-example-wrapper');
//const root = createRoot(container);

//root.render(<App />);