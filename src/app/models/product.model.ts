import { ProductImage } from './product-image.model';
import { Review } from './review.model';

export interface Product {
  id: number;
  name: string;
  short_description: string;
  description: string; 
  sku: string; 
  mrp_price: string;
  special_price: string;
  thumbnail_image?: string; 
  categories: string[];
  tag: string;
  created_at?: string;
  updated_at?: string;

  images: ProductImage[];
  reviews: Review[];
  options?: { [key: string]: any };

  is_international: number;
  
}
