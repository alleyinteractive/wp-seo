interface WPRESTMedia {
  id?: number;
  media_details?: {
    sizes: {
      [size: string]: {
        source_url: string;
        width: number;
        height: number;
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
