export type UserRole = 'admin' | 'employee' | 'store';

export interface User {
  id: number;
  email: string;
  full_name: string;
  role: UserRole;
  username?: string;
  avatar?: string;
  password?: string;
  department?: string;
  store_name?: string;
  phone?: string;
  employee_id?: string;
  created_at?: string;
}

export interface Employee {
  id: number;
  user_id?: number;
  employee_id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone: string;
  department: string;
  position: string;
  hire_date: string;
  salary: number;
  status: 'active' | 'inactive';
  photo?: string;
  address?: string;
  emergency_contact?: string;
}

export interface Supplier {
  id: number;
  supplier_code: string;
  name: string;
  contact_person: string;
  email: string;
  phone: string;
  address: string;
  city: string;
  country: string;
  status: 'active' | 'inactive';
}

export interface PurchaseOrderItem {
  id: string;
  item_name: string;
  description: string;
  quantity: number;
  unit_price: number;
  total_price: number;
}

export interface PurchaseOrderMessage {
  id: string;
  po_id: number;
  user_id: number;
  user_name: string;
  message: string;
  message_type: 'admin' | 'store';
  created_at: string;
}

export type POStatus = 'Pending' | 'Approved' | 'Rejected' | 'Processing' | 'Completed';

export interface PurchaseOrder {
  id: number;
  po_number: string;
  supplier_id: number;
  store_name: string;
  order_date: string;
  expected_delivery_date: string;
  total_amount: number;
  status: POStatus;
  notes: string;
  created_by: number;
  created_by_name: string;
  created_at: string;
  items: PurchaseOrderItem[];
  messages: PurchaseOrderMessage[];
}

export type InvoiceStatus = 'unpaid' | 'partially_paid' | 'paid';

export interface PurchaseInvoice {
  id: number;
  invoice_number: string;
  po_id: number;
  po_number: string;
  supplier_name: string;
  invoice_date: string;
  due_date: string;
  amount: number;
  tax_amount: number;
  total_amount: number;
  status: InvoiceStatus;
  notes: string;
  created_at: string;
}

export interface InventoryItem {
  id: number;
  item_name: string;
  barcode: string;
  size: string;
  unit: string;
  unit_price: number;
  category: 'chemicals' | 'consumables' | 'equipment' | 'reagents';
  supplier_id: number;
  location: string;
  min_stock_level: number;
  beginning_stock: number;
  bodega_stock: number;
  shelves_stock: number;
  delivery_stock: number;
  total_stock: number;
  total_amount: number;
  suggested_order: number;
  last_updated: string;
}

export interface InventoryTransaction {
  id: string;
  item_id: number;
  item_name: string;
  transaction_type: 'beginning' | 'delivery' | 'adjustment' | 'sale' | 'return';
  quantity: number;
  bodega_quantity: number;
  shelves_quantity: number;
  delivery_quantity: number;
  unit_price: number;
  reference_number: string;
  notes: string;
  transaction_date: string;
  created_by_name: string;
}

export interface CameraAttendanceLog {
  id: string;
  employee_id: number;
  employee_name: string;
  capture_date: string;
  capture_time: string;
  photo_path: string;
  latitude: number;
  longitude: number;
  location_address: string;
  azimuth: string;
  temperature: number;
  device_info: string;
  notes: string;
  created_at: string;
}

export type AttendanceStatus = 'present' | 'absent' | 'late' | 'half_day';

export interface AttendanceRecord {
  id: number;
  employee_id: number;
  employee_name: string;
  department: string;
  date: string;
  check_in?: string;
  check_out?: string;
  break_duration: number; // minutes
  total_hours: number;
  status: AttendanceStatus;
  notes: string;
  location?: string;
  camera_captures?: CameraAttendanceLog[];
}

export type DeliveryStatus = 'pending' | 'in_transit' | 'delivered' | 'cancelled';

export interface DeliveryItem {
  id: string;
  item_name: string;
  quantity_ordered: number;
  quantity_delivered: number;
  quantity_pending: number;
  condition_status: 'good' | 'damaged' | 'missing';
  notes: string;
}

export interface Delivery {
  id: number;
  delivery_number: string;
  po_id: number;
  po_number: string;
  supplier_id: number;
  supplier_name: string;
  delivery_date: string;
  expected_date: string;
  estimated_delivery?: string;
  status: DeliveryStatus;
  tracking_number: string;
  carrier: string;
  driver_name?: string;
  driver_contact?: string;
  origin?: string;
  destination?: string;
  temperature_celsius?: number;
  temp_range?: string;
  current_location?: string;
  speed_kmh?: number;
  vehicle_plate?: string;
  recipient_signature?: string;
  items_summary?: string;
  notes: string;
  created_by: number;
  created_by_name: string;
  created_at: string;
  items: DeliveryItem[];
  timeline: {
    status: DeliveryStatus;
    timestamp: string;
    description: string;
    location?: string;
  }[];
}

export interface SystemNotification {
  id: string;
  title: string;
  message: string;
  type: 'po' | 'delivery' | 'attendance' | 'inventory' | 'system';
  timestamp: string;
  read: boolean;
  link?: string;
}

export interface ChatMessage {
  id: string;
  sender: 'user' | 'bot';
  text: string;
  timestamp: string;
  suggestions?: string[];
  actionType?: 'navigate' | 'filter' | 'info';
  actionPayload?: string;
}
