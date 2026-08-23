import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { PurchaseInvoice, InvoiceStatus } from '../../types';
import {
  Receipt,
  Plus,
  Search,
  CheckCircle2,
  AlertCircle,
  Clock,
  Printer,
  ChevronDown,
  Building2,
  FileText,
  DollarSign,
} from 'lucide-react';

export const InvoiceView: React.FC = () => {
  const {
    invoices,
    purchaseOrders,
    suppliers,
    createInvoice,
    updateInvoiceStatus,
    currentUser,
    searchQuery,
  } = useApp();

  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [localSearch, setLocalSearch] = useState('');
  const [selectedInvoice, setSelectedInvoice] = useState<PurchaseInvoice | null>(null);
  const [showPrintModal, setShowPrintModal] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);

  // New invoice form state
  const nextInvNumber = `INV-2026-${String(invoices.length + 1).padStart(3, '0')}`;
  const [invNumber, setInvNumber] = useState(nextInvNumber);
  const [selectedPoId, setSelectedPoId] = useState<number>(purchaseOrders[0]?.id || 1);
  const [invDate, setInvDate] = useState(new Date().toISOString().split('T')[0]);
  const [dueDate, setDueDate] = useState(
    new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
  );
  const [amount, setAmount] = useState<number>(10000);
  const [taxRate, setTaxRate] = useState<number>(12); // 12% VAT
  const [notes, setNotes] = useState('');

  const taxAmount = (amount * taxRate) / 100;
  const grandTotal = amount + taxAmount;

  const handlePoSelectionChange = (poId: number) => {
    setSelectedPoId(poId);
    const po = purchaseOrders.find((p) => p.id === poId);
    if (po) {
      setAmount(po.total_amount);
    }
  };

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const po = purchaseOrders.find((p) => p.id === Number(selectedPoId));
    const sup = po ? suppliers.find((s) => s.id === po.supplier_id) : null;

    const newInv = createInvoice({
      invoice_number: invNumber,
      po_id: po ? po.id : 0,
      po_number: po ? po.po_number : 'DIRECT-REQUISITION',
      supplier_name: sup ? sup.name : po ? po.store_name : 'Direct Vendor',
      invoice_date: invDate,
      due_date: dueDate,
      amount,
      tax_amount: taxAmount,
      total_amount: grandTotal,
      status: 'unpaid',
      notes,
    });

    setShowCreateModal(false);
    setSelectedInvoice(newInv);
    setInvNumber(`INV-2026-${String(invoices.length + 2).padStart(3, '0')}`);
  };

  const effectiveSearch = (searchQuery || localSearch).toLowerCase();
  const filteredInvoices = invoices.filter((inv) => {
    const matchesStatus = statusFilter === 'all' || inv.status === statusFilter;
    const matchesSearch =
      inv.invoice_number.toLowerCase().includes(effectiveSearch) ||
      inv.po_number.toLowerCase().includes(effectiveSearch) ||
      inv.supplier_name.toLowerCase().includes(effectiveSearch) ||
      inv.notes?.toLowerCase().includes(effectiveSearch);
    return matchesStatus && matchesSearch;
  });

  const totalInvoiced = invoices.reduce((sum, i) => sum + i.total_amount, 0);
  const totalPaid = invoices.filter((i) => i.status === 'paid').reduce((sum, i) => sum + i.total_amount, 0);
  const totalUnpaid = invoices.filter((i) => i.status === 'unpaid').reduce((sum, i) => sum + i.total_amount, 0);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-xl font-bold text-slate-900">Purchase Invoice System</h1>
            <span className="text-xs px-2.5 py-0.5 rounded-full bg-teal-50 text-teal-700 font-bold border border-teal-200">
              {invoices.length} Invoices
            </span>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Track financial disbursements, VAT computations, payment milestones, and supplier billing.
          </p>
        </div>

        <button
          onClick={() => {
            setInvNumber(`INV-2026-${String(invoices.length + 1).padStart(3, '0')}`);
            if (purchaseOrders.length > 0) {
              setAmount(purchaseOrders[0].total_amount);
            }
            setShowCreateModal(true);
          }}
          className="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-sm shadow-teal-700/20 transition-colors shrink-0"
        >
          <Plus className="w-4 h-4" />
          Generate New Invoice
        </button>
      </div>

      {/* Financial Summary KPIs */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <span className="text-slate-400 font-bold uppercase tracking-wider text-[10px]">Total Billed</span>
          <div className="text-xl font-extrabold text-slate-900 mt-1 font-mono">
            ₱{totalInvoiced.toLocaleString(undefined, { minimumFractionDigits: 2 })}
          </div>
          <div className="text-[11px] text-slate-500 mt-0.5">{invoices.length} total supplier invoices</div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <span className="text-emerald-600 font-bold uppercase tracking-wider text-[10px]">Settled / Paid</span>
          <div className="text-xl font-extrabold text-emerald-700 mt-1 font-mono">
            ₱{totalPaid.toLocaleString(undefined, { minimumFractionDigits: 2 })}
          </div>
          <div className="text-[11px] text-emerald-600 mt-0.5 font-semibold">
            {invoices.filter((i) => i.status === 'paid').length} settled accounts
          </div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <span className="text-amber-600 font-bold uppercase tracking-wider text-[10px]">Pending Settlement</span>
          <div className="text-xl font-extrabold text-amber-700 mt-1 font-mono">
            ₱{totalUnpaid.toLocaleString(undefined, { minimumFractionDigits: 2 })}
          </div>
          <div className="text-[11px] text-amber-600 mt-0.5 font-semibold">
            {invoices.filter((i) => i.status === 'unpaid').length} awaiting payment release
          </div>
        </div>
      </div>

      {/* Filter and Search */}
      <div className="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div className="flex flex-wrap items-center gap-1.5">
          {['all', 'unpaid', 'partially_paid', 'paid'].map((status) => (
            <button
              key={status}
              onClick={() => setStatusFilter(status)}
              className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-colors ${
                statusFilter === status
                  ? 'bg-slate-900 text-white shadow-sm'
                  : 'bg-slate-100 hover:bg-slate-200 text-slate-600 capitalize'
              }`}
            >
              {status === 'all' ? 'All Invoices' : status.replace('_', ' ')}
            </button>
          ))}
        </div>

        <div className="relative w-full sm:w-64">
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search invoice number, PO..."
            value={localSearch}
            onChange={(e) => setLocalSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
          />
        </div>
      </div>

      {/* Invoice List Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
              <tr>
                <th className="p-4">Invoice #</th>
                <th className="p-4">PO Reference</th>
                <th className="p-4">Supplier / Vendor</th>
                <th className="p-4">Invoice Date</th>
                <th className="p-4">Due Date</th>
                <th className="p-4">Base Amount</th>
                <th className="p-4">VAT (12%)</th>
                <th className="p-4">Grand Total</th>
                <th className="p-4">Status</th>
                <th className="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {filteredInvoices.length === 0 ? (
                <tr>
                  <td colSpan={10} className="p-8 text-center text-slate-400">
                    No invoices match the selected filter.
                  </td>
                </tr>
              ) : (
                filteredInvoices.map((inv) => (
                  <tr key={inv.id} className="hover:bg-slate-50/80 transition-colors">
                    <td className="p-4 font-mono font-bold text-teal-700">{inv.invoice_number}</td>
                    <td className="p-4 font-mono text-slate-600">{inv.po_number}</td>
                    <td className="p-4 font-semibold text-slate-900">{inv.supplier_name}</td>
                    <td className="p-4 text-slate-500">{inv.invoice_date}</td>
                    <td className="p-4 text-slate-500">{inv.due_date}</td>
                    <td className="p-4 font-mono text-slate-700">
                      ₱{inv.amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </td>
                    <td className="p-4 font-mono text-slate-500">
                      ₱{inv.tax_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </td>
                    <td className="p-4 font-mono font-extrabold text-slate-900">
                      ₱{inv.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </td>
                    <td className="p-4">
                      <span
                        className={`inline-flex px-2.5 py-1 rounded-full text-[11px] font-bold capitalize ${
                          inv.status === 'paid'
                            ? 'bg-emerald-100 text-emerald-800'
                            : inv.status === 'partially_paid'
                            ? 'bg-amber-100 text-amber-800'
                            : 'bg-rose-100 text-rose-800'
                        }`}
                      >
                        {inv.status.replace('_', ' ')}
                      </span>
                    </td>
                    <td className="p-4 text-right">
                      <div className="flex items-center justify-end gap-1.5">
                        <button
                          onClick={() => {
                            setSelectedInvoice(inv);
                            setShowPrintModal(true);
                          }}
                          className="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                          title="Print Formal Invoice Slip"
                        >
                          <Printer className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* MODAL: Printable Invoice Slip */}
      {selectedInvoice && showPrintModal && (
        <div className="fixed inset-0 bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4 z-50 overflow-y-auto">
          <div className="bg-white rounded-2xl max-w-3xl w-full p-8 shadow-2xl border border-slate-200 relative my-8 print:m-0 print:border-none print:shadow-none">
            {/* Header controls (no-print) */}
            <div className="flex items-center justify-between pb-6 mb-6 border-b border-slate-200 no-print">
              <div className="flex items-center gap-2">
                <span className="text-xs text-slate-500">Official Purchase Invoice Voucher</span>
                {/* Status Switcher in Modal */}
                <select
                  value={selectedInvoice.status}
                  onChange={(e) => {
                    updateInvoiceStatus(selectedInvoice.id, e.target.value as InvoiceStatus);
                    setSelectedInvoice({ ...selectedInvoice, status: e.target.value as InvoiceStatus });
                  }}
                  className="text-xs font-bold px-2.5 py-1 rounded bg-slate-100 border border-slate-300"
                >
                  <option value="unpaid">Status: Unpaid</option>
                  <option value="partially_paid">Status: Partially Paid</option>
                  <option value="paid">Status: Paid (Settled)</option>
                </select>
              </div>

              <div className="flex items-center gap-2">
                <button
                  onClick={() => window.print()}
                  className="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl flex items-center gap-2 shadow"
                >
                  <Printer className="w-4 h-4" />
                  Print Voucher
                </button>
                <button
                  onClick={() => setShowPrintModal(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl"
                >
                  Close
                </button>
              </div>
            </div>

            {/* Printable Voucher Sheet */}
            <div className="space-y-6 text-slate-800">
              <div className="flex items-start justify-between border-b-2 border-slate-800 pb-4">
                <div>
                  <h1 className="text-2xl font-black tracking-tight text-slate-900">MCPIL PHARMACEUTICAL LABORATORY</h1>
                  <p className="text-xs text-slate-600">Accounts Payable & Supplier Disbursements</p>
                  <p className="text-xs text-slate-500">100 Innovation Park Way, BioTech Sector 4</p>
                </div>
                <div className="text-right">
                  <span className="inline-block px-3 py-1 bg-slate-900 text-white text-xs font-black tracking-widest uppercase rounded">
                    PURCHASE INVOICE
                  </span>
                  <div className="mt-2 font-mono text-lg font-black text-teal-700">{selectedInvoice.invoice_number}</div>
                  <div className="text-xs text-slate-600">PO Ref: {selectedInvoice.po_number}</div>
                  <div className="text-xs text-slate-600">Date: {selectedInvoice.invoice_date}</div>
                  <div className="text-xs text-slate-600 font-bold">Due: {selectedInvoice.due_date}</div>
                </div>
              </div>

              <div className="p-4 bg-slate-50 rounded-lg border border-slate-200 text-xs">
                <div className="font-bold text-slate-500 uppercase tracking-wider mb-1 text-[10px]">PAYEE / SUPPLIER</div>
                <div className="font-extrabold text-sm text-slate-900">{selectedInvoice.supplier_name}</div>
                <div className="text-slate-500 mt-1">Payment Status: <span className="uppercase font-bold text-teal-700">{selectedInvoice.status.replace('_', ' ')}</span></div>
              </div>

              <table className="w-full text-left text-xs border border-slate-200">
                <thead className="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                  <tr>
                    <th className="p-3 border-r border-slate-200">Description</th>
                    <th className="p-3 border-r border-slate-200">Ref Code</th>
                    <th className="p-3 text-right">Amount (PHP)</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-200">
                  <tr>
                    <td className="p-3 border-r border-slate-200">
                      Laboratory Supplies & Chemical Reagents Dispatched under PO #{selectedInvoice.po_number}
                    </td>
                    <td className="p-3 border-r border-slate-200 font-mono">{selectedInvoice.po_number}</td>
                    <td className="p-3 text-right font-mono font-semibold">
                      ₱{selectedInvoice.amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </td>
                  </tr>
                </tbody>
                <tfoot className="bg-slate-50 border-t-2 border-slate-300 font-bold">
                  <tr>
                    <td colSpan={2} className="p-2 text-right">NET AMOUNT:</td>
                    <td className="p-2 text-right font-mono">
                      ₱{selectedInvoice.amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </td>
                  </tr>
                  <tr>
                    <td colSpan={2} className="p-2 text-right">VALUE ADDED TAX (12%):</td>
                    <td className="p-2 text-right font-mono">
                      ₱{selectedInvoice.tax_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </td>
                  </tr>
                  <tr className="text-sm font-black border-t border-slate-300 bg-slate-100">
                    <td colSpan={2} className="p-2 text-right">TOTAL DISBURSEMENT:</td>
                    <td className="p-2 text-right font-mono text-teal-800">
                      ₱{selectedInvoice.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </td>
                  </tr>
                </tfoot>
              </table>

              {selectedInvoice.notes && (
                <div className="p-3 bg-slate-50 rounded border border-slate-200 text-xs">
                  <span className="font-bold text-slate-700">Remarks: </span>
                  <span className="text-slate-600">{selectedInvoice.notes}</span>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {/* MODAL: Create New Invoice Form */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
            <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
              <div className="flex items-center gap-2">
                <Receipt className="w-5 h-5 text-teal-600" />
                <h2 className="font-bold text-slate-900 text-base">Generate Purchase Invoice</h2>
              </div>
              <button
                onClick={() => setShowCreateModal(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleCreateSubmit} className="space-y-4 text-xs">
              <div>
                <label className="block font-bold text-slate-700 mb-1">Invoice Number</label>
                <input
                  type="text"
                  value={invNumber}
                  onChange={(e) => setInvNumber(e.target.value)}
                  required
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono font-bold text-teal-700"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Link to Approved Purchase Order</label>
                <select
                  value={selectedPoId}
                  onChange={(e) => handlePoSelectionChange(Number(e.target.value))}
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                >
                  {purchaseOrders.map((po) => (
                    <option key={po.id} value={po.id}>
                      {po.po_number} - {po.store_name} (₱{po.total_amount.toLocaleString()}) [{po.status}]
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Invoice Date</label>
                  <input
                    type="date"
                    value={invDate}
                    onChange={(e) => setInvDate(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Due Date</label>
                  <input
                    type="date"
                    value={dueDate}
                    onChange={(e) => setDueDate(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Base Amount (₱)</label>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    value={amount}
                    onChange={(e) => setAmount(Number(e.target.value))}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono font-bold"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">VAT Rate (%)</label>
                  <input
                    type="number"
                    value={taxRate}
                    onChange={(e) => setTaxRate(Number(e.target.value))}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono"
                  />
                </div>
              </div>

              {/* Total preview */}
              <div className="p-3 bg-teal-50 rounded-xl border border-teal-100 flex items-center justify-between font-mono">
                <div>
                  <div className="text-[10px] text-teal-700">VAT Amount: ₱{taxAmount.toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
                  <div className="text-xs font-bold text-teal-900">Total Due</div>
                </div>
                <div className="text-base font-black text-teal-800">
                  ₱{grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                </div>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Payment Reference / Notes</label>
                <textarea
                  rows={2}
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  placeholder="e.g., Wire transfer account instructions, batch release notes..."
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                />
              </div>

              <div className="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow"
                >
                  Create Invoice
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
