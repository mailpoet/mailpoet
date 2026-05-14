/* eslint-disable react/react-in-jsx-scope */
import PropTypes from 'prop-types';
import { getNewsletterEmbeds } from './api.jsx';
import { Icon } from './icon.jsx';

const wp = window.wp;
const {
  Notice,
  PanelBody,
  Placeholder,
  RangeControl,
  Spinner,
  ToggleControl,
  Disabled,
  ComboboxControl,
  SelectControl,
} = wp.components;
const { BlockIcon, InspectorControls, useBlockProps } = wp.blockEditor;
const { useEffect, useMemo, useState } = wp.element;
const { __ } = wp.i18n;
const ServerSideRender = wp.serverSideRender;

const DEFAULT_HEIGHT = 800;
const MIN_HEIGHT = 200;
const MAX_HEIGHT = 3000;
const DEFAULT_WIDTH = 640;
const MIN_WIDTH = 320;
const MAX_WIDTH = 1200;

function getNewsletterId(value) {
  if (value === '') {
    return null;
  }

  const parsed = parseInt(value, 10);
  return Number.isNaN(parsed) ? null : parsed;
}

function getHeight(value) {
  const parsed = parseInt(value, 10);
  return Number.isNaN(parsed) ? DEFAULT_HEIGHT : parsed;
}

function getWidth(value) {
  const parsed = parseInt(value, 10);
  return Number.isNaN(parsed) ? DEFAULT_WIDTH : parsed;
}

function PreviewLoadingPlaceholder() {
  return (
    <p className="mailpoet-newsletter-block-status">
      <Spinner />
      {__('Loading newsletter preview...', 'mailpoet')}
    </p>
  );
}

function PreviewErrorPlaceholder() {
  return (
    <Notice status="error" isDismissible={false}>
      {__('Newsletter preview could not be loaded.', 'mailpoet')}
    </Notice>
  );
}

function PreviewEmptyPlaceholder() {
  return (
    <Placeholder
      icon={<BlockIcon icon={Icon} showColors />}
      label={__('MailPoet Newsletter', 'mailpoet')}
    >
      {__('This newsletter is no longer available for embedding.', 'mailpoet')}
    </Placeholder>
  );
}

