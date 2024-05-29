import { useState, useCallback } from 'react';
import { createInterpolateElement } from '@wordpress/element';
import { sprintf, __ } from '@wordpress/i18n';

export const useNotices = () => {
  const [state, setState] = useState({
    items: [],
    nextId: 1,
  });

  const add = useCallback(
    (item) => {
      setState(({ items, nextId }) => ({
        items: [...items, { ...item, id: item.id || nextId }],
        nextId: item.id ? nextId : nextId + 1,
      }));
    },
    [setState],
  );

  const remove = useCallback(
    (id) => {
      setState(({ items, nextId }) => ({
        items: items.filter((x) => x.id !== id),
        nextId,
      }));
    },
    [setState],
  );

  const success = useCallback(
    (content, props = {}) =>
      add({ ...props, type: 'success', children: content }),
    [add],
  );
  const info = useCallback(
    (content, props = {}) => add({ ...props, type: 'info', children: content }),
    [add],
  );
  const warning = useCallback(
    (content, props = {}) =>
      add({ ...props, type: 'warning', children: content }),
    [add],
  );
  const error = useCallback(
    (content, props = {}) =>
      add({ ...props, type: 'error', children: content }),
    [add],
  );

  /**
   * This method fundamentally performs the same function as useNotices.error
   * The only addition is the wrapper for checking reinstall_plugin error
   */
  const apiError = useCallback(
    (response, props = {}) => {
      let content = response;
      let optionsObj = props;

      if (response && response.errors && response.errors.length > 0) {
        const containsReinstallPluginMessage = JSON.stringify(
          response.errors,
        ).includes('reinstall_plugin');

        if (containsReinstallPluginMessage) {
          content = response.errors.map((err) => (
            <p key={err.message}>
              {err.error === 'reinstall_plugin'
                ? createInterpolateElement(
                    sprintf(
                      __(
                        'The plugin has encountered an unexpected error. Please reload the page. If that does not help, re-install the MailPoet Plugin. See: %s for more information',
                        'mailpoet',
                      ),
                      '<a> https://kb.mailpoet.com/article/258-re-installing-updating-the-plugin-via-ftp </a>',
                    ),
                    {
                      a: (
                        <a
                          aria-label={err.error}
                          href="https://kb.mailpoet.com/article/258-re-installing-updating-the-plugin-via-ftp"
                          target="_blank"
                          rel="noopener noreferrer"
                        >
                          &nbsp;
                        </a>
                      ),
                    },
                  )
                : err.message}
            </p>
          ));
        } else {
          content = response.errors.map((err) => (
            <p key={err.message}>{err.message}</p>
          ));
        }

        optionsObj = containsReinstallPluginMessage
          ? { ...props, static: true, scroll: true }
          : props;
      }

      return add({ ...optionsObj, type: 'error', children: content });
    },
    [add],
  );

  return {
    items: state.items,
    success,
    info,
    warning,
    error,
    remove,
    apiError,
  };
};
