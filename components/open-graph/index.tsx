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
  usePostMetaValue,
} from '@alleyinteractive/block-editor-tools';

import PreviewModal from './PreviewModal';

import './style.scss';

function OpenGraphSlotfill() {
  const currentPostType = select('core/editor').getCurrentPostType();
  const postType = select('core').getEntityRecord('root', 'postType', currentPostType);

  const [title, setTitle] = usePostMetaValue('wp_seo_open_graph_title');
  const [description, setDescription] = usePostMetaValue('wp_seo_open_graph_description');
  const [image, setImage] = usePostMetaValue('wp_seo_open_graph_image');
  const [showModal, setShowModal] = useState(false);

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
          onChange={(next) => setTitle(next)}
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
        <ImagePicker
          onReset={() => setImage(0)}
          onUpdate={({ id: next }) => setImage(next)}
          value={image}
        />
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
