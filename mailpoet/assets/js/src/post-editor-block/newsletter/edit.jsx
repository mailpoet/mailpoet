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
  SelectControl,
  Spinner,
  TextControl,
  ToggleControl,
  Disabled,
} = wp.components;
const { BlockIcon, InspectorControls, useBlockProps } = wp.blockEditor;
const { useEffect, useMemo, useState } = wp.element;
const { __ } = wp.i18n;
const ServerSideRender = wp.serverSideRender;

const DEFAULT_HEIGHT = 800;
const MIN_HEIGHT = 200;
const MAX_HEIGHT = 3000;

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
  const showFallbackLink = attributes.showFallbackLink !== false;

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
        <TextControl
          label={__('Search newsletters', 'mailpoet')}
          value={search}
          onChange={setSearch}
          placeholder={__('Search by subject', 'mailpoet')}
          __nextHasNoMarginBottom
        />
        <SelectControl
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
            showFallbackLink,
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
            __nextHasNoMarginBottom
          />
          <ToggleControl
            label={__('Show fallback link', 'mailpoet')}
            checked={showFallbackLink}
            onChange={(value) => {
              setAttributes({
                showFallbackLink: value,
              });
            }}
          />
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
    showFallbackLink: PropTypes.bool,
    align: PropTypes.string,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export { Edit };
