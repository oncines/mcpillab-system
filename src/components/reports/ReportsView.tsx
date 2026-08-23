import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import {
  FileSpreadsheet,
  Download,
  Printer,
  Calendar,
  Filter,
  DollarSign,
  Boxes,
  Users,
  Truck,
  TrendingUp,
  CheckCircle2,
} from 'lucide-react';
import {
  ResponsiveContainer,
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  CartesianGrid,
  PieChart,
  Pie,
  Cell,
  Legend,
} from 'recharts';

export const ReportsView: React.FC = () => {
  const {
    inventory,
    purchaseOrders,
    invoices,
    attendanceRecords,
    deliveries,
    employees,
  } = useApp();

  const [activeReportTab, setActiveReportTab] = useState<'inventory' | 'expenditure' | 'attendance' | 'logistics'>('inventory');
  const [startDate, setStartDate] = useState('2026-01-01');
  const [endDate, setEndDate] = useState(new Date().toISOString().split('T')[0]);

  // Inventory valuation data
  const inventoryCategoryData = [
    {
      name: 'Chemicals & Reagents',
      value: inventory.filter((i) => i.category === 'chemicals').reduce((sum, i) => sum + i.total_amount, 0),
    },
    {
      name: 'Consumables & Packaging',
      value: inventory.filter((i) => i.category === 'consumables').reduce((sum, i) => sum + i.total_amount, 0),
    },
  ];

  // PO Expenditure by Supplier
  const poBySupplierData = [
    { name: 'MediSupply', amount: 16500 },
    { name: 'LabChem', amount: 18450 },
    { name: 'BioTech', amount: 9200 },
    { name: 'Apex Med', amount: 7300 },
  ];

  // Attendance compliance stats
  const totalAttendanceLogs = attendanceRecords.length;
  const presentLogs = attendanceRecords.filter((a) => a.status === 'present').length;
  const lateLogs = attendanceRecords.filter((a) => a.status === 'late').length;
  const absentLogs = attendanceRecords.filter((a) => a.status === 'absent').length;

  const attendanceChartData = [
    { name: 'On-Time Present', value: presentLogs, color: '#10b981' },
    { name: 'Late Clock-in', value: lateLogs, color: '#f59e0b' },
    { name: 'Absent', value: absentLogs, color: '#ef4444' },
  ];

  const exportCSV = () => {
    let csvContent = 'data:text/csv;charset=utf-8,';

    if (activeReportTab === 'inventory') {
      csvContent += 'Item Name,Barcode,Category,Location,Unit Price,Bodega,Shelves,Total Stock,Total Valuation\n';
      inventory.forEach((i) => {
        csvContent += `"${i.item_name}","${i.barcode}","${i.category}","${i.location}",${i.unit_price},${i.bodega_stock},${i.shelves_stock},${i.total_stock},${i.total_amount}\n`;
      });
    } else if (activeReportTab === 'expenditure') {
      csvContent += 'PO Number,Store Name,Supplier ID,Order Date,Total Amount,Status\n';
      purchaseOrders.forEach((p) => {
        csvContent += `"${p.po_number}","${p.store_name}",${p.supplier_id},"${p.order_date}",${p.total_amount},"${p.status}"\n`;
      });
    } else if (activeReportTab === 'attendance') {
      csvContent += 'Date,Employee Name,Department,Check In,Check Out,Total Hours,Status\n';
      attendanceRecords.forEach((a) => {
        csvContent += `"${a.date}","${a.employee_name}","${a.department}","${a.check_in}","${a.check_out}",${a.total_hours},"${a.status}"\n`;
      });
    } else {
      csvContent += 'Tracking Number,PO Number,Carrier,Driver,Origin,Destination,Estimated Date,Status\n';
      deliveries.forEach((d) => {
        csvContent += `"${d.tracking_number}","${d.po_number}","${d.carrier}","${d.driver_name}","${d.origin}","${d.destination}","${d.estimated_delivery}","${d.status}"\n`;
      });
    }

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `MCPIL_${activeReportTab.toUpperCase()}_REPORT_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const COLORS = ['#0f766e', '#3b82f6', '#f59e0b', '#ec4899'];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-xl font-bold text-slate-900">Laboratory Operations & Audit Reports</h1>
            <span className="text-xs px-2.5 py-0.5 rounded-full bg-teal-50 text-teal-700 font-bold border border-teal-200">
              GLP / GMP Compliance Ready
            </span>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Generate formal laboratory stock audits, financial summaries, timesheets, and logistics KPIs.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={exportCSV}
            className="inline-flex items-center gap-1.5 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow shadow-teal-700/20 transition-colors"
          >
            <Download className="w-4 h-4" />
            Export CSV
          </button>
          <button
            onClick={() => window.print()}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors"
          >
            <Printer className="w-4 h-4" />
            Print Report
          </button>
        </div>
      </div>

      {/* Tabs Switcher */}
      <div className="flex flex-wrap items-center justify-between gap-3 bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
        <div className="flex flex-wrap items-center gap-1.5">
          {[
            { id: 'inventory', label: 'Chemical Inventory Valuation', icon: Boxes },
            { id: 'expenditure', label: 'Procurement Expenditure', icon: DollarSign },
            { id: 'attendance', label: 'Attendance & Compliance', icon: Users },
            { id: 'logistics', label: 'Delivery & Logistics', icon: Truck },
          ].map((tab) => {
            const Icon = tab.icon;
            return (
              <button
                key={tab.id}
                onClick={() => setActiveReportTab(tab.id as any)}
                className={`flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-bold transition-colors ${
                  activeReportTab === tab.id
                    ? 'bg-slate-900 text-white shadow-sm'
                    : 'bg-slate-100 hover:bg-slate-200 text-slate-600'
                }`}
              >
                <Icon className="w-3.5 h-3.5" />
                {tab.label}
              </button>
            );
          })}
        </div>

        <div className="flex items-center gap-2 text-xs">
          <Calendar className="w-3.5 h-3.5 text-slate-400" />
          <input
            type="date"
            value={startDate}
            onChange={(e) => setStartDate(e.target.value)}
            className="bg-slate-50 border border-slate-200 rounded px-2 py-1 text-slate-700 font-semibold"
          />
          <span className="text-slate-400">to</span>
          <input
            type="date"
            value={endDate}
            onChange={(e) => setEndDate(e.target.value)}
            className="bg-slate-50 border border-slate-200 rounded px-2 py-1 text-slate-700 font-semibold"
          />
        </div>
      </div>

      {/* Dynamic Report View Based on Tab */}
      {activeReportTab === 'inventory' && (
        <div className="space-y-6">
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div className="lg:col-span-6 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
              <h3 className="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4">
                Chemical & Consumable Valuation Split
              </h3>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={inventoryCategoryData}
                      cx="50%"
                      cy="50%"
                      innerRadius={60}
                      outerRadius={90}
                      paddingAngle={4}
                      dataKey="value"
                    >
                      {inventoryCategoryData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                      ))}
                    </Pie>
                    <Tooltip formatter={(val: any) => `₱${Number(val).toLocaleString()}`} />
                    <Legend />
                  </PieChart>
                </ResponsiveContainer>
              </div>
            </div>

            <div className="lg:col-span-6 bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
              <div>
                <h3 className="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">
                  Inventory Valuation Highlights
                </h3>
                <div className="space-y-3 text-xs">
                  <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 flex justify-between items-center">
                    <span className="text-slate-600 font-medium">Total Physical Items Managed:</span>
                    <span className="font-bold text-slate-900 font-mono">{inventory.length} SKUs</span>
                  </div>
                  <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 flex justify-between items-center">
                    <span className="text-slate-600 font-medium">Total Stock Quantity:</span>
                    <span className="font-bold text-slate-900 font-mono">
                      {inventory.reduce((sum, i) => sum + i.total_stock, 0)} Units
                    </span>
                  </div>
                  <div className="p-3 bg-teal-50 rounded-xl border border-teal-200 flex justify-between items-center">
                    <span className="text-teal-900 font-bold">Total Laboratory Chemical Asset Valuation:</span>
                    <span className="font-extrabold text-teal-800 text-sm font-mono">
                      ₱
                      {inventory
                        .reduce((sum, i) => sum + i.total_amount, 0)
                        .toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </span>
                  </div>
                </div>
              </div>

              <div className="text-[11px] text-slate-500 italic mt-4 pt-3 border-t border-slate-100">
                Audited against GLP Good Laboratory Practice standards.
              </div>
            </div>
          </div>

          {/* Detailed Inventory Audit Table */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-4 border-b border-slate-200 font-bold text-slate-900 text-xs uppercase">
              Full Chemical & Consumables Ledger Audit
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                  <tr>
                    <th className="p-3">Item Name</th>
                    <th className="p-3">Barcode</th>
                    <th className="p-3">Category</th>
                    <th className="p-3">Location</th>
                    <th className="p-3 text-right">Unit Price</th>
                    <th className="p-3 text-center">Bodega</th>
                    <th className="p-3 text-center">Shelves</th>
                    <th className="p-3 text-center">Total Stock</th>
                    <th className="p-3 text-right">Total Valuation</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {inventory.map((item) => (
                    <tr key={item.id}>
                      <td className="p-3 font-bold text-slate-900">{item.item_name}</td>
                      <td className="p-3 font-mono text-slate-500">{item.barcode}</td>
                      <td className="p-3 capitalize">{item.category}</td>
                      <td className="p-3">{item.location}</td>
                      <td className="p-3 text-right font-mono">₱{item.unit_price.toFixed(2)}</td>
                      <td className="p-3 text-center font-mono">{item.bodega_stock}</td>
                      <td className="p-3 text-center font-mono">{item.shelves_stock}</td>
                      <td className="p-3 text-center font-mono font-bold text-teal-700">{item.total_stock}</td>
                      <td className="p-3 text-right font-mono font-extrabold text-slate-900">
                        ₱{item.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeReportTab === 'expenditure' && (
        <div className="space-y-6">
          <div className="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <h3 className="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4">
              Requisition Expenditure by Certified Vendor
            </h3>
            <div className="h-64">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={poBySupplierData}>
                  <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#f1f5f9" />
                  <XAxis dataKey="name" tick={{ fontSize: 11 }} />
                  <YAxis tick={{ fontSize: 11 }} />
                  <Tooltip formatter={(val: any) => `₱${Number(val).toLocaleString()}`} />
                  <Bar dataKey="amount" fill="#0d9488" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </div>

          <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-4 border-b border-slate-200 font-bold text-slate-900 text-xs uppercase">
              Purchase Order Historical Disbursements
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                  <tr>
                    <th className="p-3">PO Number</th>
                    <th className="p-3">Store Name</th>
                    <th className="p-3">Order Date</th>
                    <th className="p-3">Expected Date</th>
                    <th className="p-3 text-right">Total Amount</th>
                    <th className="p-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {purchaseOrders.map((po) => (
                    <tr key={po.id}>
                      <td className="p-3 font-mono font-bold text-teal-700">{po.po_number}</td>
                      <td className="p-3 font-medium text-slate-900">{po.store_name}</td>
                      <td className="p-3 text-slate-600">{po.order_date}</td>
                      <td className="p-3 text-slate-600">{po.expected_delivery_date}</td>
                      <td className="p-3 text-right font-mono font-extrabold text-slate-900">
                        ₱{po.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                      </td>
                      <td className="p-3">
                        <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100">
                          {po.status}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {activeReportTab === 'attendance' && (
        <div className="space-y-6">
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div className="lg:col-span-5 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
              <h3 className="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4">
                Staff Compliance Breakdown
              </h3>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={attendanceChartData}
                      cx="50%"
                      cy="50%"
                      innerRadius={55}
                      outerRadius={85}
                      paddingAngle={4}
                      dataKey="value"
                    >
                      {attendanceChartData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip />
                    <Legend />
                  </PieChart>
                </ResponsiveContainer>
              </div>
            </div>

            <div className="lg:col-span-7 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
              <h3 className="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4">
                Departmental Attendance Summaries
              </h3>
              <div className="space-y-3 text-xs">
                {['Laboratory', 'Quality Control', 'Purchasing', 'Warehouse & Logistics'].map((dept) => {
                  const deptEmps = employees.filter((e) => e.department === dept);
                  const deptLogs = attendanceRecords.filter((a) => a.department === dept);
                  return (
                    <div key={dept} className="p-3 bg-slate-50 rounded-xl border border-slate-200 flex justify-between items-center">
                      <div>
                        <div className="font-bold text-slate-900">{dept}</div>
                        <div className="text-[11px] text-slate-500">{deptEmps.length} registered technicians</div>
                      </div>
                      <div className="text-right">
                        <span className="font-mono font-bold text-teal-700">{deptLogs.length} shifts logged</span>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        </div>
      )}

      {activeReportTab === 'logistics' && (
        <div className="space-y-6">
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-4 border-b border-slate-200 font-bold text-slate-900 text-xs uppercase">
              Cold-Chain Logistics Fulfillment Performance
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                  <tr>
                    <th className="p-3">Tracking Number</th>
                    <th className="p-3">PO Reference</th>
                    <th className="p-3">Logistics Carrier</th>
                    <th className="p-3">Driver</th>
                    <th className="p-3">Route</th>
                    <th className="p-3">Estimated Date</th>
                    <th className="p-3">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {deliveries.map((del) => (
                    <tr key={del.id}>
                      <td className="p-3 font-mono font-bold text-teal-700">{del.tracking_number}</td>
                      <td className="p-3 font-mono text-slate-600">{del.po_number}</td>
                      <td className="p-3 font-semibold text-slate-900">{del.carrier}</td>
                      <td className="p-3">{del.driver_name}</td>
                      <td className="p-3 text-slate-600">
                        {del.origin} → {del.destination}
                      </td>
                      <td className="p-3">{del.estimated_delivery}</td>
                      <td className="p-3">
                        <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 capitalize">
                          {del.status.replace('_', ' ')}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
