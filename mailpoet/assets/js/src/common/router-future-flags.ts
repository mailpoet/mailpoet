// false keeps route updates synchronous, which listings that sync local view state with the URL
// rely on (e.g. automation listing). true is v7's default and needs a review of those listings first.
export const ROUTER_FUTURE_FLAGS = {
  v7_startTransition: false,
  v7_relativeSplatPath: true,
} as const;
