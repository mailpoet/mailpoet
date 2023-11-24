import { forwardRef, MouseEventHandler, useCallback, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Button, Modal, Snackbar } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronLeft, chevronRight } from '@wordpress/icons';
import { Tag } from '@woocommerce/components';
import { addQueryArgs } from '@wordpress/url';
import { TemplatePreview } from './template-preview';
import { AutomationTemplate, automationTemplateCategories } from '../config';
import { MailPoet } from '../../../mailpoet';

const getCategory = (template: AutomationTemplate): string =>
  automationTemplateCategories.find(({ slug }) => slug === template.category)
    ?.name ?? __('Uncategorized', 'mailpoet');

const useCreateFromTemplate = () => {
  const [state, setState] = useState({
    data: undefined,
    loading: false,
    error: undefined,
  });

  const create = useCallback(async (slug: string) => {
    setState((prevState) => ({ ...prevState, loading: true }));
    try {
      const data = await apiFetch<{ data: { id: string } }>({
        path: `/automations/create-from-template`,
        method: 'POST',
        data: { slug },
      });
      MailPoet.trackEvent('Automations > Template selected', {
        'Automation slug': slug,
      });
      window.location.href = addQueryArgs(MailPoet.urls.automationEditor, {
        id: data.data.id,
      });
    } catch (error) {
      setState((prevState) => ({ ...prevState, error }));
    } finally {
      setState((prevState) => ({ ...prevState, loading: false }));
    }
  }, []);

  return [create, state] as const;
};

type Props = {
  template: AutomationTemplate;
  onRequestClose: Modal.Props['onRequestClose'];
  onPreviousClick?: MouseEventHandler<HTMLButtonElement>;
  onNextClick?: MouseEventHandler<HTMLButtonElement>;
};

export const TemplateDetail = forwardRef<HTMLDivElement, Props>(
  ({ template, onRequestClose, onPreviousClick, onNextClick }, ref) => {
    const [createAutomationFromTemplate, { loading, error }] =
      useCreateFromTemplate();

    return (
      <Modal
        ref={ref}
        className="mailpoet-automation-template-detail"
        title=""
        onRequestClose={onRequestClose}
      >
        <div className="mailpoet-automation-template-detail-content">
          <div className="mailpoet-automation-template-detail-info">
            <Tag label={getCategory(template)} />
            <h1>{template.name}</h1>
            {template.description}
          </div>
          <div className="mailpoet-automation-template-detail-preview">
            <TemplatePreview template={template} />
          </div>
          <div className="mailpoet-automation-template-detail-footer">
            <div className="mailpoet-automation-template-detail-footer-navigation">
              <Button
                icon={chevronLeft}
                onClick={onPreviousClick}
                disabled={!onPreviousClick || loading}
              />
              <Button
                icon={chevronRight}
                onClick={onNextClick}
                disabled={!onNextClick || loading}
              />
            </div>
            <div className="mailpoet-automation-template-detail-footer-actions">
              {error && (
                <Snackbar className="mailpoet-automation-template-detail-error">
                  {__(
                    'An error occurred while creating the automation. Please, try again.',
                    'mailpoet',
                  )}
                </Snackbar>
              )}
              <Button
                variant="tertiary"
                onClick={onRequestClose as MouseEventHandler}
                disabled={loading}
              >
                {__('Cancel', 'mailpoet')}
              </Button>
              <Button
                variant="primary"
                onClick={() => void createAutomationFromTemplate(template.slug)}
                isBusy={loading}
                disabled={loading}
              >
                {__('Start building', 'mailpoet')}
              </Button>
            </div>
          </div>
        </div>
      </Modal>
    );
  },
);
