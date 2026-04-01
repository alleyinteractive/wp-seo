import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { select } from '@wordpress/data';
import {
  PanelBody,
  Button,
  TextareaControl,
  TextControl,
  __experimentalVStack as VStack,
} from '@wordpress/components';
import {
  ImagePicker,
  useMedia,
  usePostMetaValue,
} from '@alleyinteractive/block-editor-tools';

import PreviewModal from './PreviewModal';

import './style.scss';

/**
 * Render image help text based on image data.
 *
 * @param image image details object.
 */
function ImageHelpText({ image }) {
  const imageFullSize = image?.media_details?.sizes?.full ?? null;

  if (!imageFullSize) {
    return (
      <p style={{ fontSize: '0.75rem', color: '#757575' }}>
        {__('No image selected. If available, featured image will be used.', 'wp-seo')}
      </p>
    );
  }

  const preferredAspectRatio = 1.91;
  const preferredWidth = 1200;
  const preferredHeight = 630;
  let tipText;
  let showLink = true;

  /**
   * The following logic is based on Facebook's Open Graph image size requirements.
   * Other social platforms follow suite close enough that we use it as the baseline.
   *
   * See: https://developers.facebook.com/docs/sharing/webmasters/images/
   */

  // Image size meets the minimum requirements.
  if (imageFullSize.width >= 600 && imageFullSize.height >= 315) {
    tipText = __('Selected image size meets minimum requirements but can be improved.', 'wp-seo');
  }

  // Image size meets preferred requirements.
  if (imageFullSize.width >= preferredWidth && imageFullSize.height >= preferredHeight) {
    tipText = __('Selected image size meets preferred requirements but not in aspect ratio.', 'wp-seo');

    if (Math.abs(imageFullSize.width / imageFullSize.height - preferredAspectRatio) < 0.01) {
      showLink = false;
      tipText = (
        <span style={{ color: 'var(--wp--preset--color--vivid-green-cyan)' }}>
          {__('Selected image size and aspect ratio meets all requirements.', 'wp-seo')}
        </span>
      );
    }
  }

  // Image width or height is less than 200px and does not meet minimum
  // allowed image dimemsion.
  if (!tipText) {
    tipText = __('Selected image size does not meet minimum requirements.', 'wp-seo');
  }

  // Calculate recommended cropping if aspect ratio is off
  let recommendation = '';

  if (imageFullSize.width && imageFullSize.height) {
    const currentAspect = imageFullSize.width / imageFullSize.height;
    let needsResize = false;
    let recommendedWidth = imageFullSize.width;
    let recommendedHeight = imageFullSize.height;

    // Adjust for aspect ratio if needed
    if (Math.abs(currentAspect - preferredAspectRatio) > 0.01) {
      if (currentAspect > preferredAspectRatio) {
        // Image is too wide
        recommendedWidth = Math.round(imageFullSize.height * preferredAspectRatio);
        needsResize = true;
      } else {
        // Image is too tall
        recommendedHeight = Math.round(imageFullSize.width / preferredAspectRatio);
        needsResize = true;
      }
    }

    // Ensure minimum dimensions
    if (recommendedWidth < preferredWidth) {
      recommendedWidth = preferredWidth;
      recommendedHeight = Math.round(preferredWidth / preferredAspectRatio);
      needsResize = true;
    }
    if (recommendedHeight < preferredHeight) {
      recommendedHeight = preferredHeight;
      recommendedWidth = Math.round(preferredHeight * preferredAspectRatio);
      needsResize = true;
    }

    if (needsResize) {
      recommendation = __(
        `Recommended: ${recommendedWidth} × ${recommendedHeight}`,
        'wp-seo',
      );
    }
  }

  return (
    <p style={{ fontSize: '0.75rem', color: '#757575' }}>
      {tipText}
      {recommendation ? (
        <>
          <br />
          <br />
          {__(`Current: ${imageFullSize.width} × ${imageFullSize.height}`, 'wp-seo')}
          <br />
          <strong>{recommendation}</strong>
        </>
      ) : null}
      {showLink
        ? (
          <>
            <br />
            <br />
            <a
              href="https://developers.facebook.com/docs/sharing/webmasters/images"
              target="_blank"
              rel="noopener noreferrer"
            >
              {__('See all requirements', 'wp-seo')}
            </a>
          </>
        )
        : null }
    </p>
  );
}

function OpenGraphSlotfill() {
  const currentPostType = select('core/editor').getCurrentPostType();
  const postType = select('core').getEntityRecord('root', 'postType', currentPostType);

  const [title, setTitle] = usePostMetaValue('alley_seo_open_graph_title');
  const [description, setDescription] = usePostMetaValue('alley_seo_open_graph_description');
  const [image, setImage] = usePostMetaValue('alley_seo_open_graph_image');
  const [showModal, setShowModal] = useState(false);

  const selectedImage = useMedia(image);

  if (!postType?.supports['open-graph']) {
    return null;
  }

  const openModal = () => {
    setShowModal(true);
  };

  const closeModal = () => {
    setShowModal(false);
  };

  return (
    <PanelBody
      title={__('Open Graph', 'wp-seo')}
      initialOpen
    >
      <VStack spacing="3">
        <TextControl
          label={__('Title', 'wp-seo')}
          onChange={setTitle}
          value={title}
          __next40pxDefaultSize
          __nextHasNoMarginBottom
        />
        <TextareaControl
          label={__('Description', 'wp-seo')}
          onChange={setDescription}
          value={description}
          __nextHasNoMarginBottom
        />
        <div>
          <p style={{
            fontSize: '11px',
            marginBottom: '0.5rem',
            color: '#1E1E1E',
            textTransform: 'uppercase',
            fontWeight: '500',
          }}
          >
            {__('Image', 'wp-seo')}
          </p>
          <ImageHelpText image={selectedImage} />
          <ImagePicker
            onReset={() => setImage(0)}
            onUpdate={({ id: next }) => setImage(next)}
            value={image}
          />
        </div>
        <div>
          <Button
            onClick={openModal}
            variant="secondary"
          >
            {__('Preview', 'wp-seo')}
          </Button>
        </div>
        {showModal ? (
          <PreviewModal
            onClose={closeModal}
            openGraphTitle={title}
            openGraphDescription={description}
            openGraphImageId={image}
          />
        ) : null}
      </VStack>
    </PanelBody>
  );
}

export default OpenGraphSlotfill;
