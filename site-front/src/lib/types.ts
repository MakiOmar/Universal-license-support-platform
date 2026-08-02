export interface Customer {
  id: number;
  email: string;
  first_name?: string | null;
  last_name?: string | null;
  company?: string | null;
  phone?: string | null;
  status?: string;
  email_verified_at?: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface PricingTier {
  id: number;
  product_id: number;
  name: string;
  price: number;
  currency: string;
  max_activations?: number;
  billing_cycle?: string;
  billing_cycle_label?: string;
  is_recurring?: boolean;
  is_one_time?: boolean;
}

export interface Product {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  type?: string | null;
  version?: string | null;
  status?: string;
  pricing_tiers?: PricingTier[];
  created_at?: string;
  updated_at?: string;
}

export interface LicenseActivation {
  id: number;
  activation_type: string;
  activation_value: string;
  activation_hash: string;
  device_name?: string | null;
  platform?: string | null;
  app_version?: string | null;
  status: string;
  activated_at?: string | null;
  last_check_at?: string | null;
}

export interface License {
  id: number;
  license_key: string;
  product_id?: number;
  customer_id?: number;
  pricing_tier_id?: number | null;
  max_activations?: number;
  activations_used?: number;
  status: string;
  license_type?: string | null;
  purchased_at?: string | null;
  expires_at?: string | null;
  support_expires_at?: string | null;
  product?: Product;
  pricing_tier?: PricingTier;
  activations?: LicenseActivation[];
  activations_count?: number;
  created_at?: string;
  updated_at?: string;
}

export interface TicketAttachment {
  id: number;
  filename: string;
  size?: number;
  mime?: string | null;
  reply_id?: number | null;
  created_at?: string;
}

export interface TicketReply {
  id: number;
  ticket_id?: number;
  author_type: string;
  author_id?: number | null;
  message: string;
  is_internal?: boolean;
  attachments?: TicketAttachment[];
  created_at?: string;
  updated_at?: string;
}

export interface Ticket {
  id: number;
  ticket_number: string;
  customer_id: number;
  license_id?: number | null;
  product_id?: number | null;
  subject: string;
  description: string;
  priority: string;
  status: string;
  category?: string | null;
  product?: Product | null;
  license?: License | null;
  replies?: TicketReply[];
  attachments?: TicketAttachment[];
  created_at?: string;
  updated_at?: string;
  resolved_at?: string | null;
}

export interface Payment {
  id: number;
  amount: number | string;
  currency: string;
  status: string;
  gateway?: string;
  gateway_reference?: string | null;
  paid_at?: string | null;
  created_at?: string;
  pricing_tier?: PricingTier | null;
  product?: Product | null;
  license?: License | null;
}

export interface AuthLoginResponse {
  token: string;
  token_type?: string;
  customer: Customer;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta?: Record<string, unknown>;
  links?: Record<string, unknown>;
}

export interface CheckoutSessionResponse {
  url?: string;
  checkout_url?: string;
}
