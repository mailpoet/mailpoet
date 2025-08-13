import { store as coreDataStore } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import { use } from '@wordpress/data';
import { storeName as emailEditorStore } from '@woocommerce/email-editor';

// Define types for better type safety
type Registry = {
  select: (store: string) => Record<string, (...args: unknown[]) => unknown>;
  dispatch: (store: string) => Record<string, (...args: unknown[]) => unknown>;
};

type ActionFunction = (...args: unknown[]) => Promise<unknown>;
type ActionsRecord = Record<string, ActionFunction>;
type OriginalActionsStore = Record<string, Record<string, ActionFunction>>;

// Keep track of original actions per store
const originalActions: OriginalActionsStore = {};
// Keep information about initialization
let isInitialized = false;

// Which store and actions to wrap
const INTERCEPTED_ACTIONS: Record<string, string[]> = {
  core: ['saveEntityRecord'],
};

/**
 * Handles logic of processing and dispatching the stripped post status.
 *
 * @param args           The arguments passed to the original action.
 * @param registry       The data registry for use during processing.
 * @param originalAction The original action to call if the conditions are not met.
 * @return The result of the original action or a custom process response.
 */
async function processAndDispatchStrippedStatus(
  args: unknown[],
  registry: Registry,
  originalAction: ActionFunction,
): Promise<unknown> {
  try {
    const [kind, name, recordOrId, options] = args;

    // Validate kind and name
    if (typeof kind !== 'string' || typeof name !== 'string') {
      return await originalAction(...args);
    }

    const postType = registry
      .select(emailEditorStore)
      .getEmailPostType() as string;

    // Proceed only for correct kind/name and when stripping is enabled
    if (kind !== 'postType' || name !== postType) {
      return await originalAction(...args);
    }

    // Ensure recordOrId is object with numeric id
    if (
      typeof recordOrId !== 'object' ||
      recordOrId === null ||
      typeof (recordOrId as Record<string, unknown>)?.id !== 'number'
    ) {
      return await originalAction(...args);
    }

    const typedRecordOrId = recordOrId as Record<string, unknown>;

    // Get saved entity from store
    const post = registry
      .select(coreDataStore.name)
      .getEntityRecord(
        'postType',
        postType,
        typedRecordOrId.id as number,
      ) as Record<string, unknown> | null;

    // If post is missing or status is not defined, fallback to original action
    if (!post || typeof post?.status !== 'string') {
      return await originalAction(...args);
    }

    // Update the status in editor store to match saved post
    registry.dispatch(editorStore.name).editPost({ status: post.status });

    // Remove status from payload sent to API
    const { status, ...sanitizedRecord } = typedRecordOrId as {
      status: string;
      [key: string]: unknown;
    };
    return await originalAction(kind, name, sanitizedRecord, options);
  } catch (error) {
    // Log the error but don't break the save operation
    // eslint-disable-next-line no-console
    console.error('Error in strip-post-status middleware:', error);
    return await originalAction(...args);
  }
}

export const initStripPostStatusOnSaveMiddleware = (): void => {
  // Already registered?
  if (isInitialized) {
    return;
  }
  isInitialized = true;

  use((registry: Registry) => ({
    dispatch: (
      namespace: string | { name: string },
    ): Record<string, unknown> => {
      const storeName =
        typeof namespace === 'object' ? namespace.name : namespace;

      // Only wrap the core store
      if (!INTERCEPTED_ACTIONS[storeName]) {
        return registry.dispatch(storeName) as Record<string, unknown>;
      }

      const actions = registry.dispatch(storeName) as ActionsRecord;

      // Initialize namespace level objects if not yet done
      if (!originalActions[storeName]) {
        originalActions[storeName] = {};
      }

      // Check if we need to intercept any actions for this store
      const actionsToIntercept = INTERCEPTED_ACTIONS[storeName].filter(
        (actionName) => !originalActions[storeName][actionName],
      );

      // Only proceed if there are actions to intercept
      if (actionsToIntercept.length > 0) {
        // Use forEach instead of for...of loop to avoid ESLint warning
        actionsToIntercept.forEach((actionName) => {
          originalActions[storeName][actionName] = actions[actionName];

          // Create a local rewritten action for saveEntityRecord
          actions[actionName] = async (...args: unknown[]): Promise<unknown> =>
            processAndDispatchStrippedStatus(
              args,
              registry,
              originalActions[storeName][actionName],
            );
        });
      }

      return actions as Record<string, unknown>;
    },
  }));
};
