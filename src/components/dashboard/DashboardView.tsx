import React from 'react';
import { useApp } from '../../context/AppContext';
import {
  FileText,
  Boxes,
  Truck,
  Users,
  AlertTriangle,
  ArrowUpRight,
  TrendingUp,
  Clock,
  Camera,
  CheckCircle2,
  Receipt,
  Plus,
  ChevronRight,
  MapPin,
  Calendar,
  Building2,
} from 'lucide-react';
import {
  ResponsiveContainer,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  PieChart,
  Pie,
  Cell,
  CartesianGrid,
} from 'recharts';

export const DashboardView: React.FC = () => {
  const {
    purchaseOrders,
    inventory,
    deliveries,
    employees,
    attendanceRecords,
    cameraLogs,
    invoices,
    setActiveTab,
    currentUser,
  } = useApp();

  // Metrics
  const totalPOAmount = purchaseOrders.reduce((sum, p) => sum + p.total_amount, 0);
  const pendingPOCount = purchaseOrders.filter((p) => p.status === 'Pending').length;
  const approvedPOCount = purchaseOrders.filter((p) => p.status === 'Approved').length;

  const totalInventoryValuation = inventory.reduce((sum, i) => sum + i.total_amount, 0);
  const lowStockItems = inventory.filter((i) => i.total_stock <= i.min_stock_level);
  const totalStockUnits = inventory.reduce((sum, i) => sum + i.total_stock, 0);

  const activeDeliveries = deliveries.filter((d) => d.status === 'in_transit' || d.status === 'pending');
  const staffOnDuty = attendanceRecords.filter((a) => a.status === 'present').length;

  // PO status distribution data for chart
  const poStatusData = [
    { name: 'Pending', count: purchaseOrders.filter((p) => p.status === 'Pending').length, fill: '#f59e0b' },
    { name: 'Approved', count: purchaseOrders.filter((p) => p.status === 'Approved').length, fill: '#3b82f6' },
    { name: 'Processing', count: purchaseOrders.filter((p) => p.status === 'Processing').length, fill: '#8b5cf6' },
    { name: 'Completed', count: purchaseOrders.filter((p) => p.status === 'Completed').length, fill: '#10b981' },
    { name: 'Rejected', count: purchaseOrders.filter((p) => p.status === 'Rejected').length, fill: '#ef4444' },
  ];

  // Category valuation data
  const categoryData = [
    {
      name: 'Chemicals',
      value: inventory.filter((i) => i.category === 'chemicals').reduce((acc, i) => acc + i.total_amount, 0),
      color: '#0d9488',
    },
    {
      name: 'Consumables',
      value: inventory.filter((i) => i.category === 'consumables').reduce((acc, i) => acc + i.total_amount, 0),
      color: '#0284c7',
    },
  ];

  // Weekly attendance trend (mock historical + current)
  const attendanceTrendData = [
    { day: 'Mon', present: 5, late: 0 },
    { day: 'Tue', present: 4, late: 1 },
    { day: 'Wed', present: 5, late: 0 },
    { day: 'Thu', present: 5, late: 0 },
    { day: 'Fri', present: 4, late: 1 },
  ];

  return (
    <div className="space-y-6">
      {/* Welcome Banner */}
      <div className="bg-gradient-to-r from-teal-900 via-teal-800 to-slate-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
        <div className="absolute right-0 top-0 bottom-0 w-1/3 bg-gradient-to-l from-emerald-500/10 to-transparent pointer-events-none" />
        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div className="flex items-center gap-2 text-teal-300 text-xs font-semibold uppercase tracking-wider mb-1">
              <Building2 className="w-4 h-4" />
              MCPIL Laboratory System • Live Operations Hub
            </div>
            <h1 className="text-2xl lg:text-3xl font-extrabold tracking-tight">
              Good day, {currentUser.full_name}
            </h1>
            <p className="text-sm text-teal-100/80 mt-1 max-w-2xl">
              Monitor active requisitions, chemical stocks in Bodega & Shelves, live carrier delivery pipelines, and staff camera attendance.
            </p>
          </div>

          <div className="flex flex-wrap gap-2">
            <button
              onClick={() => setActiveTab('purchase_orders')}
              className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-teal-500 hover:bg-teal-400 text-white font-bold text-xs shadow transition-all"
            >
              <Plus className="w-4 h-4" />
              New Purchase Order
            </button>
            <button
              onClick={() => setActiveTab('camera_attendance')}
              className="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs backdrop-blur-sm border border-white/20 transition-all"
            >
              <Camera className="w-4 h-4" />
              Camera Check-in
            </button>
          </div>
        </div>
      </div>

      {/* KPI Cards Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Total Purchase Orders */}
        <div
          onClick={() => setActiveTab('purchase_orders')}
          className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:shadow-md hover:border-teal-300 transition-all cursor-pointer group"
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">Purchase Orders</span>
            <div className="p-2 bg-indigo-50 text-indigo-600 rounded-lg group-hover:scale-110 transition-transform">
              <FileText className="w-5 h-5" />
            </div>
          </div>
          <div className="mt-3">
            <div className="text-2xl font-extrabold text-slate-900">
              ₱{totalPOAmount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            </div>
            <div className="flex items-center justify-between text-xs text-slate-500 mt-1">
              <span>{purchaseOrders.length} total orders</span>
              <span className="text-amber-600 font-bold">{pendingPOCount} pending</span>
            </div>
          </div>
        </div>

        {/* Inventory Stock Valuation */}
        <div
          onClick={() => setActiveTab('inventory')}
          className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:shadow-md hover:border-teal-300 transition-all cursor-pointer group"
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">Inventory Valuation</span>
            <div className="p-2 bg-teal-50 text-teal-600 rounded-lg group-hover:scale-110 transition-transform">
              <Boxes className="w-5 h-5" />
            </div>
          </div>
          <div className="mt-3">
            <div className="text-2xl font-extrabold text-slate-900">
              ₱{totalInventoryValuation.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
            </div>
            <div className="flex items-center justify-between text-xs text-slate-500 mt-1">
              <span>{totalStockUnits} stock units</span>
              {lowStockItems.length > 0 ? (
                <span className="text-rose-600 font-bold flex items-center gap-1">
                  <AlertTriangle className="w-3 h-3" />
                  {lowStockItems.length} low stock
                </span>
              ) : (
                <span className="text-emerald-600 font-semibold">Adequate levels</span>
              )}
            </div>
          </div>
        </div>

        {/* Active Deliveries */}
        <div
          onClick={() => setActiveTab('delivery_tracking')}
          className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:shadow-md hover:border-teal-300 transition-all cursor-pointer group"
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Shipments</span>
            <div className="p-2 bg-emerald-50 text-emerald-600 rounded-lg group-hover:scale-110 transition-transform">
              <Truck className="w-5 h-5" />
            </div>
          </div>
          <div className="mt-3">
            <div className="text-2xl font-extrabold text-slate-900">{activeDeliveries.length} In-Transit</div>
            <div className="flex items-center justify-between text-xs text-slate-500 mt-1">
              <span>{deliveries.length} total deliveries</span>
              <span className="text-emerald-600 font-semibold flex items-center gap-1">
                <CheckCircle2 className="w-3 h-3" />
                {deliveries.filter((d) => d.status === 'delivered').length} received
              </span>
            </div>
          </div>
        </div>

        {/* Staff Attendance */}
        <div
          onClick={() => setActiveTab('attendance')}
          className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm hover:shadow-md hover:border-teal-300 transition-all cursor-pointer group"
        >
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">Staff On Duty</span>
            <div className="p-2 bg-purple-50 text-purple-600 rounded-lg group-hover:scale-110 transition-transform">
              <Users className="w-5 h-5" />
            </div>
          </div>
          <div className="mt-3">
            <div className="text-2xl font-extrabold text-slate-900">
              {staffOnDuty} / {employees.length}
            </div>
            <div className="flex items-center justify-between text-xs text-slate-500 mt-1">
              <span className="text-emerald-600 font-semibold">100% attendance rate</span>
              <span className="text-slate-400">Today</span>
            </div>
          </div>
        </div>
      </div>

      {/* Charts & Quick Visuals Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* PO Status Breakdown Chart */}
        <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm lg:col-span-2">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h2 className="text-sm font-bold text-slate-900">Purchase Order Distribution by Status</h2>
              <p className="text-xs text-slate-500">Live operational status across all laboratory orders</p>
            </div>
            <button
              onClick={() => setActiveTab('purchase_orders')}
              className="text-xs font-semibold text-teal-600 hover:text-teal-700 flex items-center gap-1"
            >
              View all POs <ChevronRight className="w-3.5 h-3.5" />
            </button>
          </div>
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <BarChart data={poStatusData} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                <XAxis dataKey="name" tick={{ fontSize: 12, fill: '#64748b' }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 12, fill: '#64748b' }} axisLine={false} tickLine={false} allowDecimals={false} />
                <Tooltip
                  cursor={{ fill: '#f8fafc' }}
                  contentStyle={{
                    borderRadius: '8px',
                    border: '1px solid #e2e8f0',
                    boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
                  }}
                />
                <Bar dataKey="count" radius={[6, 6, 0, 0]}>
                  {poStatusData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.fill} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Inventory Category Value Distribution */}
        <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h2 className="text-sm font-bold text-slate-900">Stock Valuation by Category</h2>
              <p className="text-xs text-slate-500">Chemicals vs Consumables</p>
            </div>
          </div>
          <div className="h-48 flex items-center justify-center">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={categoryData}
                  cx="50%"
                  cy="50%"
                  innerRadius={50}
                  outerRadius={75}
                  paddingAngle={4}
                  dataKey="value"
                >
                  {categoryData.map((entry, index) => (
                    <Cell key={`pie-cell-${index}`} fill={entry.color} />
                  ))}
                </Pie>
                <Tooltip
                  formatter={(val: any) => `₱${Number(val).toLocaleString(undefined, { minimumFractionDigits: 2 })}`}
                  contentStyle={{
                    borderRadius: '8px',
                    border: '1px solid #e2e8f0',
                  }}
                />
              </PieChart>
            </ResponsiveContainer>
          </div>
          <div className="mt-2 space-y-2">
            {categoryData.map((cat) => (
              <div key={cat.name} className="flex items-center justify-between text-xs">
                <div className="flex items-center gap-2">
                  <span className="w-3 h-3 rounded-full" style={{ backgroundColor: cat.color }}></span>
                  <span className="font-medium text-slate-700">{cat.name}</span>
                </div>
                <span className="font-bold text-slate-900">
                  ₱{cat.value.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                </span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Two Column Section: Recent POs & Urgent Stock Alerts */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Recent Purchase Orders Table */}
        <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm lg:col-span-2">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h2 className="text-sm font-bold text-slate-900">Recent Purchase Orders</h2>
              <p className="text-xs text-slate-500">Latest procurement requisitions & authorization status</p>
            </div>
            <button
              onClick={() => setActiveTab('purchase_orders')}
              className="text-xs font-semibold text-teal-600 hover:text-teal-700 flex items-center gap-1"
            >
              All POs <ChevronRight className="w-3.5 h-3.5" />
            </button>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                <tr>
                  <th className="p-3">PO Number</th>
                  <th className="p-3">Supplier</th>
                  <th className="p-3">Order Date</th>
                  <th className="p-3">Total Amount</th>
                  <th className="p-3">Status</th>
                  <th className="p-3 text-right">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {purchaseOrders.slice(0, 5).map((po) => (
                  <tr key={po.id} className="hover:bg-slate-50/80 transition-colors">
                    <td className="p-3 font-mono font-bold text-teal-700">{po.po_number}</td>
                    <td className="p-3 font-medium text-slate-800">{po.store_name}</td>
                    <td className="p-3 text-slate-500">{po.order_date}</td>
                    <td className="p-3 font-bold text-slate-900">
                      ₱{po.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </td>
                    <td className="p-3">
                      <span
                        className={`inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold ${
                          po.status === 'Approved'
                            ? 'bg-blue-100 text-blue-700'
                            : po.status === 'Completed'
                            ? 'bg-emerald-100 text-emerald-700'
                            : po.status === 'Processing'
                            ? 'bg-purple-100 text-purple-700'
                            : po.status === 'Pending'
                            ? 'bg-amber-100 text-amber-700'
                            : 'bg-rose-100 text-rose-700'
                        }`}
                      >
                        {po.status}
                      </span>
                    </td>
                    <td className="p-3 text-right">
                      <button
                        onClick={() => setActiveTab('purchase_orders')}
                        className="text-xs text-teal-600 hover:text-teal-800 font-semibold"
                      >
                        Inspect
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Low Stock Warning & Camera Attendance Today */}
        <div className="space-y-6">
          {/* Low stock card */}
          <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div className="flex items-center justify-between mb-3">
              <div className="flex items-center gap-2">
                <div className="p-1.5 bg-rose-50 text-rose-600 rounded-lg">
                  <AlertTriangle className="w-4 h-4" />
                </div>
                <h2 className="text-sm font-bold text-slate-900">Low Stock Reagents</h2>
              </div>
              <span className="text-xs font-bold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">
                {lowStockItems.length} Alert{lowStockItems.length !== 1 ? 's' : ''}
              </span>
            </div>

            <div className="space-y-3">
              {lowStockItems.slice(0, 3).map((item) => (
                <div
                  key={item.id}
                  className="p-3 rounded-lg bg-rose-50/40 border border-rose-100 flex items-center justify-between text-xs"
                >
                  <div>
                    <div className="font-bold text-slate-800">{item.item_name}</div>
                    <div className="text-[11px] text-slate-500">
                      Min Threshold: {item.min_stock_level} {item.unit}s • Location: {item.location}
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="font-extrabold text-rose-600 text-sm">{item.total_stock} left</div>
                    <div className="text-[10px] text-amber-700 font-semibold">
                      Order +{item.suggested_order}
                    </div>
                  </div>
                </div>
              ))}
              {lowStockItems.length === 0 && (
                <p className="text-xs text-slate-400 text-center py-4">All reagents are adequately stocked.</p>
              )}
            </div>

            <button
              onClick={() => setActiveTab('inventory')}
              className="w-full mt-3 py-2 text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 rounded-lg transition-colors text-center block"
            >
              Manage Chemical Inventory
            </button>
          </div>

          {/* Today's Camera Attendance Preview */}
          <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div className="flex items-center justify-between mb-3">
              <div className="flex items-center gap-2">
                <div className="p-1.5 bg-teal-50 text-teal-600 rounded-lg">
                  <Camera className="w-4 h-4" />
                </div>
                <h2 className="text-sm font-bold text-slate-900">Biometric Camera Station</h2>
              </div>
              <span className="text-xs text-emerald-600 font-semibold flex items-center gap-1">
                <span className="w-2 h-2 rounded-full bg-emerald-500"></span> Live
              </span>
            </div>

            <p className="text-xs text-slate-500 mb-3">
              Latest facial check-in with GPS geo-location & temperature telemetry:
            </p>

            {cameraLogs.length > 0 ? (
              <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 flex items-start gap-3">
                <img
                  src={cameraLogs[0].photo_path}
                  onError={(e) => {
                    // Fallback to avatar if local image not found
                    (e.currentTarget as HTMLImageElement).src =
                      'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&auto=format&fit=crop&q=80';
                  }}
                  alt={cameraLogs[0].employee_name}
                  className="w-12 h-12 rounded-lg object-cover ring-1 ring-teal-500 shadow-sm"
                />
                <div className="flex-1 min-w-0 text-xs">
                  <div className="font-bold text-slate-900 truncate">{cameraLogs[0].employee_name}</div>
                  <div className="text-slate-500 text-[11px] flex items-center gap-1 mt-0.5">
                    <Clock className="w-3 h-3 text-slate-400" />
                    {cameraLogs[0].capture_time} • {cameraLogs[0].temperature}°C
                  </div>
                  <div className="text-slate-500 text-[10px] truncate flex items-center gap-1 mt-0.5">
                    <MapPin className="w-3 h-3 text-teal-600" />
                    {cameraLogs[0].location_address}
                  </div>
                </div>
              </div>
            ) : null}

            <button
              onClick={() => setActiveTab('camera_attendance')}
              className="w-full mt-3 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors text-center block"
            >
              Open Camera Check-in Station
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};
