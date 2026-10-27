export interface StockLevel {
  productId: number;
  productName: string;
  sku: string;
  categoryName: string;
  warehouseId: number;
  warehouseName: string;
  totalStock: number;
  reservedStock: number;
  availableStock: number;
  isLowStock: boolean;
}

export interface ReservationItemLine {
  productId: number;
  name: string;
  sku: string;
  amount: number;
}

export interface WarehouseReservation {
  id: number;
  status: string;
  comment: string;
  reservedAt: string;
  releasedAt: string | null;
  items: ReservationItemLine[];
  totalItems: number;
}

export interface ReservationsResponse {
  items: WarehouseReservation[];
  total: number;
}

export interface InventoryTransaction {
  id: number;
  productId: number;
  productName: string;
  warehouseId: number;
  warehouseName: string;
  quantity: number;
  comment: string | null;
  entityType: string;
  userId: number;
  createdAt: string;
}

export interface TransactionsResponse {
  items: InventoryTransaction[];
  total: number;
}

export interface WarehouseSummary {
  warehouseId: number;
  warehouseName: string;
  totalProducts: number;
  totalStock: number;
  lowStockCount: number;
  activeReservations: number;
  todayTransactions: number;
}

export interface AdjustStockPayload {
  productId: number;
  warehouseId: number;
  quantity: number;
  comment?: string | null;
  userId?: number | null;
}

export interface TransferStockPayload {
  productId: number;
  fromWarehouseId: number;
  toWarehouseId: number;
  quantity: number;
  comment?: string | null;
  userId?: number | null;
}

export interface CancelReservationPayload {
  comment?: string | null;
}
