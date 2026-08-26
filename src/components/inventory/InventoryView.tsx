import React, { useState, useMemo } from 'react';
import { useApp } from '../../context/AppContext';
import { InventoryItem, InventoryTransaction } from '../../types';
import {
  Boxes,
  Plus,
  Search,
  AlertTriangle,
  History,
  FileSpreadsheet,
  Printer,
  Download,
  RotateCcw,
  SlidersHorizontal,
  Package,
  Layers,
  ArrowRight,
  ShoppingCart,
  CheckCircle2,
  Trash2,
  Edit3,
  Eye,
  Building2,
  MapPin,
  TrendingDown,
  Info,
  Calendar,
  X,
  FileText,
} from 'lucide-react';

export const InventoryView: React.FC = () => {
  const {
    inventory,
    suppliers,
    transactions,
    addInventoryItem,
    updateInventoryItem,
    deleteInventoryItem,
    adjustStock,
    addPurchaseOrder,
    currentUser,
    searchQuery,
    setActiveTab,
  } = useApp();

  // Active view tab
  const [activeSubTab, setActiveSubTab] = useState<'report' | 'add_form' | 'adjust' | 'history' | 'reorder'>('report');

  // Filters & Search
  const [categoryFilter, setCategoryFilter] = useState<string>('all');
  const [localSearch, setLocalSearch] = useState('');
  const [selectedSupplierFilter, setSelectedSupplierFilter] = useState<string>('all');

  // Modals state
  const [showAdjustModal, setShowAdjustModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showDetailsModal, setShowDetailsModal] = useState(false);
  const [selectedItem, setSelectedItem] = useState<InventoryItem | null>(null);
  const [itemToDelete, setItemToDelete] = useState<InventoryItem | null>(null);

  // Stock Adjustment Form State
  const [bodegaChange, setBodegaChange] = useState<number>(0);
  const [shelvesChange, setShelvesChange] = useState<number>(0);
  const [deliveryChange, setDeliveryChange] = useState<number>(0);
  const [adjustNotes, setAdjustNotes] = useState('');
  const [adjustRef, setAdjustRef] = useState('');

  // Add Item Form State
  const [formData, setFormData] = useState({
    item_name: '',
    item_code: '',
    category: 'chemicals' as 'chemicals' | 'consumables' | 'equipment' | 'reagents',
    description: '',
    unit: 'bottle',
    size: '500ml',
    content: '1',
    quantity: 30,
    unit_price: 150,
    min_stock_level: 20,
    supplier_id: suppliers[0]?.id || 1,
    location: 'Bodega-A1',
    bodega_stock: 20,
    shelves_stock: 10,
    delivery_stock: 0,
  });

  // Edit Item Form State
  const [editFormData, setEditFormData] = useState({
    item_name: '',
    barcode: '',
    category: 'chemicals' as 'chemicals' | 'consumables' | 'equipment' | 'reagents',
    unit: 'bottle',
    size: '500ml',
    unit_price: 0,
    min_stock_level: 0,
    supplier_id: 1,
    location: '',
  });

  // Transaction filter state
  const [txTypeFilter, setTxTypeFilter] = useState<string>('all');
  const [txSearch, setTxSearch] = useState('');

  // Success alert banner
  const [bannerMessage, setBannerMessage] = useState<string | null>(null);

  const showNotificationBanner = (msg: string) => {
    setBannerMessage(msg);
    setTimeout(() => setBannerMessage(null), 5000);
  };

  // Form submit: Add Item
  const handleAddSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.item_name.trim()) return;

    const barcode = formData.item_code.trim() || `BAR${String(inventory.length + 1).padStart(3, '0')}`;
    const totalBeginning = Number(formData.bodega_stock) + Number(formData.shelves_stock);

    addInventoryItem({
      item_name: formData.item_name.trim().toUpperCase(),
      barcode: barcode,
      size: formData.size.trim() || '500ml',
      unit: formData.unit.trim() || 'unit',
      unit_price: Number(formData.unit_price) || 0,
      category: formData.category,
      supplier_id: Number(formData.supplier_id),
      location: formData.location.trim() || 'Warehouse Hub',
      min_stock_level: Number(formData.min_stock_level) || 10,
      beginning_stock: totalBeginning,
      bodega_stock: Number(formData.bodega_stock) || 0,
      shelves_stock: Number(formData.shelves_stock) || 0,
      delivery_stock: Number(formData.delivery_stock) || 0,
    });

    showNotificationBanner(`Added "${formData.item_name.toUpperCase()}" to inventory.`);

    // Reset form
    setFormData({
      item_name: '',
      item_code: '',
      category: 'chemicals',
      description: '',
      unit: 'bottle',
      size: '500ml',
      content: '1',
      quantity: 30,
      unit_price: 150,
      min_stock_level: 20,
      supplier_id: suppliers[0]?.id || 1,
      location: 'Bodega-A1',
      bodega_stock: 20,
      shelves_stock: 10,
      delivery_stock: 0,
    });

    setActiveSubTab('report');
  };

  // Stock Adjustment Submit
  const handleAdjustSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedItem) return;

    adjustStock(
      selectedItem.id,
      {
        bodega: Number(bodegaChange),
        shelves: Number(shelvesChange),
        delivery: Number(deliveryChange),
      },
      adjustNotes || 'Manual stock level adjustment',
      adjustRef || `ADJ-${Date.now().toString().slice(-4)}`
    );

    showNotificationBanner(`Updated stock quantities for "${selectedItem.item_name}".`);
    setShowAdjustModal(false);
    setSelectedItem(null);
    setBodegaChange(0);
    setShelvesChange(0);
    setDeliveryChange(0);
    setAdjustNotes('');
    setAdjustRef('');
  };

  // Edit Item Submit
  const handleEditSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedItem) return;

    updateInventoryItem(selectedItem.id, {
      item_name: editFormData.item_name.toUpperCase(),
      barcode: editFormData.barcode,
      category: editFormData.category,
      unit: editFormData.unit,
      size: editFormData.size,
      unit_price: Number(editFormData.unit_price),
      min_stock_level: Number(editFormData.min_stock_level),
      supplier_id: Number(editFormData.supplier_id),
      location: editFormData.location,
    });

    showNotificationBanner(`Updated item details for "${editFormData.item_name}".`);
    setShowEditModal(false);
    setSelectedItem(null);
  };

  // Delete Item Action
  const confirmDeleteItem = () => {
    if (!itemToDelete) return;
    deleteInventoryItem(itemToDelete.id);
    showNotificationBanner(`Removed "${itemToDelete.item_name}" from inventory ledger.`);
    setItemToDelete(null);
  };

  // Quick 1-Click Purchase Order for Suggested Orders
  const handleCreatePOFromItem = (item: InventoryItem) => {
    const qty = item.suggested_order > 0 ? item.suggested_order : Math.max(10, item.min_stock_level);
    const supplier = suppliers.find((s) => s.id === item.supplier_id) || suppliers[0];

    const newPO = addPurchaseOrder({
      po_number: `PO-${new Date().getFullYear()}-${Math.floor(1000 + Math.random() * 9000)}`,
      supplier_id: supplier?.id || 1,
      store_name: currentUser.store_name || 'McPIL Main Pharmacy & Lab',
      order_date: new Date().toISOString().split('T')[0],
      expected_delivery_date: new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0],
      total_amount: qty * item.unit_price,
      status: 'Pending',
      notes: `Automated replenishment order generated for low stock item: ${item.item_name} (Current stock: ${item.total_stock}, Safety min: ${item.min_stock_level})`,
      created_by: currentUser.id,
      created_by_name: currentUser.full_name,
      items: [
        {
          id: `item-${Date.now()}`,
          item_name: item.item_name,
          description: `${item.size} • ${item.unit} (${item.barcode})`,
          quantity: qty,
          unit_price: item.unit_price,
          total_price: qty * item.unit_price,
        },
      ],
    });

    showNotificationBanner(`Purchase Order ${newPO.po_number} created for ${item.item_name}!`);
    setActiveTab('purchase_orders');
  };

  // CSV Export functionality
  const handleExportCSV = () => {
    const headers = [
      'NAME OF PRODUCTS',
      'BARCODE',
      'SIZE',
      'UNIT',
      'UNITPRICE',
      'CATEGORY',
      'STORAGE LOCATION',
      'BEGINNING BODEGA',
      'BEGINNING SHELVES',
      'BEGINNING DELIVERY',
      'BEGINNING TOTAL',
      'ENDING BODEGA',
      'ENDING SHELVES',
      'ENDING DELIVERY',
      'ENDING TOTAL',
      'ON HAND',
      'TOTAL AMOUNT (PHP)',
      'SUGGESTED ORDER',
    ];

    const rows = filteredInventory.map((item) => [
      `"${item.item_name.replace(/"/g, '""')}"`,
      `"${item.barcode}"`,
      `"${item.size}"`,
      `"${item.unit}"`,
      item.unit_price.toFixed(2),
      `"${item.category}"`,
      `"${item.location}"`,
      item.bodega_stock,
      item.shelves_stock,
      item.delivery_stock,
      item.total_stock,
      item.bodega_stock,
      item.shelves_stock,
      item.delivery_stock,
      item.total_stock,
      item.total_stock,
      item.total_amount.toFixed(2),
      item.suggested_order,
    ]);

    // Totals row
    rows.push([
      '"TOTALS"',
      '""',
      '""',
      '""',
      '""',
      '""',
      '""',
      totalBodega,
      totalShelves,
      totalDelivery,
      totalStockUnits,
      totalBodega,
      totalShelves,
      totalDelivery,
      totalStockUnits,
      totalStockUnits,
      totalValuation.toFixed(2),
      totalSuggestedOrder,
    ]);

    const csvContent = [headers.join(','), ...rows.map((e) => e.join(','))].join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `MCPIL_Inventory_Report_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  // Filtered Items Logic
  const effectiveSearch = (searchQuery || localSearch).toLowerCase();
  const filteredInventory = useMemo(() => {
    return inventory.filter((item) => {
      let matchesCategory = true;
      if (categoryFilter === 'low_stock') {
        matchesCategory = item.total_stock <= item.min_stock_level;
      } else if (categoryFilter === 'reorder') {
        matchesCategory = item.suggested_order > 0;
      } else if (categoryFilter !== 'all') {
        matchesCategory = item.category === categoryFilter;
      }

      let matchesSupplier = true;
      if (selectedSupplierFilter !== 'all') {
        matchesSupplier = item.supplier_id === Number(selectedSupplierFilter);
      }

      const matchesSearch =
        item.item_name.toLowerCase().includes(effectiveSearch) ||
        item.barcode.toLowerCase().includes(effectiveSearch) ||
        item.location.toLowerCase().includes(effectiveSearch) ||
        item.unit.toLowerCase().includes(effectiveSearch) ||
        item.size.toLowerCase().includes(effectiveSearch);

      return matchesCategory && matchesSupplier && matchesSearch;
    });
  }, [inventory, categoryFilter, selectedSupplierFilter, effectiveSearch]);

  // Totals calculations
  const totalStockUnits = useMemo(() => filteredInventory.reduce((sum, i) => sum + i.total_stock, 0), [filteredInventory]);
  const totalBodega = useMemo(() => filteredInventory.reduce((sum, i) => sum + i.bodega_stock, 0), [filteredInventory]);
  const totalShelves = useMemo(() => filteredInventory.reduce((sum, i) => sum + i.shelves_stock, 0), [filteredInventory]);
  const totalDelivery = useMemo(() => filteredInventory.reduce((sum, i) => sum + i.delivery_stock, 0), [filteredInventory]);
  const totalValuation = useMemo(() => filteredInventory.reduce((sum, i) => sum + i.total_amount, 0), [filteredInventory]);
  const totalSuggestedOrder = useMemo(() => filteredInventory.reduce((sum, i) => sum + i.suggested_order, 0), [filteredInventory]);
  const lowStockCount = useMemo(() => inventory.filter((i) => i.total_stock <= i.min_stock_level).length, [inventory]);
  const reorderCount = useMemo(() => inventory.filter((i) => i.suggested_order > 0).length, [inventory]);

  // Filtered transactions
  const filteredTransactions = useMemo(() => {
    return transactions.filter((tx) => {
      const matchesType = txTypeFilter === 'all' || tx.transaction_type === txTypeFilter;
      const matchesTxSearch =
        tx.item_name.toLowerCase().includes(txSearch.toLowerCase()) ||
        tx.reference_number.toLowerCase().includes(txSearch.toLowerCase()) ||
        tx.notes.toLowerCase().includes(txSearch.toLowerCase()) ||
        tx.created_by_name.toLowerCase().includes(txSearch.toLowerCase());
      return matchesType && matchesTxSearch;
    });
  }, [transactions, txTypeFilter, txSearch]);

  return (
    <div className="space-y-6">
      {/* Banner message */}
      {bannerMessage && (
        <div className="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl flex items-center justify-between shadow-xs animate-in fade-in">
          <div className="flex items-center gap-2 font-medium text-xs">
            <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
            <span>{bannerMessage}</span>
          </div>
          <button onClick={() => setBannerMessage(null)} className="text-emerald-700 hover:text-emerald-900">
            <X className="w-4 h-4" />
          </button>
        </div>
      )}

      {/* Main Header */}
      <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2.5">
            <div className="p-2 rounded-xl bg-blue-50 text-blue-700 border border-blue-100">
              <Boxes className="w-5 h-5" />
            </div>
            <div>
              <h1 className="text-xl font-extrabold text-slate-900 tracking-tight">
                Pharmaceutical Laboratory Inventory
              </h1>
              <p className="text-xs text-slate-500 mt-0.5">
                Complete multi-tier reagent ledger tracking Bodega storage, Laboratory Shelves, and Delivery pipeline.
              </p>
            </div>
          </div>
        </div>

        {/* Header Action Buttons */}
        <div className="flex flex-wrap items-center gap-2">
          <button
            onClick={() => window.print()}
            className="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors"
            title="Print Inventory Report"
          >
            <Printer className="w-4 h-4" />
            Print Report
          </button>

          <button
            onClick={handleExportCSV}
            className="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold text-xs rounded-xl border border-emerald-200 transition-colors"
            title="Export to Excel / CSV"
          >
            <Download className="w-4 h-4" />
            Export to Excel
          </button>

          <button
            onClick={() => setActiveSubTab('add_form')}
            className="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-sm shadow-blue-600/20 transition-colors"
          >
            <Plus className="w-4 h-4" />
            Add New Item
          </button>
        </div>
      </div>

      {/* Summary KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <span className="text-slate-400 font-bold uppercase tracking-wider text-[10px]">Total Items</span>
            <div className="text-2xl font-black text-slate-900 mt-1 font-mono">{inventory.length}</div>
            <div className="text-[11px] text-slate-500 mt-0.5">Active Laboratory SKUs</div>
          </div>
          <div className="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-600">
            <Package className="w-5 h-5" />
          </div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <span className="text-blue-600 font-bold uppercase tracking-wider text-[10px]">Total Quantity</span>
            <div className="text-2xl font-black text-blue-900 mt-1 font-mono">{totalStockUnits}</div>
            <div className="text-[11px] text-slate-500 mt-0.5">
              Bodega: {totalBodega} | Shelves: {totalShelves}
            </div>
          </div>
          <div className="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600">
            <Layers className="w-5 h-5" />
          </div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <span className="text-emerald-600 font-bold uppercase tracking-wider text-[10px]">Total Value (PHP)</span>
            <div className="text-2xl font-black text-emerald-800 mt-1 font-mono">
              ₱{totalValuation.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            </div>
            <div className="text-[11px] text-slate-500 mt-0.5">Valuation at unit pricing</div>
          </div>
          <div className="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
            <Building2 className="w-5 h-5" />
          </div>
        </div>

        <div
          onClick={() => {
            setCategoryFilter('reorder');
            setActiveSubTab('report');
          }}
          className="bg-white p-4 rounded-xl border border-rose-200 shadow-xs flex items-center justify-between cursor-pointer hover:bg-rose-50/40 transition-colors"
        >
          <div>
            <span className="text-rose-600 font-bold uppercase tracking-wider text-[10px]">Items to Order</span>
            <div className="text-2xl font-black text-rose-700 mt-1 font-mono">
              {reorderCount} <span className="text-xs font-normal text-slate-500">({totalSuggestedOrder} units)</span>
            </div>
            <div className="text-[11px] text-rose-600 font-semibold mt-0.5">
              {reorderCount > 0 ? 'Click to review suggested orders' : 'Stocks at healthy levels'}
            </div>
          </div>
          <div className="w-10 h-10 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600">
            <AlertTriangle className="w-5 h-5" />
          </div>
        </div>
      </div>

      {/* Feature Navigation Tabs */}
      <div className="flex border-b border-slate-200 bg-white px-4 rounded-t-xl overflow-x-auto gap-2">
        {[
          { id: 'report', label: 'Inventory Report & Ledger', icon: FileSpreadsheet },
          { id: 'add_form', label: 'Add New Inventory Item', icon: Plus },
          { id: 'reorder', label: `Suggested Orders (${reorderCount})`, icon: ShoppingCart },
          { id: 'history', label: 'Stock Movement History', icon: History },
        ].map((tab) => {
          const Icon = tab.icon;
          const isActive = activeSubTab === tab.id;
          return (
            <button
              key={tab.id}
              onClick={() => setActiveSubTab(tab.id as any)}
              className={`flex items-center gap-2 py-3 px-4 font-bold text-xs border-b-2 whitespace-nowrap transition-colors ${
                isActive
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300'
              }`}
            >
              <Icon className="w-4 h-4" />
              {tab.label}
            </button>
          );
        })}
      </div>

      {/* TAB 1: INVENTORY REPORT & DOUBLE-HEADER TABLE */}
      {activeSubTab === 'report' && (
        <div className="space-y-4">
          {/* Filter and Search Bar */}
          <div className="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div className="flex flex-wrap items-center gap-1.5">
              {[
                { id: 'all', label: `All Items (${inventory.length})` },
                { id: 'chemicals', label: 'Chemicals & Reagents' },
                { id: 'consumables', label: 'Consumables' },
                { id: 'low_stock', label: `Low Stock (${lowStockCount})` },
                { id: 'reorder', label: `Suggested Reorder (${reorderCount})` },
              ].map((cat) => (
                <button
                  key={cat.id}
                  onClick={() => setCategoryFilter(cat.id)}
                  className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-colors ${
                    categoryFilter === cat.id
                      ? 'bg-slate-900 text-white shadow-xs'
                      : 'bg-slate-100 hover:bg-slate-200 text-slate-600'
                  }`}
                >
                  {cat.label}
                </button>
              ))}
            </div>

            <div className="flex items-center gap-2 w-full sm:w-auto">
              <select
                value={selectedSupplierFilter}
                onChange={(e) => setSelectedSupplierFilter(e.target.value)}
                className="text-xs bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="all">All Suppliers</option>
                {suppliers.map((s) => (
                  <option key={s.id} value={s.id}>
                    {s.name}
                  </option>
                ))}
              </select>

              <div className="relative flex-1 sm:w-64">
                <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  type="text"
                  placeholder="Search item, barcode, location..."
                  value={localSearch}
                  onChange={(e) => setLocalSearch(e.target.value)}
                  className="w-full pl-9 pr-4 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>
          </div>

          {/* Authentic MCPIL Double-Header Inventory Table */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs border-collapse">
                <thead>
                  {/* Row 1 Headers */}
                  <tr className="bg-slate-100 text-slate-700 font-bold border-b border-slate-300">
                    <th rowSpan={2} className="p-3 border-r border-slate-300 min-w-[200px]">
                      NAME OF PRODUCTS
                    </th>
                    <th rowSpan={2} className="p-3 border-r border-slate-300 text-center font-mono">
                      BARCODE
                    </th>
                    <th rowSpan={2} className="p-3 border-r border-slate-300 text-center">
                      SIZE
                    </th>
                    <th rowSpan={2} className="p-3 border-r border-slate-300 text-center">
                      UNIT
                    </th>
                    <th rowSpan={2} className="p-3 border-r border-slate-300 text-right">
                      UNITPRICE
                    </th>
                    <th rowSpan={2} className="p-3 border-r border-slate-300 text-center">
                      CONTENT
                    </th>
                    <th colSpan={4} className="p-2 border-r border-slate-300 text-center bg-blue-50 text-blue-900">
                      BEGINNING
                    </th>
                    <th colSpan={4} className="p-2 border-r border-slate-300 text-center bg-indigo-50 text-indigo-900">
                      ENDING
                    </th>
                    <th rowSpan={2} className="p-3 border-r border-slate-300 text-center font-extrabold bg-slate-200 text-slate-900">
                      ON HAND
                    </th>
                    <th rowSpan={2} className="p-3 border-r border-slate-300 text-right font-extrabold bg-emerald-50 text-emerald-900">
                      TOTAL AMOUNT
                    </th>
                    <th rowSpan={2} className="p-3 border-r border-slate-300 text-center font-extrabold bg-rose-50 text-rose-900">
                      SUGGESTED ORDER
                    </th>
                    <th rowSpan={2} className="p-3 text-center no-print">
                      ACTIONS
                    </th>
                  </tr>
                  {/* Row 2 Sub-Headers */}
                  <tr className="bg-slate-50 text-slate-600 font-semibold text-[11px] border-b border-slate-300">
                    <th className="p-2 border-r border-slate-300 text-center bg-blue-50/70">BODEGA</th>
                    <th className="p-2 border-r border-slate-300 text-center bg-blue-50/70">SHELVES</th>
                    <th className="p-2 border-r border-slate-300 text-center bg-blue-50/70">DELIVERY</th>
                    <th className="p-2 border-r border-slate-300 text-center font-bold bg-blue-100/70">TOTAL</th>

                    <th className="p-2 border-r border-slate-300 text-center bg-indigo-50/70">BODEGA</th>
                    <th className="p-2 border-r border-slate-300 text-center bg-indigo-50/70">SHELVES</th>
                    <th className="p-2 border-r border-slate-300 text-center bg-indigo-50/70">DELIVERY</th>
                    <th className="p-2 border-r border-slate-300 text-center font-bold bg-indigo-100/70">TOTAL</th>
                  </tr>
                </thead>

                <tbody className="divide-y divide-slate-200">
                  {filteredInventory.length === 0 ? (
                    <tr>
                      <td colSpan={18} className="p-12 text-center text-slate-400">
                        <Package className="w-10 h-10 mx-auto text-slate-300 mb-2" />
                        <p className="font-semibold text-slate-600">No inventory items found.</p>
                        <p className="text-xs text-slate-400 mt-1">Try adjusting your search criteria or add a new item.</p>
                      </td>
                    </tr>
                  ) : (
                    filteredInventory.map((item) => {
                      const isLow = item.total_stock <= item.min_stock_level;
                      const hasSuggested = item.suggested_order > 0;
                      return (
                        <tr key={item.id} className="hover:bg-blue-50/30 transition-colors">
                          {/* Name & Details */}
                          <td className="p-3 border-r border-slate-200 font-bold text-slate-900">
                            <div className="flex items-center gap-1.5">
                              <span>{item.item_name}</span>
                              {isLow && (
                                <span title="Low stock alert" className="inline-block">
                                  <AlertTriangle className="w-3.5 h-3.5 text-rose-500" />
                                </span>
                              )}
                            </div>
                            <div className="text-[10.5px] font-normal text-slate-500 mt-0.5 flex items-center gap-2">
                              <span className="capitalize text-slate-600">{item.category}</span>
                              <span>•</span>
                              <span className="flex items-center gap-0.5">
                                <MapPin className="w-3 h-3 text-slate-400" />
                                {item.location}
                              </span>
                            </div>
                          </td>

                          {/* Barcode */}
                          <td className="p-3 border-r border-slate-200 text-center font-mono text-slate-600 font-semibold">
                            {item.barcode}
                          </td>

                          {/* Size */}
                          <td className="p-3 border-r border-slate-200 text-center text-slate-700 font-medium">
                            {item.size || '-'}
                          </td>

                          {/* Unit */}
                          <td className="p-3 border-r border-slate-200 text-center text-slate-700 capitalize">
                            {item.unit}
                          </td>

                          {/* Unit Price */}
                          <td className="p-3 border-r border-slate-200 text-right font-mono font-semibold text-slate-900">
                            {item.unit_price.toFixed(2)}
                          </td>

                          {/* Content */}
                          <td className="p-3 border-r border-slate-200 text-center text-slate-600 font-mono">
                            1
                          </td>

                          {/* BEGINNING STOCK */}
                          <td className="p-2 border-r border-slate-200 text-center font-mono text-slate-700 bg-blue-50/20">
                            {item.bodega_stock}
                          </td>
                          <td className="p-2 border-r border-slate-200 text-center font-mono text-slate-700 bg-blue-50/20">
                            {item.shelves_stock}
                          </td>
                          <td className="p-2 border-r border-slate-200 text-center font-mono text-slate-500 bg-blue-50/20">
                            {item.delivery_stock}
                          </td>
                          <td className="p-2 border-r border-slate-200 text-center font-mono font-bold text-blue-900 bg-blue-50/40">
                            {item.total_stock}
                          </td>

                          {/* ENDING STOCK */}
                          <td className="p-2 border-r border-slate-200 text-center font-mono text-slate-700 bg-indigo-50/20">
                            {item.bodega_stock}
                          </td>
                          <td className="p-2 border-r border-slate-200 text-center font-mono text-slate-700 bg-indigo-50/20">
                            {item.shelves_stock}
                          </td>
                          <td className="p-2 border-r border-slate-200 text-center font-mono text-slate-500 bg-indigo-50/20">
                            {item.delivery_stock}
                          </td>
                          <td className="p-2 border-r border-slate-200 text-center font-mono font-bold text-indigo-900 bg-indigo-50/40">
                            {item.total_stock}
                          </td>

                          {/* ON HAND */}
                          <td className="p-3 border-r border-slate-200 text-center font-mono font-extrabold text-slate-900 bg-slate-100/50">
                            <span
                              className={`px-2 py-0.5 rounded ${
                                isLow ? 'bg-rose-100 text-rose-800' : 'bg-slate-200/80 text-slate-800'
                              }`}
                            >
                              {item.total_stock}
                            </span>
                          </td>

                          {/* TOTAL AMOUNT */}
                          <td className="p-3 border-r border-slate-200 text-right font-mono font-bold text-emerald-800 bg-emerald-50/30">
                            ₱{item.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                          </td>

                          {/* SUGGESTED ORDER */}
                          <td
                            className={`p-3 border-r border-slate-200 text-center font-mono font-extrabold ${
                              hasSuggested ? 'text-rose-600 bg-rose-50/60 font-black' : 'text-slate-400'
                            }`}
                          >
                            {item.suggested_order}
                          </td>

                          {/* ACTIONS */}
                          <td className="p-2.5 text-center no-print">
                            <div className="flex items-center justify-center gap-1">
                              <button
                                onClick={() => {
                                  setSelectedItem(item);
                                  setBodegaChange(0);
                                  setShelvesChange(0);
                                  setDeliveryChange(0);
                                  setShowAdjustModal(true);
                                }}
                                className="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                title="Adjust stock levels"
                              >
                                <SlidersHorizontal className="w-3.5 h-3.5" />
                              </button>

                              <button
                                onClick={() => {
                                  setSelectedItem(item);
                                  setEditFormData({
                                    item_name: item.item_name,
                                    barcode: item.barcode,
                                    category: item.category,
                                    unit: item.unit,
                                    size: item.size,
                                    unit_price: item.unit_price,
                                    min_stock_level: item.min_stock_level,
                                    supplier_id: item.supplier_id,
                                    location: item.location,
                                  });
                                  setShowEditModal(true);
                                }}
                                className="p-1.5 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors"
                                title="Edit item details"
                              >
                                <Edit3 className="w-3.5 h-3.5" />
                              </button>

                              <button
                                onClick={() => {
                                  setSelectedItem(item);
                                  setShowDetailsModal(true);
                                }}
                                className="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                title="View item profile"
                              >
                                <Eye className="w-3.5 h-3.5" />
                              </button>

                              {hasSuggested && (
                                <button
                                  onClick={() => handleCreatePOFromItem(item)}
                                  className="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                  title="Create Purchase Order"
                                >
                                  <ShoppingCart className="w-3.5 h-3.5" />
                                </button>
                              )}

                              <button
                                onClick={() => setItemToDelete(item)}
                                className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                title="Delete item"
                              >
                                <Trash2 className="w-3.5 h-3.5" />
                              </button>
                            </div>
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>

                {/* Table Totals Footer */}
                {filteredInventory.length > 0 && (
                  <tfoot>
                    <tr className="bg-slate-100 text-slate-900 font-extrabold border-t-2 border-slate-300">
                      <td colSpan={6} className="p-3 border-r border-slate-300 text-right tracking-wide">
                        GRAND TOTALS
                      </td>
                      <td className="p-2 border-r border-slate-300 text-center font-mono">{totalBodega}</td>
                      <td className="p-2 border-r border-slate-300 text-center font-mono">{totalShelves}</td>
                      <td className="p-2 border-r border-slate-300 text-center font-mono">{totalDelivery}</td>
                      <td className="p-2 border-r border-slate-300 text-center font-mono text-blue-900 bg-blue-100/50">
                        {totalStockUnits}
                      </td>

                      <td className="p-2 border-r border-slate-300 text-center font-mono">{totalBodega}</td>
                      <td className="p-2 border-r border-slate-300 text-center font-mono">{totalShelves}</td>
                      <td className="p-2 border-r border-slate-300 text-center font-mono">{totalDelivery}</td>
                      <td className="p-2 border-r border-slate-300 text-center font-mono text-indigo-900 bg-indigo-100/50">
                        {totalStockUnits}
                      </td>

                      <td className="p-3 border-r border-slate-300 text-center font-mono bg-slate-200 text-slate-900">
                        {totalStockUnits}
                      </td>
                      <td className="p-3 border-r border-slate-300 text-right font-mono text-emerald-900 bg-emerald-100/50">
                        ₱{totalValuation.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                      </td>
                      <td className="p-3 border-r border-slate-300 text-center font-mono text-rose-700 bg-rose-100/50">
                        {totalSuggestedOrder}
                      </td>
                      <td className="p-3 text-center no-print">-</td>
                    </tr>
                  </tfoot>
                )}
              </table>
            </div>
          </div>
        </div>
      )}

      {/* TAB 2: ADD NEW INVENTORY ITEM (FULL FORM) */}
      {activeSubTab === 'add_form' && (
        <div className="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 max-w-4xl mx-auto space-y-6">
          <div className="border-b border-slate-200 pb-4">
            <h2 className="text-base font-extrabold text-slate-900 flex items-center gap-2">
              <Plus className="w-5 h-5 text-blue-600" />
              Add New Inventory Item
            </h2>
            <p className="text-xs text-slate-500 mt-0.5">
              Register a new reagent, pharmaceutical chemical, or laboratory consumable into the central ledger.
            </p>
          </div>

          <form onSubmit={handleAddSubmit} className="space-y-5 text-xs">
            {/* Primary Details */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block font-bold text-slate-700 mb-1">
                  Item Name <span className="text-rose-500">*</span>
                </label>
                <input
                  type="text"
                  placeholder="e.g., SODIUM HYDROXIDE 1N"
                  value={formData.item_name}
                  onChange={(e) => setFormData({ ...formData, item_name: e.target.value })}
                  required
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold uppercase focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">
                  Item Code / Barcode <span className="text-rose-500">*</span>
                </label>
                <input
                  type="text"
                  placeholder="e.g., BAR005 or CHEM-099"
                  value={formData.item_code}
                  onChange={(e) => setFormData({ ...formData, item_code: e.target.value })}
                  required
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono font-bold uppercase focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            {/* Classification & Specifications */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block font-bold text-slate-700 mb-1">
                  Category <span className="text-rose-500">*</span>
                </label>
                <select
                  value={formData.category}
                  onChange={(e) => setFormData({ ...formData, category: e.target.value as any })}
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="chemicals">Chemicals & Reagents</option>
                  <option value="consumables">Consumables & Packaging</option>
                  <option value="equipment">Laboratory Equipment</option>
                  <option value="reagents">Analytical Standards</option>
                </select>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Package Size</label>
                <input
                  type="text"
                  placeholder="e.g. 500ml, 1L, 100g, 50 tests"
                  value={formData.size}
                  onChange={(e) => setFormData({ ...formData, size: e.target.value })}
                  required
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">
                  Unit <span className="text-rose-500">*</span>
                </label>
                <input
                  type="text"
                  placeholder="e.g. bottle, box, vial, drum"
                  value={formData.unit}
                  onChange={(e) => setFormData({ ...formData, unit: e.target.value })}
                  required
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>

            {/* Pricing, Supplier, Location */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block font-bold text-slate-700 mb-1">
                  Unit Price (₱) <span className="text-rose-500">*</span>
                </label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  value={formData.unit_price}
                  onChange={(e) => setFormData({ ...formData, unit_price: Number(e.target.value) })}
                  required
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono font-bold focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">
                  Min Safety Stock Level <span className="text-rose-500">*</span>
                </label>
                <input
                  type="number"
                  min="0"
                  value={formData.min_stock_level}
                  onChange={(e) => setFormData({ ...formData, min_stock_level: Number(e.target.value) })}
                  required
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">
                  Primary Supplier <span className="text-rose-500">*</span>
                </label>
                <select
                  value={formData.supplier_id}
                  onChange={(e) => setFormData({ ...formData, supplier_id: Number(e.target.value) })}
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  {suppliers.map((s) => (
                    <option key={s.id} value={s.id}>
                      {s.name} ({s.supplier_code})
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div>
              <label className="block font-bold text-slate-700 mb-1">
                Storage Location <span className="text-rose-500">*</span>
              </label>
              <input
                type="text"
                placeholder="e.g. Shelf A-1, Room 201, Refrigerator #3"
                value={formData.location}
                onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                required
                className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            {/* Initial Stock Allocations */}
            <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
              <h3 className="font-bold text-slate-800 text-xs">Initial Stock Distribution (Beginning Inventory)</h3>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                  <label className="block font-semibold text-slate-600 mb-1">Bodega Warehouse Stock</label>
                  <input
                    type="number"
                    min="0"
                    value={formData.bodega_stock}
                    onChange={(e) => setFormData({ ...formData, bodega_stock: Number(e.target.value) })}
                    className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg font-mono font-bold"
                  />
                </div>

                <div>
                  <label className="block font-semibold text-slate-600 mb-1">Laboratory Shelves Stock</label>
                  <input
                    type="number"
                    min="0"
                    value={formData.shelves_stock}
                    onChange={(e) => setFormData({ ...formData, shelves_stock: Number(e.target.value) })}
                    className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg font-mono font-bold"
                  />
                </div>

                <div>
                  <label className="block font-semibold text-slate-600 mb-1">In-Delivery Pipeline Stock</label>
                  <input
                    type="number"
                    min="0"
                    value={formData.delivery_stock}
                    onChange={(e) => setFormData({ ...formData, delivery_stock: Number(e.target.value) })}
                    className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg font-mono font-bold"
                  />
                </div>
              </div>
            </div>

            <div className="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
              <button
                type="button"
                onClick={() =>
                  setFormData({
                    item_name: '',
                    item_code: '',
                    category: 'chemicals',
                    description: '',
                    unit: 'bottle',
                    size: '500ml',
                    content: '1',
                    quantity: 30,
                    unit_price: 150,
                    min_stock_level: 20,
                    supplier_id: suppliers[0]?.id || 1,
                    location: 'Bodega-A1',
                    bodega_stock: 20,
                    shelves_stock: 10,
                    delivery_stock: 0,
                  })
                }
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition-colors flex items-center gap-1.5"
              >
                <RotateCcw className="w-3.5 h-3.5" />
                Reset Form
              </button>

              <button
                type="submit"
                className="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-md shadow-blue-600/20 transition-all flex items-center gap-2"
              >
                <Plus className="w-4 h-4" />
                Save Item to Inventory
              </button>
            </div>
          </form>
        </div>
      )}

      {/* TAB 3: SUGGESTED REORDERS & CRITICAL WATCHLIST */}
      {activeSubTab === 'reorder' && (
        <div className="space-y-4">
          <div className="bg-rose-50 border border-rose-200 p-4 rounded-xl flex items-center justify-between">
            <div className="flex items-center gap-3">
              <AlertTriangle className="w-5 h-5 text-rose-600 shrink-0" />
              <div>
                <h3 className="font-extrabold text-rose-900 text-xs">Automated Reorder Intelligence</h3>
                <p className="text-[11px] text-rose-700">
                  {reorderCount} items have reached or breached their minimum safety threshold buffer.
                </p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                  <tr>
                    <th className="p-3.5">Reagent / Item</th>
                    <th className="p-3.5">Barcode</th>
                    <th className="p-3.5">Location</th>
                    <th className="p-3.5 text-center">Current Total</th>
                    <th className="p-3.5 text-center">Safety Buffer</th>
                    <th className="p-3.5 text-center font-extrabold text-rose-600">Suggested Order</th>
                    <th className="p-3.5 text-right">Est. Cost (PHP)</th>
                    <th className="p-3.5 text-right">Action</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {inventory
                    .filter((item) => item.suggested_order > 0 || item.total_stock <= item.min_stock_level)
                    .map((item) => {
                      const estCost = item.suggested_order * item.unit_price;
                      return (
                        <tr key={item.id} className="hover:bg-rose-50/20">
                          <td className="p-3.5">
                            <div className="font-bold text-slate-900">{item.item_name}</div>
                            <div className="text-[11px] text-slate-500">
                              {item.size} • {item.unit}
                            </div>
                          </td>
                          <td className="p-3.5 font-mono text-slate-600">{item.barcode}</td>
                          <td className="p-3.5 text-slate-700">{item.location}</td>
                          <td className="p-3.5 text-center font-mono font-bold text-rose-600">{item.total_stock}</td>
                          <td className="p-3.5 text-center font-mono text-slate-700">{item.min_stock_level}</td>
                          <td className="p-3.5 text-center font-mono font-black text-rose-700 bg-rose-50/50">
                            {item.suggested_order} {item.unit}s
                          </td>
                          <td className="p-3.5 text-right font-mono font-bold text-slate-900">
                            ₱{estCost.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                          </td>
                          <td className="p-3.5 text-right">
                            <button
                              onClick={() => handleCreatePOFromItem(item)}
                              className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition-colors"
                            >
                              <ShoppingCart className="w-3.5 h-3.5" />
                              Create PO
                            </button>
                          </td>
                        </tr>
                      );
                    })}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* TAB 4: STOCK MOVEMENT HISTORY & AUDIT LOG */}
      {activeSubTab === 'history' && (
        <div className="space-y-4">
          <div className="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
            <div className="flex flex-wrap items-center gap-1.5">
              {['all', 'beginning', 'adjustment', 'delivery', 'sale'].map((type) => (
                <button
                  key={type}
                  onClick={() => setTxTypeFilter(type)}
                  className={`px-3 py-1.5 rounded-lg text-xs font-bold capitalize transition-colors ${
                    txTypeFilter === type
                      ? 'bg-slate-900 text-white shadow-xs'
                      : 'bg-slate-100 hover:bg-slate-200 text-slate-600'
                  }`}
                >
                  {type === 'all' ? 'All Movements' : type}
                </button>
              ))}
            </div>

            <div className="relative w-full sm:w-64">
              <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                placeholder="Search transaction log..."
                value={txSearch}
                onChange={(e) => setTxSearch(e.target.value)}
                className="w-full pl-9 pr-4 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>

          <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                  <tr>
                    <th className="p-3.5">Date</th>
                    <th className="p-3.5">Item Name</th>
                    <th className="p-3.5">Movement Type</th>
                    <th className="p-3.5 text-center">Net Qty Change</th>
                    <th className="p-3.5">Reference No.</th>
                    <th className="p-3.5">Officer Responsible</th>
                    <th className="p-3.5">Audit Notes</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {filteredTransactions.length === 0 ? (
                    <tr>
                      <td colSpan={7} className="p-8 text-center text-slate-400">
                        No movement audit records found.
                      </td>
                    </tr>
                  ) : (
                    filteredTransactions.map((tx) => (
                      <tr key={tx.id} className="hover:bg-slate-50/80">
                        <td className="p-3.5 font-mono text-slate-500">{tx.transaction_date}</td>
                        <td className="p-3.5 font-bold text-slate-900">{tx.item_name}</td>
                        <td className="p-3.5">
                          <span className="capitalize px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold text-[11px]">
                            {tx.transaction_type}
                          </span>
                        </td>
                        <td className="p-3.5 text-center font-mono font-bold">
                          <span className={tx.quantity >= 0 ? 'text-emerald-600' : 'text-rose-600'}>
                            {tx.quantity > 0 ? `+${tx.quantity}` : tx.quantity}
                          </span>
                        </td>
                        <td className="p-3.5 font-mono text-blue-700 font-semibold">{tx.reference_number}</td>
                        <td className="p-3.5 text-slate-700">{tx.created_by_name}</td>
                        <td className="p-3.5 text-slate-500 max-w-xs truncate">{tx.notes}</td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* MODAL 1: ADJUST STOCK LEVEL */}
      {selectedItem && showAdjustModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div className="flex items-center justify-between border-b border-slate-200 pb-3">
              <div>
                <h2 className="font-extrabold text-slate-900 text-base">Adjust Stock Level</h2>
                <p className="text-xs text-blue-700 font-mono font-bold mt-0.5">{selectedItem.item_name}</p>
              </div>
              <button
                onClick={() => setShowAdjustModal(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleAdjustSubmit} className="space-y-4 text-xs">
              <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 grid grid-cols-3 gap-2 text-center">
                <div>
                  <div className="text-[10px] text-slate-500 uppercase font-semibold">Bodega</div>
                  <div className="text-sm font-extrabold text-slate-900">{selectedItem.bodega_stock}</div>
                </div>
                <div>
                  <div className="text-[10px] text-slate-500 uppercase font-semibold">Shelves</div>
                  <div className="text-sm font-extrabold text-slate-900">{selectedItem.shelves_stock}</div>
                </div>
                <div>
                  <div className="text-[10px] text-slate-500 uppercase font-semibold">Delivery</div>
                  <div className="text-sm font-extrabold text-slate-900">{selectedItem.delivery_stock}</div>
                </div>
              </div>

              <div className="space-y-3">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">
                    Bodega Delta (+/- to add or deduct):
                  </label>
                  <input
                    type="number"
                    value={bodegaChange}
                    onChange={(e) => setBodegaChange(Number(e.target.value))}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono font-bold"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">
                    Shelves Delta (+/- to add or deduct):
                  </label>
                  <input
                    type="number"
                    value={shelvesChange}
                    onChange={(e) => setShelvesChange(Number(e.target.value))}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono font-bold"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">
                    Delivery Delta (+/-):
                  </label>
                  <input
                    type="number"
                    value={deliveryChange}
                    onChange={(e) => setDeliveryChange(Number(e.target.value))}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono font-bold"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Audit Reference No. / Slip</label>
                  <input
                    type="text"
                    placeholder="e.g. AUD-2026-08"
                    value={adjustRef}
                    onChange={(e) => setAdjustRef(e.target.value)}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Reason / Explanation</label>
                  <input
                    type="text"
                    placeholder="e.g. Formulation usage, QC inspection sample, or breakage"
                    value={adjustNotes}
                    onChange={(e) => setAdjustNotes(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl"
                  />
                </div>
              </div>

              <div className="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <button
                  type="button"
                  onClick={() => setShowAdjustModal(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs"
                >
                  Confirm Adjustment
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 2: EDIT ITEM DETAILS */}
      {selectedItem && showEditModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div className="flex items-center justify-between border-b border-slate-200 pb-3">
              <h2 className="font-extrabold text-slate-900 text-base">Edit Inventory Item</h2>
              <button
                onClick={() => setShowEditModal(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleEditSubmit} className="space-y-4 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div className="col-span-2">
                  <label className="block font-bold text-slate-700 mb-1">Item Full Name</label>
                  <input
                    type="text"
                    value={editFormData.item_name}
                    onChange={(e) => setEditFormData({ ...editFormData, item_name: e.target.value })}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold uppercase"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Barcode</label>
                  <input
                    type="text"
                    value={editFormData.barcode}
                    onChange={(e) => setEditFormData({ ...editFormData, barcode: e.target.value })}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono font-bold"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Category</label>
                  <select
                    value={editFormData.category}
                    onChange={(e) => setEditFormData({ ...editFormData, category: e.target.value as any })}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl"
                  >
                    <option value="chemicals">Chemicals & Reagents</option>
                    <option value="consumables">Consumables & Packaging</option>
                    <option value="equipment">Equipment</option>
                    <option value="reagents">Reagents</option>
                  </select>
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Unit Price (₱)</label>
                  <input
                    type="number"
                    step="0.01"
                    value={editFormData.unit_price}
                    onChange={(e) => setEditFormData({ ...editFormData, unit_price: Number(e.target.value) })}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono font-bold"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Min Stock Safety Buffer</label>
                  <input
                    type="number"
                    value={editFormData.min_stock_level}
                    onChange={(e) => setEditFormData({ ...editFormData, min_stock_level: Number(e.target.value) })}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Size</label>
                  <input
                    type="text"
                    value={editFormData.size}
                    onChange={(e) => setEditFormData({ ...editFormData, size: e.target.value })}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Unit</label>
                  <input
                    type="text"
                    value={editFormData.unit}
                    onChange={(e) => setEditFormData({ ...editFormData, unit: e.target.value })}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl"
                  />
                </div>

                <div className="col-span-2">
                  <label className="block font-bold text-slate-700 mb-1">Storage Location</label>
                  <input
                    type="text"
                    value={editFormData.location}
                    onChange={(e) => setEditFormData({ ...editFormData, location: e.target.value })}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl"
                  />
                </div>
              </div>

              <div className="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <button
                  type="button"
                  onClick={() => setShowEditModal(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs"
                >
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL 3: ITEM DETAILS INSPECTOR */}
      {selectedItem && showDetailsModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-5">
            <div className="flex items-center justify-between border-b border-slate-200 pb-3">
              <div>
                <span className="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">
                  {selectedItem.category}
                </span>
                <h2 className="font-extrabold text-slate-900 text-lg mt-1">{selectedItem.item_name}</h2>
                <p className="text-xs text-slate-500 font-mono">{selectedItem.barcode}</p>
              </div>
              <button
                onClick={() => setShowDetailsModal(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100"
              >
                ✕
              </button>
            </div>

            <div className="grid grid-cols-2 gap-4 text-xs">
              <div className="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div className="text-[10px] text-slate-400 uppercase font-bold">Storage Location</div>
                <div className="font-extrabold text-slate-900 text-sm mt-0.5">{selectedItem.location}</div>
              </div>

              <div className="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div className="text-[10px] text-slate-400 uppercase font-bold">Unit Price</div>
                <div className="font-extrabold text-slate-900 text-sm mt-0.5 font-mono">
                  ₱{selectedItem.unit_price.toFixed(2)} / {selectedItem.unit}
                </div>
              </div>

              <div className="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div className="text-[10px] text-slate-400 uppercase font-bold">Total Stock</div>
                <div className="font-extrabold text-slate-900 text-sm mt-0.5 font-mono">
                  {selectedItem.total_stock} {selectedItem.unit}s
                </div>
              </div>

              <div className="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div className="text-[10px] text-slate-400 uppercase font-bold">Asset Valuation</div>
                <div className="font-extrabold text-emerald-800 text-sm mt-0.5 font-mono">
                  ₱{selectedItem.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                </div>
              </div>
            </div>

            {/* Distribution Breakdown */}
            <div className="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
              <div className="text-[11px] font-bold text-slate-700">Stock Allocation</div>
              <div className="grid grid-cols-3 gap-2 text-center text-xs">
                <div className="bg-white p-2.5 rounded-lg border border-slate-200">
                  <div className="text-[10px] text-slate-400">Bodega</div>
                  <div className="font-extrabold text-slate-900 mt-0.5 font-mono">{selectedItem.bodega_stock}</div>
                </div>
                <div className="bg-white p-2.5 rounded-lg border border-slate-200">
                  <div className="text-[10px] text-slate-400">Shelves</div>
                  <div className="font-extrabold text-slate-900 mt-0.5 font-mono">{selectedItem.shelves_stock}</div>
                </div>
                <div className="bg-white p-2.5 rounded-lg border border-slate-200">
                  <div className="text-[10px] text-slate-400">In-Delivery</div>
                  <div className="font-extrabold text-slate-900 mt-0.5 font-mono">{selectedItem.delivery_stock}</div>
                </div>
              </div>
            </div>

            <div className="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
              <button
                type="button"
                onClick={() => setShowDetailsModal(false)}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL 4: DELETE ITEM CONFIRMATION */}
      {itemToDelete && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div className="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto">
              <Trash2 className="w-6 h-6" />
            </div>

            <div className="text-center space-y-1">
              <h2 className="font-extrabold text-slate-900 text-base">Remove Inventory Item?</h2>
              <p className="text-xs text-slate-500">
                Are you sure you want to remove <span className="font-bold text-slate-800">{itemToDelete.item_name}</span> from the active laboratory ledger?
              </p>
            </div>

            <div className="flex items-center justify-center gap-3 pt-2">
              <button
                onClick={() => setItemToDelete(null)}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl"
              >
                Cancel
              </button>
              <button
                onClick={confirmDeleteItem}
                className="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-xs"
              >
                Yes, Remove Item
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
