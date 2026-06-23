import {
  ComponentType,
  forwardRef,
  MouseEventHandler,
  ReactNode,
  useCallback,
  useState,
} from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Button, Modal, Snackbar as WpSnackbar } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { chevronLeft, chevronRight, caution } from '@wordpress/icons';
import { Tag } from '@woocommerce/components';
import { addQueryArgs } from '@wordpress/url';
import { TemplatePreview } from './template-preview';
import { TemplateEmailPreviews } from './template-email-previews';
import { AutomationTemplate, automationTemplateCategories } from '../config';
import { MailPoet } from '../../../mailpoet';

// snackbar icon is not annotated in the types
const Snackbar = WpSnackbar as ComponentType<
  React.ComponentProps<typeof WpSnackbar> & {
    icon: ReactNode;
    isDismissible?: boolean;
    explicitDismiss?: boolean;
  }
>;

const getCategory = (template: AutomationTemplate): string =>
  automationTemplateCategories.find(({ slug }) => slug === template.category)
    ?.name ?? __('Uncategorized', 'mailpoet');

const useCreateFromTemplate = () => {
  const [state, setState] = useState({
    data: undefined,
    loading: false,
    error: undefined,
    resetError: () => setState((prevState) => ({ ...prevState, error: null })),
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
  onRequestClose: React.ComponentProps<typeof Modal>['onRequestClose'];
  onPreviousClick?: MouseEventHandler<HTMLButtonElement>;
  onNextClick?: MouseEventHandler<HTMLButtonElement>;
};

export const TemplateDetail = forwardRef<HTMLDivElement, Props>(
  ({ template, onRequestClose, onPreviousClick, onNextClick }, ref) => {
    const [createAutomationFromTemplate, { loading, error, resetError }] =
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
            <TemplateEmailPreviews templateSlug={template.slug} />
          </div>
          <div className="mailpoet-automation-template-detail-preview">
            <TemplatePreview template={template} />
          </div>
          <div className="mailpoet-automation-template-detail-footer">
            <div className="mailpoet-automation-template-detail-footer-navigation">
              <Button
                icon={chevronLeft}
                aria-label={__('Previous template', 'mailpoet')}
                onClick={onPreviousClick}
                disabled={!onPreviousClick || loading}
              />
              <Button
                icon={chevronRight}
                aria-label={__('Next template', 'mailpoet')}
                onClick={onNextClick}
                disabled={!onNextClick || loading}
              />
            </div>
            <div className="mailpoet-automation-template-detail-footer-actions">
              {error && (
                <Snackbar
                  className="mailpoet-automation-template-detail-error"
                  icon={caution}
                  onRemove={resetError}
                  isDismissible
                  explicitDismiss
                >
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
