export interface ProductVideo {
  id: number;
  product_id: number;
  url: string;
  type?: string; // e.g., "youtube", "vimeo", "mp4"
  created_at?: string;
  updated_at?: string;
}
