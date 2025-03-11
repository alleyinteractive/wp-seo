import { useState } from 'react';
import { Button, TextareaControl, TextControl } from '@wordpress/components';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

import {
  ImagePicker,
  usePostMetaValue,
} from '@alleyinteractive/block-editor-tools';
import PreviewModal from './PreviewModal';

import './style.scss';

function OpenGraphSlotfill() {
  const [title, setTitle] = usePostMetaValue('wp_seo_open_graph_title');
  const [description, setDescription] = usePostMetaValue('wp_seo_open_graph_description');
  const [image, setImage] = usePostMetaValue('wp_seo_open_graph_image');
  const [showModal, setShowModal] = useState(false);

  const openModal = () => {
    setShowModal(true);
  };

  const closeModal = () => {
    setShowModal(false);
  };

  return (
    <PluginDocumentSettingPanel
      icon="share"
      name="opengraph"
      title={__('Open Graph', 'wp-seo')}
    >
      <TextControl
        label={__('Title', 'wp-seo')}
        onChange={(next) => setTitle(next)}
        value={title}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
      <br />
      <TextareaControl
        label={__('Description', 'wp-seo')}
        onChange={(next) => setDescription(next)}
        value={description}
        __nextHasNoMarginBottom
      />
      <br />
      <ImagePicker
        onReset={() => setImage(0)}
        onUpdate={({ id: next }) => setImage(next)}
        value={image}
      />
      <br />
      <Button
        onClick={openModal}
        variant="secondary"
      >
        {__('Preview', 'wp-seo')}
      </Button>
      {showModal ? (
        <PreviewModal
          onClose={closeModal}
          openGraphTitle={title}
          openGraphDescription={description}
          openGraphImageId={image}
        />
      ) : null}
    </PluginDocumentSettingPanel>
  );
}

export default OpenGraphSlotfill;
