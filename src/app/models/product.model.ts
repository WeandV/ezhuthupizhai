import { ProductImage } from './product-image.model';
import { ProductVideo } from './product-video.model';

export interface Product {
  id: number;
  name: string;
  tamil_name: string;
  short_description?: any[];
  accordion?: { title: string; description: string }[];
  description: string;
  sku: string;
  mrp_price: string;
  special_price: string;
  offers: string;
  thumbnail_image?: string;
  categories: string[];
  tag: string;
  weight_kg?: number;
  length_cm?: number;
  breadth_cm?: number;
  height_cm?: number;
  created_at?: string;
  updated_at?: string;

  images: ProductImage[];

  videos?: ProductVideo[];
  options?: { [key: string]: any };

  is_international: number;

    appliedCoupon?: any;
  hasCoupon?: boolean;

}