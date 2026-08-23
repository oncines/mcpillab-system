import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import {
  User,
  Employee,
  Supplier,
  PurchaseOrder,
  PurchaseInvoice,
  InventoryItem,
  InventoryTransaction,
  AttendanceRecord,
  CameraAttendanceLog,
  Delivery,
  SystemNotification,
  POStatus,
  InvoiceStatus,
  DeliveryStatus,
  AttendanceStatus,
} from '../types';
import {
  initialUsers,
  initialEmployees,
  initialSuppliers,
  initialInventory,
  initialPurchaseOrders,
  initialInvoices,
  initialDeliveries,
  initialCameraLogs,
  initialAttendance,
  initialTransactions,
  initialNotifications,
} from '../data/initialData';

export type NavTab =
  | 'dashboard'
  | 'purchase_orders'
  | 'invoices'
  | 'inventory'
  | 'employees'
  | 'attendance'
  | 'camera_attendance'
  | 'delivery_tracking'
  | 'delivery_history'
  | 'reports';

interface AppContextType {
  currentUser: User | null;
  setCurrentUser: (user: User) => void;
  isAuthenticated: boolean;
  users: User[];
  login: (identifier: string, password?: string) => { success: boolean; message: string; user?: User };
  loginAsRole: (role: 'admin' | 'employee' | 'store') => void;
  register: (userData: {
    full_name: string;
    email: string;
    username: string;
    password?: string;
    role: 'admin' | 'employee' | 'store';
    department?: string;
    store_name?: string;
    phone?: string;
    employee_id?: string;
    avatar?: string;
  }) => { success: boolean; message: string; user?: User };
  logout: () => void;
  activeTab: NavTab;
  setActiveTab: (tab: NavTab) => void;
  searchQuery: string;
  setSearchQuery: (query: string) => void;

  // Data
  employees: Employee[];
  suppliers: Supplier[];
  inventory: InventoryItem[];
  purchaseOrders: PurchaseOrder[];
  invoices: PurchaseInvoice[];
  deliveries: Delivery[];
  attendanceRecords: AttendanceRecord[];
  cameraLogs: CameraAttendanceLog[];
  transactions: InventoryTransaction[];
  notifications: SystemNotification[];

  // Actions
  addPurchaseOrder: (po: Omit<PurchaseOrder, 'id' | 'created_at' | 'messages'>) => PurchaseOrder;
  updatePOStatus: (id: number, status: POStatus, adminNotes?: string) => void;
  addPOMessage: (poId: number, message: string) => void;

  createInvoice: (invoice: Omit<PurchaseInvoice, 'id' | 'created_at'>) => PurchaseInvoice;
  updateInvoiceStatus: (id: number, status: InvoiceStatus) => void;

  addInventoryItem: (item: Omit<InventoryItem, 'id' | 'total_stock' | 'total_amount' | 'suggested_order' | 'last_updated'>) => void;
  updateInventoryItem: (id: number, updates: Partial<InventoryItem>) => void;
  adjustStock: (itemId: number, changes: { bodega?: number; shelves?: number; delivery?: number }, notes: string, refNo?: string) => void;

  addEmployee: (employee: Omit<Employee, 'id'>) => void;
  updateEmployee: (id: number, employee: Partial<Employee>) => void;
  deleteEmployee: (id: number) => void;

  markAttendance: (record: Omit<AttendanceRecord, 'id'>) => void;
  recordCameraAttendance: (log: Omit<CameraAttendanceLog, 'id' | 'created_at'>) => void;
  updateAttendanceStatus: (id: number, status: AttendanceStatus) => void;

  createDelivery: (delivery: Omit<Delivery, 'id' | 'created_at' | 'timeline'>) => Delivery;
  updateDeliveryStatus: (id: number, status: DeliveryStatus, locationNote?: string) => void;

  addSupplier: (supplier: Omit<Supplier, 'id'>) => void;
  updateSupplier: (id: number, supplier: Partial<Supplier>) => void;

  markNotificationRead: (id: string) => void;
  markAllNotificationsRead: () => void;
  addNotification: (notif: Omit<SystemNotification, 'id' | 'timestamp' | 'read'>) => void;
  resetAllData: () => void;
}

const AppContext = createContext<AppContextType | undefined>(undefined);

const LOCAL_STORAGE_KEY_PREFIX = 'mcpillab_';

