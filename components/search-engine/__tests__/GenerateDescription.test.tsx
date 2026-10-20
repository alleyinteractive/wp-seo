import {
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react';
import { speak } from '@wordpress/a11y';
import apiFetch from '@wordpress/api-fetch';

import GenerateDescription from '../GenerateDescription';

const mockUseSelect = jest.fn();

jest.mock('@wordpress/a11y', () => ({ speak: jest.fn() }));
jest.mock('@wordpress/api-fetch', () => jest.fn());
jest.mock('@wordpress/core-data', () => ({ store: 'core' }));
jest.mock('@wordpress/data', () => {
  const actual = jest.requireActual('@wordpress/data');

  /*
   * Only useSelect is replaced. The rest is read from the package when used,
   * rather than copied now, since the package imports itself while it loads.
   */
  return new Proxy(actual, {
    get: (target, prop) => (prop === 'useSelect' ? mockUseSelect : target[prop]),
  });
});
jest.mock('@wordpress/editor', () => ({ store: 'core/editor' }));

const mockApiFetch = apiFetch as unknown as jest.Mock;
const mockSpeak = speak as jest.Mock;

/**
 * Edit a post in the fake editor stores.
 *
 * @param post             The post being edited.
 * @param post.isNew       Whether the post has never been saved.
 * @param post.isSupported Whether its post type supports the generator.
 */
function editPost({ isNew = false, isSupported = true } = {}) {
  const stores: Record<string, object> = {
    'core/editor': {
      getCurrentPostId: () => 123,
      getCurrentPostType: () => 'post',
      isEditedPostNew: () => isNew,
    },
    core: {
      getEntityRecord: () => ({
        supports: isSupported ? { 'wp-seo-description-generator': true } : {},
      }),
    },
  };

  mockUseSelect.mockImplementation((mapSelect) => mapSelect((store: string) => stores[store]));
}

describe('GenerateDescription', () => {
  beforeEach(() => {
    mockApiFetch.mockReset();
    mockSpeak.mockReset();
    editPost();
  });

  it('does not render on post types without support', () => {
    editPost({ isSupported: false });

    const { container } = render(<GenerateDescription onGenerate={jest.fn()} />);

    expect(container).toBeEmptyDOMElement();
  });

  it('is disabled until the post has been saved', () => {
    editPost({ isNew: true });

    render(<GenerateDescription onGenerate={jest.fn()} />);
    const button = screen.getByRole('button', { name: 'Generate description' });
    fireEvent.click(button);

    expect(button).toHaveAttribute('aria-disabled', 'true');
    expect(mockApiFetch).not.toHaveBeenCalled();
    expect(screen.getByText('Save the post to generate a description.')).toBeInTheDocument();
  });

  it('fills the description with the one generated for the post', async () => {
    mockApiFetch.mockResolvedValue({ description: 'A neutral summary of the post.' });
    const onGenerate = jest.fn();

    render(<GenerateDescription onGenerate={onGenerate} />);
    fireEvent.click(screen.getByRole('button', { name: 'Generate description' }));

    await waitFor(() => expect(onGenerate).toHaveBeenCalledWith('A neutral summary of the post.'));
    expect(mockApiFetch).toHaveBeenCalledWith({
      path: '/wp-seo/v1/generate-description',
      method: 'POST',
      data: { post_id: 123 },
    });
    expect(mockSpeak).toHaveBeenCalledWith('Description generated.');
  });

  it('is disabled while the description is generated, but keeps focus', async () => {
    let respond: (value: { description: string }) => void = () => {};
    mockApiFetch.mockReturnValue(new Promise((resolve) => { respond = resolve; }));

    render(<GenerateDescription onGenerate={jest.fn()} />);
    const button = screen.getByRole('button', { name: 'Generate description' });
    fireEvent.click(button);
    fireEvent.click(button);

    expect(button).toHaveAttribute('aria-disabled', 'true');
    // A natively disabled button would lose focus to the page.
    expect(button).not.toBeDisabled();
    expect(mockApiFetch).toHaveBeenCalledTimes(1);

    respond({ description: 'A neutral summary of the post.' });

    await waitFor(() => expect(button).not.toHaveAttribute('aria-disabled'));
  });

  it('shows the error and leaves the description alone when generating fails', async () => {
    mockApiFetch.mockRejectedValue({
      code: 'wp_seo_description_rate_limited',
      message: 'Too many descriptions have been generated. Please try again later.',
    });
    const onGenerate = jest.fn();

    render(<GenerateDescription onGenerate={onGenerate} />);
    fireEvent.click(screen.getByRole('button', { name: 'Generate description' }));

    expect(await screen.findByText('Too many descriptions have been generated. Please try again later.')).toBeInTheDocument();
    expect(onGenerate).not.toHaveBeenCalled();
    expect(mockSpeak).not.toHaveBeenCalledWith('Description generated.');
  });
});
