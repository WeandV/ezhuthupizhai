export interface AppliedCoupon {
  id?: number; 
  coupon_code: string;
  discount_value: number;
  discount_type: 'fixed' | 'percentage' | 'delivery_free';
  min_order_value?: number;
  expiry_date?: Date | string; // Allow string during initial fetch, then convert to Date
  visibility: 'public' | 'hidden' | 'specific_customer';
  display_text?: string | null; // Can be NULL in DB
  allowed_customer_ids?: string | number[] | null; // CRUCIAL: Allow string OR number array
  created_at?: Date | string;
  updated_at?: Date | string;
  logo_url?: string; // ADD THIS LINE
  company_name?: string; // ADD THIS LINEð
  allowed_product_ids?: number[] | string | null; // <--- Add this

}