function loadStorage<T>(key: string, fallback: T): T {
  try {
    const saved = localStorage.getItem(LOCAL_STORAGE_KEY_PREFIX + key);
    return saved ? JSON.parse(saved) : fallback;
  } catch (err) {
    console.error(`Error loading ${key} from storage:`, err);
    return fallback;
  }
}

function saveStorage<T>(key: string, data: T) {
  try {
    localStorage.setItem(LOCAL_STORAGE_KEY_PREFIX + key, JSON.stringify(data));
  } catch (err) {
    console.error(`Error saving ${key} to storage:`, err);
  }
}

export const AppProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [users, setUsers] = useState<User[]>(() => loadStorage('users', initialUsers));
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(() => loadStorage('is_authenticated', true));
  const [currentUser, setCurrentUser] = useState<User | null>(() => loadStorage('current_user', initialUsers[0]));
  const [activeTab, setActiveTab] = useState<NavTab>('dashboard');
  const [searchQuery, setSearchQuery] = useState('');

  const [employees, setEmployees] = useState<Employee[]>(() => loadStorage('employees', initialEmployees));
  const [suppliers, setSuppliers] = useState<Supplier[]>(() => loadStorage('suppliers', initialSuppliers));
  const [inventory, setInventory] = useState<InventoryItem[]>(() => loadStorage('inventory', initialInventory));
  const [purchaseOrders, setPurchaseOrders] = useState<PurchaseOrder[]>(() => loadStorage('pos', initialPurchaseOrders));
  const [invoices, setInvoices] = useState<PurchaseInvoice[]>(() => loadStorage('invoices', initialInvoices));
  const [deliveries, setDeliveries] = useState<Delivery[]>(() => loadStorage('deliveries', initialDeliveries));
  const [attendanceRecords, setAttendanceRecords] = useState<AttendanceRecord[]>(() => loadStorage('attendance', initialAttendance));
  const [cameraLogs, setCameraLogs] = useState<CameraAttendanceLog[]>(() => loadStorage('camera_logs', initialCameraLogs));
  const [transactions, setTransactions] = useState<InventoryTransaction[]>(() => loadStorage('transactions', initialTransactions));
  const [notifications, setNotifications] = useState<SystemNotification[]>(() => loadStorage('notifications', initialNotifications));

  // Sync to storage
  useEffect(() => saveStorage('users', users), [users]);
  useEffect(() => saveStorage('is_authenticated', isAuthenticated), [isAuthenticated]);
  useEffect(() => saveStorage('current_user', currentUser), [currentUser]);
  useEffect(() => saveStorage('employees', employees), [employees]);
  useEffect(() => saveStorage('suppliers', suppliers), [suppliers]);
  useEffect(() => saveStorage('inventory', inventory), [inventory]);
  useEffect(() => saveStorage('pos', purchaseOrders), [purchaseOrders]);
  useEffect(() => saveStorage('invoices', invoices), [invoices]);
  useEffect(() => saveStorage('deliveries', deliveries), [deliveries]);
  useEffect(() => saveStorage('attendance', attendanceRecords), [attendanceRecords]);
  useEffect(() => saveStorage('camera_logs', cameraLogs), [cameraLogs]);
  useEffect(() => saveStorage('transactions', transactions), [transactions]);
  useEffect(() => saveStorage('notifications', notifications), [notifications]);

  // Actions
  const addNotification = (notif: Omit<SystemNotification, 'id' | 'timestamp' | 'read'>) => {
    const newNotif: SystemNotification = {
      ...notif,
      id: `notif-${Date.now()}-${Math.random().toString(36).substring(2, 5)}`,
      timestamp: new Date().toISOString().replace('T', ' ').substring(0, 16),
      read: false,
    };
    setNotifications((prev) => [newNotif, ...prev]);
  };

  const login = (identifier: string, password?: string) => {
    const trimmed = identifier.trim().toLowerCase();
    const foundUser = users.find(
      (u) =>
        u.username?.toLowerCase() === trimmed ||
        u.email.toLowerCase() === trimmed
    );

    if (!foundUser) {
      return { success: false, message: 'No registered user found with this email or username.' };
    }

    if (password && foundUser.password && foundUser.password !== password) {
      return { success: false, message: 'Invalid password. Please check your credentials.' };
    }

    setCurrentUser(foundUser);
    setIsAuthenticated(true);
    addNotification({
      title: `Welcome, ${foundUser.full_name}!`,
      message: `Signed in with ${foundUser.role.toUpperCase()} laboratory clearance.`,
      type: 'system',
    });
    return { success: true, message: 'Signed in successfully!', user: foundUser };
  };

  const loginAsRole = (role: 'admin' | 'employee' | 'store') => {
    const matching = users.find((u) => u.role === role) || initialUsers.find((u) => u.role === role);
    if (matching) {
      setCurrentUser(matching);
      setIsAuthenticated(true);
      addNotification({
        title: `Switched Role to ${role.toUpperCase()}`,
        message: `Active session logged in as ${matching.full_name}.`,
        type: 'system',
      });
    }
  };

  const register = (userData: {
    full_name: string;
    email: string;
    username: string;
    password?: string;
    role: 'admin' | 'employee' | 'store';
    department?: string;
    store_name?: string;
    phone?: string;
    employee_id?: string;
    avatar?: string;
  }) => {
    const trimmedEmail = userData.email.trim().toLowerCase();
    const trimmedUsername = userData.username.trim().toLowerCase();

    if (users.some((u) => u.email.toLowerCase() === trimmedEmail)) {
      return { success: false, message: 'An account with this email is already registered.' };
    }
    if (users.some((u) => u.username?.toLowerCase() === trimmedUsername)) {
      return { success: false, message: 'This username is already taken. Please choose another.' };
    }

    const nextId = users.length > 0 ? Math.max(...users.map((u) => u.id)) + 1 : 1;
    const roleAvatars: Record<string, string> = {
      admin: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
      employee: 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&auto=format&fit=crop&q=80',
      store: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80',
    };

    const newUser: User = {
      id: nextId,
      full_name: userData.full_name.trim(),
      email: trimmedEmail,
      username: trimmedUsername,
      password: userData.password || 'pass123',
      role: userData.role,
      department:
        userData.department ||
        (userData.role === 'admin'
          ? 'Executive Administration'
          : userData.role === 'store'
          ? 'Warehouse Logistics'
          : 'Analytical Chemistry'),
      store_name: userData.store_name || (userData.role === 'store' ? 'Central Warehouse Hub B' : undefined),
      phone: userData.phone || '+1-555-0100',
      employee_id: userData.employee_id || `${userData.role.substring(0, 3).toUpperCase()}-00${nextId}`,
      avatar: userData.avatar || roleAvatars[userData.role] || roleAvatars.employee,
      created_at: new Date().toISOString(),
    };

    setUsers((prev) => [...prev, newUser]);
    setCurrentUser(newUser);
    setIsAuthenticated(true);

    if (userData.role === 'employee') {
      const nameParts = userData.full_name.trim().split(' ');
      const firstName = nameParts[0] || 'Staff';
      const lastName = nameParts.slice(1).join(' ') || 'Member';
      addEmployee({
        employee_id: newUser.employee_id || `EMP-00${nextId}`,
        first_name: firstName,
        last_name: lastName,
        email: trimmedEmail,
        phone: newUser.phone || '+1-555-0100',
        department: newUser.department || 'Analytical Chemistry',
        position: 'Laboratory Technician',
        hire_date: new Date().toISOString().split('T')[0],
        salary: 32000,
        status: 'active',
        photo: newUser.avatar,
      });
    }

    addNotification({
      title: `Registration Successful (${newUser.role.toUpperCase()})`,
      message: `Account created for ${newUser.full_name}. Session activated.`,
      type: 'system',
    });

    return { success: true, message: 'Account registered and signed in!', user: newUser };
  };

  const logout = () => {
    setIsAuthenticated(false);
    addNotification({
      title: 'Session Ended',
      message: 'You have signed out from the MCPIL Laboratory system.',
      type: 'system',
    });
  };

  const markNotificationRead = (id: string) => {
    setNotifications((prev) => prev.map((n) => (n.id === id ? { ...n, read: true } : n)));
  };

  const markAllNotificationsRead = () => {
    setNotifications((prev) => prev.map((n) => ({ ...n, read: true })));
  };

  const addPurchaseOrder = (poData: Omit<PurchaseOrder, 'id' | 'created_at' | 'messages'>): PurchaseOrder => {
    const nextId = purchaseOrders.length > 0 ? Math.max(...purchaseOrders.map((p) => p.id)) + 1 : 1;
    const newPO: PurchaseOrder = {
      ...poData,
      id: nextId,
      created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
      messages: [
        {
          id: `msg-${Date.now()}`,
          po_id: nextId,
          user_id: currentUser.id,
          user_name: currentUser.full_name,
          message: `Created Purchase Order ${poData.po_number}`,
          message_type: currentUser.role === 'admin' ? 'admin' : 'store',
          created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
        },
      ],
    };

    setPurchaseOrders((prev) => [newPO, ...prev]);

    addNotification({
      title: 'New Purchase Order Created',
      message: `${newPO.po_number} for ₱${newPO.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })} created by ${currentUser.full_name}.`,
      type: 'po',
      link: 'purchase_orders',
    });

    return newPO;
  };

  const updatePOStatus = (id: number, status: POStatus, adminNotes?: string) => {
    setPurchaseOrders((prev) =>
      prev.map((po) => {
        if (po.id === id) {
          const updatedMessages = [...po.messages];
          if (adminNotes) {
            updatedMessages.push({
              id: `msg-${Date.now()}`,
              po_id: id,
              user_id: currentUser.id,
              user_name: currentUser.full_name,
              message: `Status changed to ${status}: ${adminNotes}`,
              message_type: currentUser.role === 'admin' ? 'admin' : 'store',
              created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
            });
          }
          return {
            ...po,
            status,
            messages: updatedMessages,
          };
        }
        return po;
      })
    );

    addNotification({
      title: `PO Status Updated: ${status}`,
      message: `Purchase order #${id} status changed to ${status}.`,
      type: 'po',
      link: 'purchase_orders',
    });
  };

  const addPOMessage = (poId: number, messageText: string) => {
    setPurchaseOrders((prev) =>
      prev.map((po) => {
        if (po.id === poId) {
          const newMsg = {
            id: `msg-${Date.now()}`,
            po_id: poId,
            user_id: currentUser.id,
            user_name: currentUser.full_name,
            message: messageText,
            message_type: (currentUser.role === 'admin' ? 'admin' : 'store') as 'admin' | 'store',
            created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
          };
          return {
            ...po,
            messages: [...po.messages, newMsg],
          };
        }
        return po;
      })
    );
  };

  const createInvoice = (invoiceData: Omit<PurchaseInvoice, 'id' | 'created_at'>): PurchaseInvoice => {
    const nextId = invoices.length > 0 ? Math.max(...invoices.map((i) => i.id)) + 1 : 1;
    const newInvoice: PurchaseInvoice = {
      ...invoiceData,
      id: nextId,
      created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
    };
    setInvoices((prev) => [newInvoice, ...prev]);

    addNotification({
      title: 'Invoice Generated',
      message: `Invoice ${newInvoice.invoice_number} generated for ${newInvoice.po_number}. Total: ₱${newInvoice.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}`,
      type: 'po',
      link: 'invoices',
    });

    return newInvoice;
  };

  const updateInvoiceStatus = (id: number, status: InvoiceStatus) => {
    setInvoices((prev) => prev.map((inv) => (inv.id === id ? { ...inv, status } : inv)));
  };

  const addInventoryItem = (itemData: Omit<InventoryItem, 'id' | 'total_stock' | 'total_amount' | 'suggested_order' | 'last_updated'>) => {
    const nextId = inventory.length > 0 ? Math.max(...inventory.map((i) => i.id)) + 1 : 1;
    const total_stock = itemData.beginning_stock + itemData.bodega_stock + itemData.shelves_stock + itemData.delivery_stock;
    const total_amount = total_stock * itemData.unit_price;
    const suggested_order = total_stock < itemData.min_stock_level ? Math.round(itemData.min_stock_level * 1.5) : 0;

    const newItem: InventoryItem = {
      ...itemData,
      id: nextId,
      total_stock,
      total_amount,
      suggested_order,
      last_updated: new Date().toISOString().split('T')[0],
    };

    setInventory((prev) => [newItem, ...prev]);

    // Record beginning transaction
    const newTx: InventoryTransaction = {
      id: `tx-${Date.now()}`,
      item_id: nextId,
      item_name: newItem.item_name,
      transaction_type: 'beginning',
      quantity: total_stock,
      bodega_quantity: newItem.bodega_stock,
      shelves_quantity: newItem.shelves_stock,
      delivery_quantity: newItem.delivery_stock,
      unit_price: newItem.unit_price,
      reference_number: `INIT-${nextId}`,
      notes: 'Initial item creation entry',
      transaction_date: new Date().toISOString().split('T')[0],
      created_by_name: currentUser.full_name,
    };
    setTransactions((prev) => [newTx, ...prev]);
  };

  const updateInventoryItem = (id: number, updates: Partial<InventoryItem>) => {
    setInventory((prev) =>
      prev.map((item) => {
        if (item.id === id) {
          const updated = { ...item, ...updates };
          updated.total_stock = updated.beginning_stock + updated.bodega_stock + updated.shelves_stock + updated.delivery_stock;
          updated.total_amount = updated.total_stock * updated.unit_price;
          updated.suggested_order = updated.total_stock < updated.min_stock_level ? Math.round(updated.min_stock_level * 1.5) : 0;
          updated.last_updated = new Date().toISOString().split('T')[0];
          return updated;
        }
        return item;
      })
    );
  };

  const adjustStock = (
    itemId: number,
    changes: { bodega?: number; shelves?: number; delivery?: number },
    notes: string,
    refNo?: string
  ) => {
    const item = inventory.find((i) => i.id === itemId);
    if (!item) return;

    const bodegaDiff = changes.bodega || 0;
    const shelvesDiff = changes.shelves || 0;
    const deliveryDiff = changes.delivery || 0;
    const netChange = bodegaDiff + shelvesDiff + deliveryDiff;

    setInventory((prev) =>
      prev.map((i) => {
        if (i.id === itemId) {
          const newBodega = Math.max(0, i.bodega_stock + bodegaDiff);
          const newShelves = Math.max(0, i.shelves_stock + shelvesDiff);
          const newDelivery = Math.max(0, i.delivery_stock + deliveryDiff);
          const total_stock = i.beginning_stock + newBodega + newShelves + newDelivery;
          const total_amount = total_stock * i.unit_price;
          const suggested_order = total_stock < i.min_stock_level ? Math.round(i.min_stock_level * 1.5) : 0;

          return {
            ...i,
            bodega_stock: newBodega,
            shelves_stock: newShelves,
            delivery_stock: newDelivery,
            total_stock,
            total_amount,
            suggested_order,
            last_updated: new Date().toISOString().split('T')[0],
          };
        }
        return i;
      })
    );

    const newTx: InventoryTransaction = {
      id: `tx-${Date.now()}`,
      item_id: itemId,
      item_name: item.item_name,
      transaction_type: 'adjustment',
      quantity: netChange,
      bodega_quantity: bodegaDiff,
      shelves_quantity: shelvesDiff,
      delivery_quantity: deliveryDiff,
      unit_price: item.unit_price,
      reference_number: refNo || `ADJ-${Date.now().toString().slice(-4)}`,
      notes,
      transaction_date: new Date().toISOString().split('T')[0],
      created_by_name: currentUser.full_name,
    };
    setTransactions((prev) => [newTx, ...prev]);
  };

  const addEmployee = (empData: Omit<Employee, 'id'>) => {
    const nextId = employees.length > 0 ? Math.max(...employees.map((e) => e.id)) + 1 : 1;
    const newEmp: Employee = { ...empData, id: nextId };
    setEmployees((prev) => [...prev, newEmp]);
  };

  const updateEmployee = (id: number, updates: Partial<Employee>) => {
    setEmployees((prev) => prev.map((e) => (e.id === id ? { ...e, ...updates } : e)));
  };

  const deleteEmployee = (id: number) => {
    setEmployees((prev) => prev.filter((e) => e.id !== id));
  };

  const markAttendance = (recordData: Omit<AttendanceRecord, 'id'>) => {
    const nextId = attendanceRecords.length > 0 ? Math.max(...attendanceRecords.map((a) => a.id)) + 1 : 1;
    const newRecord: AttendanceRecord = { ...recordData, id: nextId };
    // If record exists for same employee and date, update it
    setAttendanceRecords((prev) => {
      const idx = prev.findIndex((a) => a.employee_id === newRecord.employee_id && a.date === newRecord.date);
      if (idx >= 0) {
        const copy = [...prev];
        copy[idx] = { ...copy[idx], ...newRecord };
        return copy;
      }
      return [newRecord, ...prev];
    });
  };

  const recordCameraAttendance = (logData: Omit<CameraAttendanceLog, 'id' | 'created_at'>) => {
    const newLog: CameraAttendanceLog = {
      ...logData,
      id: `cam-${Date.now()}`,
      created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
    };
    setCameraLogs((prev) => [newLog, ...prev]);

    // Also ensure attendance record exists
    const emp = employees.find((e) => e.id === logData.employee_id);
    if (emp) {
      markAttendance({
        employee_id: emp.id,
        employee_name: `${emp.first_name} ${emp.last_name}`,
        department: emp.department,
        date: logData.capture_date,
        check_in: logData.capture_time.substring(0, 5),
        break_duration: 60,
        total_hours: 8.0,
        status: 'present',
        notes: `Biometric Camera Check-in (${logData.location_address})`,
      });
    }

    addNotification({
      title: 'Camera Attendance Logged',
      message: `${logData.employee_name} checked in via Camera Station at ${logData.capture_time}.`,
      type: 'attendance',
      link: 'camera_attendance',
    });
  };

  const updateAttendanceStatus = (id: number, status: AttendanceStatus) => {
    setAttendanceRecords((prev) => prev.map((a) => (a.id === id ? { ...a, status } : a)));
  };

  const createDelivery = (delData: Omit<Delivery, 'id' | 'created_at' | 'timeline'>): Delivery => {
    const nextId = deliveries.length > 0 ? Math.max(...deliveries.map((d) => d.id)) + 1 : 1;
    const newDel: Delivery = {
      ...delData,
      id: nextId,
      created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
      timeline: [
        {
          status: delData.status,
          timestamp: new Date().toISOString().replace('T', ' ').substring(0, 16),
          description: `Delivery dispatch created by ${currentUser.full_name}`,
          location: 'MCPIL Receiving Portal',
        },
      ],
    };
    setDeliveries((prev) => [newDel, ...prev]);

    addNotification({
      title: 'Delivery Shipment Registered',
      message: `${newDel.delivery_number} from ${newDel.supplier_name} registered. Carrier: ${newDel.carrier}`,
      type: 'delivery',
      link: 'delivery_tracking',
    });

    return newDel;
  };

  const updateDeliveryStatus = (id: number, status: DeliveryStatus, locationNote?: string) => {
    setDeliveries((prev) =>
      prev.map((del) => {
        if (del.id === id) {
          const newTimelineItem = {
            status,
            timestamp: new Date().toISOString().replace('T', ' ').substring(0, 16),
            description: locationNote || `Status updated to ${status}`,
            location: locationNote || 'Distribution Network',
          };
          return {
            ...del,
            status,
            timeline: [...del.timeline, newTimelineItem],
          };
        }
        return del;
      })
    );

    addNotification({
      title: `Delivery Update: ${status}`,
      message: `Delivery record #${id} updated to ${status}.`,
      type: 'delivery',
      link: 'delivery_tracking',
    });
  };

  const addSupplier = (supData: Omit<Supplier, 'id'>) => {
    const nextId = suppliers.length > 0 ? Math.max(...suppliers.map((s) => s.id)) + 1 : 1;
    setSuppliers((prev) => [...prev, { ...supData, id: nextId }]);
  };

  const updateSupplier = (id: number, updates: Partial<Supplier>) => {
    setSuppliers((prev) => prev.map((s) => (s.id === id ? { ...s, ...updates } : s)));
  };

  const resetAllData = () => {
    localStorage.clear();
    setEmployees(initialEmployees);
    setSuppliers(initialSuppliers);
    setInventory(initialInventory);
    setPurchaseOrders(initialPurchaseOrders);
    setInvoices(initialInvoices);
    setDeliveries(initialDeliveries);
    setAttendanceRecords(initialAttendance);
    setCameraLogs(initialCameraLogs);
    setTransactions(initialTransactions);
    setNotifications(initialNotifications);
    setCurrentUser(initialUsers[0]);
  };

  return (
    <AppContext.Provider
      value={{
        currentUser,
        setCurrentUser,
        isAuthenticated,
        users,
        login,
        loginAsRole,
        register,
        logout,
        activeTab,
        setActiveTab,
        searchQuery,
        setSearchQuery,
        employees,
        suppliers,
        inventory,
        purchaseOrders,
        invoices,
        deliveries,
        attendanceRecords,
        cameraLogs,
        transactions,
        notifications,
        addPurchaseOrder,
        updatePOStatus,
        addPOMessage,
        createInvoice,
        updateInvoiceStatus,
        addInventoryItem,
        updateInventoryItem,
        adjustStock,
        addEmployee,
        updateEmployee,
        deleteEmployee,
        markAttendance,
        recordCameraAttendance,
        updateAttendanceStatus,
        createDelivery,
        updateDeliveryStatus,
        addSupplier,
        updateSupplier,
        markNotificationRead,
        markAllNotificationsRead,
        addNotification,
        resetAllData,
      }}
    >
      {children}
    </AppContext.Provider>
  );
};

export const useApp = () => {
  const context = useContext(AppContext);
  if (!context) {
    throw new Error('useApp must be used within an AppProvider');
  }
  return context;
};
