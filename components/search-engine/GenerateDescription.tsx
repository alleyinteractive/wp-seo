import { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Button, Notice } from '@wordpress/components';
import { store as coreStore, type Type } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

interface GenerateDescriptionProps {
  onGenerate: (description: string) => void;
}

/**
 * A button that asks the Meta Description Generator for a description of the
 * post, on post types that support it.
 *
 * The description is written from the last saved version of the post, so the
 * button is disabled until the post has been saved once.
 */
function GenerateDescription({ onGenerate }: GenerateDescriptionProps) {
  const { isNew, postId, supported } = useSelect((select) => {
    const editor = select(editorStore);
    const postType = select(coreStore).getEntityRecord<Type>('root', 'postType', editor.getCurrentPostType());

    return {
      isNew: editor.isEditedPostNew(),
      postId: editor.getCurrentPostId(),
      supported: Boolean(postType?.supports?.['wp-seo-description-generator']),
    };
  }, []);
  const [isGenerating, setIsGenerating] = useState(false);
  const [error, setError] = useState('');

  if (!supported) {
    return null;
  }

  const generate = async () => {
    setIsGenerating(true);
    setError('');

    try {
      const { description } = await apiFetch<{ description: string }>({
        path: '/wp-seo/v1/generate-description',
        method: 'POST',
        data: { post_id: postId },
      });

      onGenerate(description);
    } catch (e) {
      setError((e as { message?: string })?.message || __('The description could not be generated.', 'wp-seo'));
    } finally {
      setIsGenerating(false);
    }
  };

  return (
    <div>
      <Button
        variant="secondary"
        onClick={generate}
        disabled={isNew || isGenerating}
        isBusy={isGenerating}
        __next40pxDefaultSize
      >
        {__('Generate description', 'wp-seo')}
      </Button>
      <p style={{ fontSize: '0.75rem', marginTop: '0.2rem', color: '#757575' }}>
        {isNew
          ? __('Save the post to generate a description.', 'wp-seo')
          : __('Uses AI to write a description from the last saved version of the post.', 'wp-seo')}
      </p>
      {error ? (
        <Notice status="error" isDismissible={false}>
          {error}
        </Notice>
      ) : null}
    </div>
  );
}

export default GenerateDescription;
