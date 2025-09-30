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

  let text;

  /**
   * The following logic is based on Facebook's Open Graph image size requirements.
   * Other social platforms follow suite close enough that we use it as the baseline.
   *
   * See: https://developers.facebook.com/docs/sharing/webmasters/images/
   */

  // Image size 200x200px meets the minimum requirements.
  if (imageFullSize.width >= 200 && imageFullSize.height >= 200) {
    text = __('Selected image size meets minimum requirements. 1500x1500px is preferred.', 'wp-seo');
  }

  // Image size 1500x1500px meets the preferred requirements.
  if (imageFullSize.width >= 1500 && imageFullSize.height >= 1500) {
    text = __('Selected image size meets preferred requirements.', 'wp-seo');
  }

  // Image size smaller than 200x200px risk not being used.
  if (!text) {
    text = __('Selected image size does not meet minimum requirements. Image size must be at least 200x200px. 1500x1500px is preferred.', 'wp-seo');
  }

  return (
    <p style={{ fontSize: '0.75rem', color: '#757575' }}>{text}</p>
  );
}

function OpenGraphSlotfill() {
  const currentPostType = select('core/editor').getCurrentPostType();
  const postType = select('core').getEntityRecord('root', 'postType', currentPostType);

  const [title, setTitle] = usePostMetaValue('wp_seo_open_graph_title');
  const [description, setDescription] = usePostMetaValue('wp_seo_open_graph_description');
  const [image, setImage] = usePostMetaValue('wp_seo_open_graph_image');
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
