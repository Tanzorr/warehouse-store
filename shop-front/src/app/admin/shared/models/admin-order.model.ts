export type OrderStatus = 'pending' | 'processing' | 'shipped' | 'delivered' | 'canceled';
export type PaymentStatus = 'pending' | 'paid' | 'failed' | 'refunded' | 'cancelled';

export const ORDER_STATUSES: OrderStatus[] = ['pending', 'processing', 'shipped', 'delivered', 'canceled'];
export const PAYMENT_STATUSES: PaymentStatus[] = ['pending', 'paid', 'failed', 'refunded', 'cancelled'];

export interface AdminOrderItem {
  id: number;
  order_id: number;
  product_id: number;
  quantity: number;
  price: number;
}

export interface AdminOrder {
  id: number;
  user_id: number;
  status: OrderStatus;
  total_price: number;
  payment_status: PaymentStatus;
  payment_method: string | null;
  reservation_id: string | null;
  shipping_address: string;
  billing_address: string | null;
  notes: string | null;
  orderItems?: AdminOrderItem[];
  created_at: string;
  updated_at: string;
}

export interface UpdateOrderPayload {
  status?: OrderStatus;
  shipping_address?: string;
  billing_address?: string | null;
  notes?: string | null;
}