function Edit({ attributes, setAttributes }) {
  const blockProps = useBlockProps();
  const [search, setSearch] = useState('');
  const [newsletters, setNewsletters] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  const selectedNewsletterId = attributes.newsletterId || null;
  const height = attributes.height || DEFAULT_HEIGHT;
  const width = attributes.width || DEFAULT_WIDTH;
  const showFallbackLink = attributes.showFallbackLink !== false;
  const fallbackLinkAlignment = attributes.fallbackLinkAlignment || 'center';
  const iframeAlignment = attributes.iframeAlignment || 'center';
  const showEmailBackground = attributes.showEmailBackground !== false;

  const alignmentOptions = [
    {
      label: __('Left', 'mailpoet'),
      value: 'left',
    },
    {
      label: __('Center', 'mailpoet'),
      value: 'center',
    },
    {
      label: __('Right', 'mailpoet'),
      value: 'right',
    },
  ];

  useEffect(() => {
    let isCurrent = true;
    setIsLoading(true);
    setError(null);

    getNewsletterEmbeds(search)
      .then((items) => {
        if (!isCurrent) return;
        setNewsletters(items);
      })
      .catch(() => {
        if (!isCurrent) return;
        setNewsletters([]);
        setError(__('Newsletter selector could not be loaded.', 'mailpoet'));
      })
      .finally(() => {
        if (!isCurrent) return;
        setIsLoading(false);
      });

    return () => {
      isCurrent = false;
    };
  }, [search]);

  const options = useMemo(() => {
    const selectorOptions = [
      {
        label: __('Select a newsletter', 'mailpoet'),
        value: '',
      },
      ...newsletters.map((newsletter) => ({
        label: newsletter.label,
        value: String(newsletter.id),
      })),
    ];

    const selectedNewsletterMissing =
      selectedNewsletterId !== null &&
      !newsletters.some((newsletter) => newsletter.id === selectedNewsletterId);

    if (selectedNewsletterMissing) {
      selectorOptions.push({
        label: __('Selected newsletter', 'mailpoet'),
        value: String(selectedNewsletterId),
      });
    }

    return selectorOptions;
  }, [newsletters, selectedNewsletterId]);

  function renderSelectorControls() {
    return (
      <>
        <ComboboxControl
          label={__('Newsletter', 'mailpoet')}
          value={
            selectedNewsletterId === null ? '' : String(selectedNewsletterId)
          }
          options={options}
          onChange={(value) => {
            setAttributes({
              newsletterId: getNewsletterId(value),
            });
          }}
          onFilterValueChange={setSearch}
          placeholder={__('Search by subject', 'mailpoet')}
          disabled={isLoading && newsletters.length === 0}
          __nextHasNoMarginBottom
        />
        {isLoading && (
          <p className="mailpoet-newsletter-block-status">
            <Spinner />
            {__('Loading newsletters...', 'mailpoet')}
          </p>
        )}
        {error && (
          <Notice status="error" isDismissible={false}>
            {error}
          </Notice>
        )}
        {!isLoading && !error && newsletters.length === 0 && (
          <p>{__('No sent newsletters are available to embed.', 'mailpoet')}</p>
        )}
      </>
    );
  }

  function renderPreview() {
    return (
      <Disabled>
        <ServerSideRender
          block="mailpoet/newsletter-render"
          attributes={{
            newsletterId: selectedNewsletterId,
            height,
            width,
            showFallbackLink,
            fallbackLinkAlignment,
            iframeAlignment,
            showEmailBackground,
            align: attributes.align,
          }}
          LoadingResponsePlaceholder={PreviewLoadingPlaceholder}
          ErrorResponsePlaceholder={PreviewErrorPlaceholder}
          EmptyResponsePlaceholder={PreviewEmptyPlaceholder}
        />
      </Disabled>
    );
  }

  function renderPlaceholder() {
    return (
      <Placeholder
        icon={<BlockIcon icon={Icon} showColors />}
        label={__('MailPoet Newsletter', 'mailpoet')}
      >
        <p>{__('Select a sent MailPoet newsletter to embed.', 'mailpoet')}</p>
        {renderSelectorControls()}
      </Placeholder>
    );
  }

  return (
    <div {...blockProps}>
      <InspectorControls>
        <PanelBody title={__('MailPoet Newsletter', 'mailpoet')} initialOpen>
          {renderSelectorControls()}
        </PanelBody>
        <PanelBody title={__('Size and layout', 'mailpoet')} initialOpen>
          <RangeControl
            label={__('Height', 'mailpoet')}
            value={height}
            onChange={(value) => {
              setAttributes({
                height: getHeight(value),
              });
            }}
            min={MIN_HEIGHT}
            max={MAX_HEIGHT}
            step={50}
          />
          <RangeControl
            label={__('Width', 'mailpoet')}
            value={width}
            onChange={(value) => {
              setAttributes({
                width: getWidth(value),
              });
            }}
            min={MIN_WIDTH}
            max={MAX_WIDTH}
            step={20}
          />
          <SelectControl
            label={__('Newsletter alignment', 'mailpoet')}
            value={iframeAlignment}
            options={alignmentOptions}
            onChange={(value) => {
              setAttributes({
                iframeAlignment: value,
              });
            }}
          />
          <ToggleControl
            label={__('Show email background', 'mailpoet')}
            checked={showEmailBackground}
            onChange={(value) => {
              setAttributes({
                showEmailBackground: value,
              });
            }}
          />
        </PanelBody>
        <PanelBody title={__('Fallback link', 'mailpoet')} initialOpen>
          <ToggleControl
            label={__('Show fallback link', 'mailpoet')}
            checked={showFallbackLink}
            onChange={(value) => {
              setAttributes({
                showFallbackLink: value,
              });
            }}
          />
          {showFallbackLink && (
            <SelectControl
              label={__('Fallback link alignment', 'mailpoet')}
              value={fallbackLinkAlignment}
              options={alignmentOptions}
              onChange={(value) => {
                setAttributes({
                  fallbackLinkAlignment: value,
                });
              }}
            />
          )}
        </PanelBody>
      </InspectorControls>
      <div className="mailpoet-block-div">
        {selectedNewsletterId === null ? renderPlaceholder() : renderPreview()}
      </div>
    </div>
  );
}

Edit.propTypes = {
  attributes: PropTypes.shape({
    newsletterId: PropTypes.number,
    height: PropTypes.number,
    width: PropTypes.number,
    showFallbackLink: PropTypes.bool,
    fallbackLinkAlignment: PropTypes.string,
    iframeAlignment: PropTypes.string,
    showEmailBackground: PropTypes.bool,
    align: PropTypes.string,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export { Edit };
