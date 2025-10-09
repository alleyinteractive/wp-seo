interface WPRESTMedia {
  id?: number;
  media_details?: {
    sizes: {
      [size: string]: {
        source_url: string;
      };
    };
  };
  sizes?: {
    [size: string]: {
      url: string;
    };
  };
  source_url?: string;
  url?: string;
}

export default WPRESTMedia;
