import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { InventoryItem, InventoryTransaction } from '../../types';
import {
  Boxes,
  Plus,
  Search,
  AlertTriangle,
  ArrowUpDown,
  History,
  CheckCircle2,
  Package,
  Layers,
  Filter,
  Save,
  Clock,
  ChevronRight,
} from 'lucide-react';

export const InventoryView: React.FC = () => {
  const {
    inventory,
    suppliers,
    transactions,
    addInventoryItem,
    adjustStock,
    searchQuery,
  } = useApp();

  const [categoryFilter, setCategoryFilter] = useState<string>('all');
  const [localSearch, setLocalSearch] = useState('');
  const [showAddModal, setShowAddModal] = useState(false);
  const [showAdjustModal, setShowAdjustModal] = useState(false);
  const [showHistoryModal, setShowHistoryModal] = useState(false);
  const [selectedItem, setSelectedItem] = useState<InventoryItem | null>(null);

  // Adjustment form state
  const [bodegaChange, setBodegaChange] = useState<number>(0);
  const [shelvesChange, setShelvesChange] = useState<number>(0);
  const [deliveryChange, setDeliveryChange] = useState<number>(0);
  const [adjustNotes, setAdjustNotes] = useState('');

  // Add Item form state
  const [itemName, setItemName] = useState('');
  const [barcode, setBarcode] = useState('');
  const [size, setSize] = useState('500ml');
  const [unit, setUnit] = useState('bottle');
  const [unitPrice, setUnitPrice] = useState<number>(100);
  const [category, setCategory] = useState<'chemicals' | 'consumables'>('chemicals');
  const [supplierId, setSupplierId] = useState<number>(suppliers[0]?.id || 1);
  const [location, setLocation] = useState('Bodega-A1');
  const [minStock, setMinStock] = useState<number>(20);
  const [initBodega, setInitBodega] = useState<number>(20);
  const [initShelves, setInitShelves] = useState<number>(10);

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
      adjustNotes || 'Manual stock level adjustment'
    );
    setShowAdjustModal(false);
    setBodegaChange(0);
    setShelvesChange(0);
    setDeliveryChange(0);
    setAdjustNotes('');
  };

  const handleAddSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!itemName.trim()) return;

    addInventoryItem({
      item_name: itemName.trim().toUpperCase(),
      barcode: barcode.trim() || `BAR${String(inventory.length + 1).padStart(3, '0')}`,
      size,
      unit,
      unit_price: unitPrice,
      category,
      supplier_id: supplierId,
      location,
      min_stock_level: minStock,
      beginning_stock: initBodega + initShelves,
      bodega_stock: initBodega,
      shelves_stock: initShelves,
      delivery_stock: 0,
    });

    setShowAddModal(false);
    setItemName('');
    setBarcode('');
  };

  const effectiveSearch = (searchQuery || localSearch).toLowerCase();
  const filteredInventory = inventory.filter((item) => {
    let matchesCategory = true;
    if (categoryFilter === 'low_stock') {
      matchesCategory = item.total_stock <= item.min_stock_level;
    } else if (categoryFilter === 'reorder') {
      matchesCategory = item.suggested_order > 0;
    } else if (categoryFilter !== 'all') {
      matchesCategory = item.category === categoryFilter;
    }

    const matchesSearch =
      item.item_name.toLowerCase().includes(effectiveSearch) ||
      item.barcode.toLowerCase().includes(effectiveSearch) ||
      item.location.toLowerCase().includes(effectiveSearch);

    return matchesCategory && matchesSearch;
  });

  const totalStockUnits = inventory.reduce((sum, i) => sum + i.total_stock, 0);
  const totalValuation = inventory.reduce((sum, i) => sum + i.total_amount, 0);
  const lowStockCount = inventory.filter((i) => i.total_stock <= i.min_stock_level).length;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-xl font-bold text-slate-900">Chemical & Consumable Inventory</h1>
            <span className="text-xs px-2.5 py-0.5 rounded-full bg-teal-50 text-teal-700 font-bold border border-teal-200">
              {inventory.length} Chemical Reagents
            </span>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Real-time stock ledger across Bodega storage, Laboratory Shelves, and Delivery pipeline.
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <button
            onClick={() => setShowHistoryModal(true)}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors"
          >
            <History className="w-4 h-4" />
            Stock Ledger History
          </button>
          <button
            onClick={() => {
              setBarcode(`BAR${String(inventory.length + 1).padStart(3, '0')}`);
              setShowAddModal(true);
            }}
            className="inline-flex items-center gap-1.5 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow shadow-teal-700/20 transition-colors"
          >
            <Plus className="w-4 h-4" />
            Add New Item
          </button>
        </div>
      </div>

      {/* Inventory KPI Badges */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <span className="text-slate-400 font-bold uppercase tracking-wider text-[10px]">Total Stock In Warehouse</span>
          <div className="text-xl font-extrabold text-slate-900 mt-1 font-mono">{totalStockUnits} Units</div>
          <div className="text-[11px] text-slate-500 mt-0.5">Across {inventory.length} distinct laboratory SKUs</div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <span className="text-teal-600 font-bold uppercase tracking-wider text-[10px]">Valuation (PHP)</span>
          <div className="text-xl font-extrabold text-teal-800 mt-1 font-mono">
            ₱{totalValuation.toLocaleString(undefined, { minimumFractionDigits: 2 })}
          </div>
          <div className="text-[11px] text-teal-600 mt-0.5 font-semibold">Active pharmaceutical asset value</div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <span className="text-rose-600 font-bold uppercase tracking-wider text-[10px]">Critical Threshold Alerts</span>
          <div className="text-xl font-extrabold text-rose-700 mt-1 font-mono">{lowStockCount} Reagents Low</div>
          <div className="text-[11px] text-rose-600 mt-0.5 font-semibold">
            {lowStockCount > 0 ? 'Replenishment purchase order recommended' : 'All stocks above safety threshold'}
          </div>
        </div>
      </div>

      {/* Filters and search */}
      <div className="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div className="flex flex-wrap items-center gap-1.5">
          {[
            { id: 'all', label: 'All Items' },
            { id: 'chemicals', label: 'Chemicals' },
            { id: 'consumables', label: 'Consumables' },
            { id: 'low_stock', label: `Low Stock Alert (${lowStockCount})` },
            { id: 'reorder', label: 'Reorder Needed' },
          ].map((cat) => (
            <button
              key={cat.id}
              onClick={() => setCategoryFilter(cat.id)}
              className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-colors ${
                categoryFilter === cat.id
                  ? 'bg-slate-900 text-white shadow-sm'
                  : 'bg-slate-100 hover:bg-slate-200 text-slate-600'
              }`}
            >
              {cat.label}
            </button>
          ))}
        </div>

        <div className="relative w-full sm:w-64">
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search chemical name, barcode, location..."
            value={localSearch}
            onChange={(e) => setLocalSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
          />
        </div>
      </div>

      {/* Inventory Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
              <tr>
                <th className="p-3.5">Item Name & Specs</th>
                <th className="p-3.5">Barcode / SKU</th>
                <th className="p-3.5">Category</th>
                <th className="p-3.5">Location</th>
                <th className="p-3.5 text-right">Unit Price</th>
                <th className="p-3.5 text-center">Bodega</th>
                <th className="p-3.5 text-center">Shelves</th>
                <th className="p-3.5 text-center">In-Delivery</th>
                <th className="p-3.5 text-center">Total Stock</th>
                <th className="p-3.5 text-right">Valuation</th>
                <th className="p-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {filteredInventory.length === 0 ? (
                <tr>
                  <td colSpan={11} className="p-8 text-center text-slate-400">
                    No items found matching the current search criteria.
                  </td>
                </tr>
              ) : (
                filteredInventory.map((item) => {
                  const isLow = item.total_stock <= item.min_stock_level;
                  return (
                    <tr key={item.id} className="hover:bg-slate-50/80 transition-colors">
                      <td className="p-3.5">
                        <div className="font-bold text-slate-900">{item.item_name}</div>
                        <div className="text-[11px] text-slate-500">
                          {item.size} • {item.unit}
                        </div>
                      </td>
                      <td className="p-3.5 font-mono text-slate-600 font-semibold">{item.barcode}</td>
                      <td className="p-3.5">
                        <span
                          className={`px-2 py-0.5 rounded-md text-[10px] font-bold uppercase ${
                            item.category === 'chemicals'
                              ? 'bg-teal-50 text-teal-700 border border-teal-200/60'
                              : 'bg-blue-50 text-blue-700 border border-blue-200/60'
                          }`}
                        >
                          {item.category}
                        </span>
                      </td>
                      <td className="p-3.5 font-medium text-slate-700">{item.location}</td>
                      <td className="p-3.5 text-right font-mono font-semibold text-slate-800">
                        ₱{item.unit_price.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                      </td>
                      <td className="p-3.5 text-center font-mono text-slate-700">{item.bodega_stock}</td>
                      <td className="p-3.5 text-center font-mono text-slate-700">{item.shelves_stock}</td>
                      <td className="p-3.5 text-center font-mono text-slate-500">+{item.delivery_stock}</td>
                      <td className="p-3.5 text-center">
                        <div
                          className={`inline-flex items-center gap-1 font-mono font-extrabold px-2 py-0.5 rounded ${
                            isLow ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-800'
                          }`}
                        >
                          {item.total_stock}
                          {isLow && <AlertTriangle className="w-3 h-3 text-rose-600" />}
                        </div>
                      </td>
                      <td className="p-3.5 text-right font-mono font-bold text-slate-900">
                        ₱{item.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                      </td>
                      <td className="p-3.5 text-right">
                        <button
                          onClick={() => {
                            setSelectedItem(item);
                            setBodegaChange(0);
                            setShelvesChange(0);
                            setDeliveryChange(0);
                            setShowAdjustModal(true);
                          }}
                          className="px-2.5 py-1 text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 rounded-lg transition-colors"
                        >
                          Adjust Stock
                        </button>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* MODAL: Adjust Stock */}
      {selectedItem && showAdjustModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
              <div>
                <h2 className="font-bold text-slate-900 text-base">Adjust Stock Quantities</h2>
                <p className="text-xs text-teal-700 font-mono font-bold mt-0.5">{selectedItem.item_name}</p>
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
                    Bodega Delta (+/- to add/subtract):
                  </label>
                  <input
                    type="number"
                    value={bodegaChange}
                    onChange={(e) => setBodegaChange(Number(e.target.value))}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono font-bold"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">
                    Shelves Delta (+/- to add/subtract):
                  </label>
                  <input
                    type="number"
                    value={shelvesChange}
                    onChange={(e) => setShelvesChange(Number(e.target.value))}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono font-bold"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Reason / Reference Number</label>
                  <input
                    type="text"
                    placeholder="e.g., QC batch testing usage, formulation spill, or audit count"
                    value={adjustNotes}
                    onChange={(e) => setAdjustNotes(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
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
                  className="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow"
                >
                  Save Stock Adjustment
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* MODAL: Stock Transaction History Drawer */}
      {showHistoryModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-3xl w-full max-h-[85vh] overflow-y-auto p-6 shadow-2xl border border-slate-200">
            <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
              <div className="flex items-center gap-2">
                <History className="w-5 h-5 text-teal-600" />
                <h2 className="font-bold text-slate-900 text-base">Inventory Movement & Audit Ledger</h2>
              </div>
              <button
                onClick={() => setShowHistoryModal(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100"
              >
                ✕
              </button>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                  <tr>
                    <th className="p-3">Date</th>
                    <th className="p-3">Item</th>
                    <th className="p-3">Type</th>
                    <th className="p-3 text-center">Net Qty</th>
                    <th className="p-3">Reference</th>
                    <th className="p-3">Officer</th>
                    <th className="p-3">Notes</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {transactions.map((tx) => (
                    <tr key={tx.id} className="hover:bg-slate-50/80">
                      <td className="p-3 font-mono text-slate-500">{tx.transaction_date}</td>
                      <td className="p-3 font-bold text-slate-900">{tx.item_name}</td>
                      <td className="p-3">
                        <span className="capitalize px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-semibold">
                          {tx.transaction_type}
                        </span>
                      </td>
                      <td className="p-3 text-center font-mono font-bold">
                        <span className={tx.quantity >= 0 ? 'text-emerald-600' : 'text-rose-600'}>
                          {tx.quantity > 0 ? `+${tx.quantity}` : tx.quantity}
                        </span>
                      </td>
                      <td className="p-3 font-mono text-teal-700">{tx.reference_number}</td>
                      <td className="p-3 text-slate-600">{tx.created_by_name}</td>
                      <td className="p-3 text-slate-500 text-[11px] max-w-xs truncate">{tx.notes}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* MODAL: Add New Inventory Item */}
      {showAddModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-200">
            <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
              <div className="flex items-center gap-2">
                <Boxes className="w-5 h-5 text-teal-600" />
                <h2 className="font-bold text-slate-900 text-base">Add New Chemical / Consumable</h2>
              </div>
              <button
                onClick={() => setShowAddModal(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleAddSubmit} className="space-y-4 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div className="col-span-2">
                  <label className="block font-bold text-slate-700 mb-1">Item Full Name</label>
                  <input
                    type="text"
                    placeholder="e.g. SODIUM HYDROXIDE 1N"
                    value={itemName}
                    onChange={(e) => setItemName(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-bold"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Barcode / SKU</label>
                  <input
                    type="text"
                    value={barcode}
                    onChange={(e) => setBarcode(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono font-bold"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Category</label>
                  <select
                    value={category}
                    onChange={(e) => setCategory(e.target.value as any)}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  >
                    <option value="chemicals">Chemicals & Reagents</option>
                    <option value="consumables">Consumables & Packaging</option>
                  </select>
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Package Size</label>
                  <input
                    type="text"
                    placeholder="e.g. 500ml, 1L, 100g"
                    value={size}
                    onChange={(e) => setSize(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Unit</label>
                  <input
                    type="text"
                    placeholder="bottle, box, vial"
                    value={unit}
                    onChange={(e) => setUnit(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Unit Price (₱)</label>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    value={unitPrice}
                    onChange={(e) => setUnitPrice(Number(e.target.value))}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono font-bold"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Storage Location</label>
                  <input
                    type="text"
                    placeholder="e.g. Bodega-A3, Shelves-B2"
                    value={location}
                    onChange={(e) => setLocation(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Minimum Safety Stock</label>
                  <input
                    type="number"
                    min="0"
                    value={minStock}
                    onChange={(e) => setMinStock(Number(e.target.value))}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Primary Supplier</label>
                  <select
                    value={supplierId}
                    onChange={(e) => setSupplierId(Number(e.target.value))}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  >
                    {suppliers.map((s) => (
                      <option key={s.id} value={s.id}>
                        {s.name}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Initial Bodega Stock</label>
                  <input
                    type="number"
                    min="0"
                    value={initBodega}
                    onChange={(e) => setInitBodega(Number(e.target.value))}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Initial Shelves Stock</label>
                  <input
                    type="number"
                    min="0"
                    value={initShelves}
                    onChange={(e) => setInitShelves(Number(e.target.value))}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono"
                  />
                </div>
              </div>

              <div className="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <button
                  type="button"
                  onClick={() => setShowAddModal(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow"
                >
                  Save Item to Catalog
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
