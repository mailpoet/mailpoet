import { ComponentProps, ComponentType, useEffect, useState } from 'react';
import { Spinner as WpSpinner } from '@wordpress/components';
import { Button } from '../../../components/button';
import { MailPoetAjax } from '../../../../../../ajax';

// @types/wordpress__components don't define "className", which is supported
const Spinner = WpSpinner as ComponentType<
  ComponentProps<typeof WpSpinner> & { className?: string }
>;

type Props = {
  emailId: number;
};

export function Thumbnail({ emailId }: Props): JSX.Element {
  const [thumbnailUrl, setThumbnailUrl] = useState<string>();

  useEffect(() => {
    const getData = async () => {
      const data = await MailPoetAjax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'newsletters',
        action: 'get',
        data: { id: emailId },
      });

      // TODO: we need to implement thumbnails backend first
      if (data?.data?.thumbnail_url) {
        setThumbnailUrl(data.data.thumbnail_url as string);
      }
    };

    void getData();
  }, [emailId]);

  return (
    <>
      <div className="mailpoet-automation-thumbnail-box">
        {thumbnailUrl ? (
          <div className="mailpoet-automation-thumbnail-wrapper">
            <img
              className="mailpoet-automation-thumbnail-image"
              src={thumbnailUrl}
              alt="Email thumbnail"
            />
          </div>
        ) : (
          <Spinner className="mailpoet-automation-thumbnail-spinner" />
        )}
      </div>

      <div className="mailpoet-automation-thumbnail-buttons">
        <Button
          variant="sidebar-primary"
          centered
          href={`?page=mailpoet-newsletter-editor&id=${emailId}`}
        >
          Edit content
        </Button>
        <Button variant="secondary" centered>
          Preview
        </Button>
      </div>
    </>
  );
}
