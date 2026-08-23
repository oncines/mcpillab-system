import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { PurchaseOrder, PurchaseOrderItem, POStatus } from '../../types';
import {
  FileText,
  Plus,
  Search,
  Filter,
  CheckCircle2,
  XCircle,
  Clock,
  Printer,
  Receipt,
  MessageSquare,
  Building2,
  Trash2,
  ChevronDown,
  Eye,
  Send,
  AlertCircle,
} from 'lucide-react';

export const PurchaseOrderView: React.FC = () => {
  const {
    purchaseOrders,
    suppliers,
    currentUser,
    addPurchaseOrder,
    updatePOStatus,
    addPOMessage,
    createInvoice,
    setActiveTab,
    searchQuery,
  } = useApp();

  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [localSearch, setLocalSearch] = useState('');
  const [selectedPO, setSelectedPO] = useState<PurchaseOrder | null>(null);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showPrintModal, setShowPrintModal] = useState(false);
  const [messageInput, setMessageInput] = useState('');

  // Form State for new PO
  const nextPoNumber = `PO-2026-${String(purchaseOrders.length + 1).padStart(3, '0')}`;
  const [newPoNumber, setNewPoNumber] = useState(nextPoNumber);
  const [selectedSupplierId, setSelectedSupplierId] = useState<number>(suppliers[0]?.id || 1);
  const [storeName, setStoreName] = useState('MCPIL Main Pharmaceutical Lab Store');
  const [orderDate, setOrderDate] = useState(new Date().toISOString().split('T')[0]);
  const [expectedDate, setExpectedDate] = useState(
    new Date(Date.now() + 14 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
  );
  const [notes, setNotes] = useState('');
  const [items, setItems] = useState<PurchaseOrderItem[]>([
    {
      id: 'item-1',
      item_name: 'ACEITE DE ALCAMPORADO 100ml',
      description: 'USP Pharmaceutical Grade',
      quantity: 20,
      unit_price: 150.0,
      total_price: 3000.0,
    },
  ]);

  // Calculations
  const calculatedTotal = items.reduce((acc, item) => acc + item.quantity * item.unit_price, 0);

  const handleAddItemRow = () => {
    setItems([
      ...items,
      {
        id: `item-${Date.now()}`,
        item_name: '',
        description: '',
        quantity: 1,
        unit_price: 0,
        total_price: 0,
      },
    ]);
  };

  const handleRemoveItemRow = (id: string) => {
    if (items.length === 1) return;
    setItems(items.filter((item) => item.id !== id));
  };

  const handleItemChange = (id: string, field: keyof PurchaseOrderItem, value: any) => {
    setItems(
      items.map((item) => {
        if (item.id === id) {
          const updated = { ...item, [field]: value };
          if (field === 'quantity' || field === 'unit_price') {
            updated.total_price = Number(updated.quantity || 0) * Number(updated.unit_price || 0);
          }
          return updated;
        }
        return item;
      })
    );
  };

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (items.some((i) => !i.item_name.trim() || i.quantity <= 0)) {
      alert('Please fill in valid item names and quantities.');
      return;
    }

    const created = addPurchaseOrder({
      po_number: newPoNumber,
      supplier_id: Number(selectedSupplierId),
      store_name: storeName,
      order_date: orderDate,
      expected_delivery_date: expectedDate,
      total_amount: calculatedTotal,
      status: 'Pending',
      notes,
      created_by: currentUser.id,
      created_by_name: currentUser.full_name,
      items,
    });

    setShowCreateModal(false);
    setSelectedPO(created);
    // Reset form
    setNewPoNumber(`PO-2026-${String(purchaseOrders.length + 2).padStart(3, '0')}`);
    setNotes('');
  };

  const handleSendMessage = () => {
    if (!messageInput.trim() || !selectedPO) return;
    addPOMessage(selectedPO.id, messageInput.trim());
    // update local modal view
    const updated = purchaseOrders.find((p) => p.id === selectedPO.id);
    if (updated) {
      setSelectedPO({
        ...updated,
        messages: [
          ...updated.messages,
          {
            id: `msg-${Date.now()}`,
            po_id: selectedPO.id,
            user_id: currentUser.id,
            user_name: currentUser.full_name,
            message: messageInput.trim(),
            message_type: currentUser.role === 'admin' ? 'admin' : 'store',
            created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
          },
        ],
      });
    }
    setMessageInput('');
  };

  const handleGenerateInvoice = (po: PurchaseOrder) => {
    const invNumber = `INV-2026-${String(Date.now()).slice(-3)}`;
    const sup = suppliers.find((s) => s.id === po.supplier_id);
    const tax = po.total_amount * 0.12;
    const inv = createInvoice({
      invoice_number: invNumber,
      po_id: po.id,
      po_number: po.po_number,
      supplier_name: sup ? sup.name : po.store_name,
      invoice_date: new Date().toISOString().split('T')[0],
      due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
      amount: po.total_amount,
      tax_amount: tax,
      total_amount: po.total_amount + tax,
      status: 'unpaid',
      notes: `Generated automatically from approved purchase order ${po.po_number}`,
    });

    alert(`Invoice ${inv.invoice_number} created successfully! Redirecting to Invoices...`);
    setActiveTab('invoices');
  };

  // Filter list
  const effectiveSearch = (searchQuery || localSearch).toLowerCase();
  const filteredPOs = purchaseOrders.filter((po) => {
    const matchesStatus = statusFilter === 'all' || po.status.toLowerCase() === statusFilter.toLowerCase();
    const matchesSearch =
      po.po_number.toLowerCase().includes(effectiveSearch) ||
      po.store_name.toLowerCase().includes(effectiveSearch) ||
      po.notes?.toLowerCase().includes(effectiveSearch) ||
      po.items.some((item) => item.item_name.toLowerCase().includes(effectiveSearch));
    return matchesStatus && matchesSearch;
  });

  return (
    <div className="space-y-6">
      {/* Header Bar */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-xl font-bold text-slate-900">Purchase Order Management</h1>
            <span className="text-xs px-2.5 py-0.5 rounded-full bg-teal-50 text-teal-700 font-bold border border-teal-200">
              {purchaseOrders.length} Orders
            </span>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Create, approve, and track pharmaceutical laboratory chemical & equipment requisitions.
          </p>
        </div>

        <button
          onClick={() => {
            setNewPoNumber(`PO-2026-${String(purchaseOrders.length + 1).padStart(3, '0')}`);
            setShowCreateModal(true);
          }}
          className="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-sm shadow-teal-700/20 transition-colors shrink-0"
        >
          <Plus className="w-4 h-4" />
          Create New Purchase Order
        </button>
      </div>

      {/* Filter and Search Controls */}
      <div className="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        {/* Status Pills */}
        <div className="flex flex-wrap items-center gap-1.5">
          {['all', 'Pending', 'Approved', 'Processing', 'Completed', 'Rejected'].map((status) => (
            <button
              key={status}
              onClick={() => setStatusFilter(status)}
              className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-colors ${
                statusFilter === status
                  ? 'bg-slate-900 text-white shadow-sm'
                  : 'bg-slate-100 hover:bg-slate-200 text-slate-600'
              }`}
            >
              {status === 'all' ? 'All Orders' : status}
            </button>
          ))}
        </div>

        {/* Local Search */}
        <div className="relative w-full sm:w-64">
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Filter POs by number or item..."
            value={localSearch}
            onChange={(e) => setLocalSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500"
          />
        </div>
      </div>

      {/* Purchase Orders Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
              <tr>
                <th className="p-4">PO Number</th>
                <th className="p-4">Order Date</th>
                <th className="p-4">Store / Requester</th>
                <th className="p-4">Supplier</th>
                <th className="p-4">Line Items</th>
                <th className="p-4">Total Amount</th>
                <th className="p-4">Status</th>
                <th className="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {filteredPOs.length === 0 ? (
                <tr>
                  <td colSpan={8} className="p-8 text-center text-slate-400">
                    No purchase orders match your current filters.
                  </td>
                </tr>
              ) : (
                filteredPOs.map((po) => {
                  const sup = suppliers.find((s) => s.id === po.supplier_id);
                  return (
                    <tr key={po.id} className="hover:bg-slate-50/80 transition-colors">
                      <td className="p-4 font-mono font-bold text-teal-700">{po.po_number}</td>
                      <td className="p-4 text-slate-600">{po.order_date}</td>
                      <td className="p-4">
                        <div className="font-semibold text-slate-900">{po.store_name}</div>
                        <div className="text-[11px] text-slate-400">By: {po.created_by_name}</div>
                      </td>
                      <td className="p-4 font-medium text-slate-800">{sup ? sup.name : 'Unknown Supplier'}</td>
                      <td className="p-4 text-slate-600">
                        {po.items.length} item{po.items.length !== 1 ? 's' : ''}
                      </td>
                      <td className="p-4 font-extrabold text-slate-900">
                        ₱{po.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                      </td>
                      <td className="p-4">
                        <span
                          className={`inline-flex px-2.5 py-1 rounded-full text-[11px] font-bold ${
                            po.status === 'Approved'
                              ? 'bg-blue-100 text-blue-800'
                              : po.status === 'Completed'
                              ? 'bg-emerald-100 text-emerald-800'
                              : po.status === 'Processing'
                              ? 'bg-purple-100 text-purple-800'
                              : po.status === 'Pending'
                              ? 'bg-amber-100 text-amber-800'
                              : 'bg-rose-100 text-rose-800'
                          }`}
                        >
                          {po.status}
                        </span>
                      </td>
                      <td className="p-4 text-right">
                        <div className="flex items-center justify-end gap-1.5">
                          <button
                            onClick={() => setSelectedPO(po)}
                            className="p-1.5 text-slate-600 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition-colors"
                            title="Inspect Details & Discussion"
                          >
                            <Eye className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => {
                              setSelectedPO(po);
                              setShowPrintModal(true);
                            }}
                            className="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition-colors"
                            title="Print Formal Purchase Order"
                          >
                            <Printer className="w-4 h-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* MODAL: Detailed PO Viewer & Approval & Chat */}
      {selectedPO && !showPrintModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200">
            {/* Modal Header */}
            <div className="p-5 border-b border-slate-200 flex items-center justify-between sticky top-0 bg-white z-10">
              <div>
                <div className="flex items-center gap-2">
                  <span className="font-mono text-base font-extrabold text-teal-700">{selectedPO.po_number}</span>
                  <span
                    className={`px-2.5 py-0.5 rounded-full text-xs font-bold ${
                      selectedPO.status === 'Approved'
                        ? 'bg-blue-100 text-blue-800'
                        : selectedPO.status === 'Completed'
                        ? 'bg-emerald-100 text-emerald-800'
                        : selectedPO.status === 'Processing'
                        ? 'bg-purple-100 text-purple-800'
                        : selectedPO.status === 'Pending'
                        ? 'bg-amber-100 text-amber-800'
                        : 'bg-rose-100 text-rose-800'
                    }`}
                  >
                    {selectedPO.status}
                  </span>
                </div>
                <p className="text-xs text-slate-500 mt-0.5">
                  Ordered on {selectedPO.order_date} • Expected by {selectedPO.expected_delivery_date}
                </p>
              </div>
              <button
                onClick={() => setSelectedPO(null)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100"
              >
                ✕
              </button>
            </div>

            {/* Modal Body */}
            <div className="p-6 space-y-6">
              {/* Top Overview Cards */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
                <div>
                  <span className="text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                    Requisitioning Store / Lab
                  </span>
                  <div className="font-bold text-slate-900 text-sm mt-0.5">{selectedPO.store_name}</div>
                  <div className="text-slate-500 mt-0.5">Created by: {selectedPO.created_by_name}</div>
                  {selectedPO.notes && (
                    <div className="mt-2 text-slate-600 italic bg-white p-2 rounded border border-slate-200">
                      "{selectedPO.notes}"
                    </div>
                  )}
                </div>

                <div>
                  <span className="text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                    Assigned Supplier
                  </span>
                  {(() => {
                    const sup = suppliers.find((s) => s.id === selectedPO.supplier_id);
                    return sup ? (
                      <div className="mt-0.5">
                        <div className="font-bold text-slate-900 text-sm">{sup.name}</div>
                        <div className="text-slate-600">Contact: {sup.contact_person} ({sup.phone})</div>
                        <div className="text-slate-500">{sup.email} • {sup.city}, {sup.country}</div>
                      </div>
                    ) : (
                      <div className="text-slate-500">Supplier ID #{selectedPO.supplier_id}</div>
                    );
                  })()}
                </div>
              </div>

              {/* Status Action Controls for Admin/Manager */}
              <div className="bg-teal-50/60 p-4 rounded-xl border border-teal-100 flex flex-wrap items-center justify-between gap-3">
                <div>
                  <div className="text-xs font-bold text-teal-900">Purchase Order Authorization & Workflow</div>
                  <div className="text-[11px] text-teal-700">Update the operational status of this requisition:</div>
                </div>

                <div className="flex flex-wrap gap-2">
                  {selectedPO.status !== 'Approved' && (
                    <button
                      onClick={() => {
                        updatePOStatus(selectedPO.id, 'Approved', 'Approved by authorized officer.');
                        setSelectedPO({ ...selectedPO, status: 'Approved' });
                      }}
                      className="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg shadow-xs"
                    >
                      Approve PO
                    </button>
                  )}
                  {selectedPO.status !== 'Processing' && (
                    <button
                      onClick={() => {
                        updatePOStatus(selectedPO.id, 'Processing', 'Order acknowledged and in processing.');
                        setSelectedPO({ ...selectedPO, status: 'Processing' });
                      }}
                      className="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs rounded-lg shadow-xs"
                    >
                      Mark Processing
                    </button>
                  )}
                  {selectedPO.status !== 'Completed' && (
                    <button
                      onClick={() => {
                        updatePOStatus(selectedPO.id, 'Completed', 'Shipment fulfilled and inspected.');
                        setSelectedPO({ ...selectedPO, status: 'Completed' });
                      }}
                      className="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg shadow-xs"
                    >
                      Mark Completed
                    </button>
                  )}
                  {selectedPO.status !== 'Rejected' && (
                    <button
                      onClick={() => {
                        const reason = prompt('Reason for PO rejection:');
                        if (reason) {
                          updatePOStatus(selectedPO.id, 'Rejected', reason);
                          setSelectedPO({ ...selectedPO, status: 'Rejected' });
                        }
                      }}
                      className="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-lg shadow-xs"
                    >
                      Reject PO
                    </button>
                  )}
                </div>
              </div>

              {/* Line Items Table */}
              <div>
                <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Line Items Breakdown</h3>
                <div className="border border-slate-200 rounded-xl overflow-hidden">
                  <table className="w-full text-left text-xs">
                    <thead className="bg-slate-100 text-slate-600 font-semibold">
                      <tr>
                        <th className="p-3">Item Description</th>
                        <th className="p-3 text-center">Qty</th>
                        <th className="p-3 text-right">Unit Price</th>
                        <th className="p-3 text-right">Total Price</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {selectedPO.items.map((item, idx) => (
                        <tr key={idx}>
                          <td className="p-3">
                            <div className="font-bold text-slate-900">{item.item_name}</div>
                            {item.description && <div className="text-[11px] text-slate-500">{item.description}</div>}
                          </td>
                          <td className="p-3 text-center font-semibold text-slate-800">{item.quantity}</td>
                          <td className="p-3 text-right text-slate-600">
                            ₱{item.unit_price.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                          </td>
                          <td className="p-3 text-right font-bold text-slate-900">
                            ₱{item.total_price.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                    <tfoot className="bg-slate-50 border-t border-slate-200">
                      <tr>
                        <td colSpan={3} className="p-3 text-right font-bold text-slate-700">
                          Grand Total:
                        </td>
                        <td className="p-3 text-right font-extrabold text-slate-900 text-sm">
                          ₱{selectedPO.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                        </td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>

              {/* Messages & Comments Discussion Thread */}
              <div>
                <div className="flex items-center gap-2 mb-2">
                  <MessageSquare className="w-4 h-4 text-teal-600" />
                  <h3 className="text-xs font-bold text-slate-800 uppercase tracking-wider">
                    Store & Admin Discussion Thread
                  </h3>
                </div>

                <div className="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-3 max-h-48 overflow-y-auto">
                  {selectedPO.messages.length === 0 ? (
                    <p className="text-center text-xs text-slate-400">No discussion messages logged.</p>
                  ) : (
                    selectedPO.messages.map((msg) => (
                      <div
                        key={msg.id}
                        className={`p-3 rounded-xl text-xs ${
                          msg.message_type === 'admin'
                            ? 'bg-purple-50 border border-purple-200 text-purple-900 ml-4'
                            : 'bg-white border border-slate-200 text-slate-800 mr-4'
                        }`}
                      >
                        <div className="flex items-center justify-between mb-1 font-semibold text-[11px]">
                          <span className={msg.message_type === 'admin' ? 'text-purple-700' : 'text-slate-700'}>
                            {msg.user_name} ({msg.message_type === 'admin' ? 'Admin' : 'Store User'})
                          </span>
                          <span className="text-[10px] text-slate-400">{msg.created_at}</span>
                        </div>
                        <p>{msg.message}</p>
                      </div>
                    ))
                  )}
                </div>

                {/* Message input */}
                <div className="flex items-center gap-2 mt-2">
                  <input
                    type="text"
                    placeholder="Type an operational remark or question..."
                    value={messageInput}
                    onChange={(e) => setMessageInput(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleSendMessage()}
                    className="flex-1 text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                  />
                  <button
                    onClick={handleSendMessage}
                    className="px-3 py-2 bg-teal-600 text-white rounded-lg text-xs font-bold hover:bg-teal-700 flex items-center gap-1"
                  >
                    <Send className="w-3.5 h-3.5" />
                    Send
                  </button>
                </div>
              </div>

              {/* Action Buttons */}
              <div className="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-200">
                <div className="flex items-center gap-2">
                  <button
                    onClick={() => setShowPrintModal(true)}
                    className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl flex items-center gap-1.5 transition-colors"
                  >
                    <Printer className="w-4 h-4" />
                    Print Formal Slip
                  </button>
                  {selectedPO.status === 'Approved' && (
                    <button
                      onClick={() => handleGenerateInvoice(selectedPO)}
                      className="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-bold text-xs rounded-xl flex items-center gap-1.5 transition-colors border border-emerald-200"
                    >
                      <Receipt className="w-4 h-4" />
                      Create Invoice from PO
                    </button>
                  )}
                </div>

                <button
                  onClick={() => setSelectedPO(null)}
                  className="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl transition-colors"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* MODAL: Printable Official Purchase Order Document */}
      {selectedPO && showPrintModal && (
        <div className="fixed inset-0 bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4 z-50 overflow-y-auto">
          <div className="bg-white rounded-2xl max-w-4xl w-full p-8 shadow-2xl border border-slate-200 relative my-8 print:m-0 print:border-none print:shadow-none">
            {/* Document Controls (hidden when printed) */}
            <div className="flex items-center justify-between pb-6 mb-6 border-b border-slate-200 no-print">
              <div className="text-xs text-slate-500">Official Purchase Order Slip (Print Ready)</div>
              <div className="flex items-center gap-2">
                <button
                  onClick={() => window.print()}
                  className="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl flex items-center gap-2 shadow"
                >
                  <Printer className="w-4 h-4" />
                  Print Now
                </button>
                <button
                  onClick={() => setShowPrintModal(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl"
                >
                  Back to Details
                </button>
              </div>
            </div>

            {/* Printable Document Sheet */}
            <div className="space-y-6 text-slate-800 font-sans">
              {/* Header Letterhead */}
              <div className="flex items-start justify-between border-b-2 border-slate-800 pb-4">
                <div>
                  <h1 className="text-2xl font-black tracking-tight text-slate-900">MCPIL PHARMACEUTICAL LABORATORY</h1>
                  <p className="text-xs text-slate-600">Research, Quality Control & Formulation Laboratories</p>
                  <p className="text-xs text-slate-500">100 Innovation Park Way, BioTech Sector 4</p>
                  <p className="text-xs text-slate-500">Tel: +1 (555) 019-2830 • contact@mcpillab.com</p>
                </div>
                <div className="text-right">
                  <span className="inline-block px-3 py-1 bg-slate-900 text-white text-xs font-black tracking-widest uppercase rounded">
                    PURCHASE ORDER
                  </span>
                  <div className="mt-2 font-mono text-lg font-black text-teal-700">{selectedPO.po_number}</div>
                  <div className="text-xs text-slate-600">Date: {selectedPO.order_date}</div>
                  <div className="text-xs text-slate-600">Status: {selectedPO.status.toUpperCase()}</div>
                </div>
              </div>

              {/* Vendor & Delivery Addresses */}
              <div className="grid grid-cols-2 gap-8 text-xs">
                <div className="p-4 bg-slate-50 rounded-lg border border-slate-200">
                  <div className="font-bold text-slate-500 uppercase tracking-wider mb-1 text-[10px]">VENDOR / SUPPLIER</div>
                  {(() => {
                    const sup = suppliers.find((s) => s.id === selectedPO.supplier_id);
                    return sup ? (
                      <div>
                        <div className="font-extrabold text-sm text-slate-900">{sup.name}</div>
                        <div>Attn: {sup.contact_person}</div>
                        <div>{sup.address}, {sup.city}, {sup.country}</div>
                        <div>Phone: {sup.phone} • Email: {sup.email}</div>
                      </div>
                    ) : (
                      <div>Supplier ID #{selectedPO.supplier_id}</div>
                    );
                  })()}
                </div>

                <div className="p-4 bg-slate-50 rounded-lg border border-slate-200">
                  <div className="font-bold text-slate-500 uppercase tracking-wider mb-1 text-[10px]">SHIP TO / FACILITY</div>
                  <div className="font-extrabold text-sm text-slate-900">MCPIL Central Laboratory Receiving Dock</div>
                  <div>Attn: Receiving Officer (Carol Davis)</div>
                  <div>100 Innovation Park Way, BioTech Sector 4</div>
                  <div>Expected Delivery Date: {selectedPO.expected_delivery_date}</div>
                </div>
              </div>

              {/* Items Table */}
              <table className="w-full text-left text-xs border border-slate-200">
                <thead className="bg-slate-100 text-slate-700 font-bold border-b border-slate-200">
                  <tr>
                    <th className="p-2 border-r border-slate-200">#</th>
                    <th className="p-2 border-r border-slate-200">Item Name & Specifications</th>
                    <th className="p-2 border-r border-slate-200 text-center">Qty</th>
                    <th className="p-2 border-r border-slate-200 text-right">Unit Price (PHP)</th>
                    <th className="p-2 text-right">Total Price (PHP)</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-200">
                  {selectedPO.items.map((item, index) => (
                    <tr key={index}>
                      <td className="p-2 border-r border-slate-200 text-slate-500">{index + 1}</td>
                      <td className="p-2 border-r border-slate-200">
                        <div className="font-bold">{item.item_name}</div>
                        {item.description && <div className="text-[11px] text-slate-500">{item.description}</div>}
                      </td>
                      <td className="p-2 border-r border-slate-200 text-center font-bold">{item.quantity}</td>
                      <td className="p-2 border-r border-slate-200 text-right font-mono">
                        ₱{item.unit_price.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                      </td>
                      <td className="p-2 text-right font-mono font-bold">
                        ₱{item.total_price.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                      </td>
                    </tr>
                  ))}
                </tbody>
                <tfoot className="bg-slate-50 border-t-2 border-slate-300 font-bold">
                  <tr>
                    <td colSpan={4} className="p-2 text-right">SUBTOTAL:</td>
                    <td className="p-2 text-right font-mono">
                      ₱{selectedPO.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </td>
                  </tr>
                  <tr>
                    <td colSpan={4} className="p-2 text-right">VAT / TAX (Included):</td>
                    <td className="p-2 text-right font-mono">₱0.00</td>
                  </tr>
                  <tr className="text-sm font-black border-t border-slate-300 bg-slate-100">
                    <td colSpan={4} className="p-2 text-right">GRAND TOTAL:</td>
                    <td className="p-2 text-right font-mono text-teal-800">
                      ₱{selectedPO.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </td>
                  </tr>
                </tfoot>
              </table>

              {/* Special Instructions & Signatures */}
              <div className="grid grid-cols-2 gap-8 pt-4 text-xs">
                <div>
                  <div className="font-bold text-slate-700 uppercase tracking-wider mb-1 text-[10px]">
                    NOTES / SPECIAL INSTRUCTIONS:
                  </div>
                  <p className="text-slate-600 bg-slate-50 p-2.5 rounded border border-slate-200">
                    {selectedPO.notes || 'Handle chemical containers with safety protocols. Enclose Certificate of Analysis (CoA) with batch.'}
                  </p>
                </div>

                <div className="grid grid-cols-2 gap-4 text-center">
                  <div>
                    <div className="h-12 border-b border-slate-400 flex items-end justify-center font-script text-slate-700 pb-1 italic">
                      {selectedPO.created_by_name}
                    </div>
                    <div className="text-[10px] font-bold text-slate-500 mt-1 uppercase">PREPARED BY</div>
                  </div>
                  <div>
                    <div className="h-12 border-b border-slate-400 flex items-end justify-center font-script text-slate-700 pb-1 italic">
                      Dr. Arthur Vance
                    </div>
                    <div className="text-[10px] font-bold text-slate-500 mt-1 uppercase">AUTHORIZED OFFICER</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* MODAL: Create New Purchase Order Form */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200">
            <div className="p-5 border-b border-slate-200 flex items-center justify-between sticky top-0 bg-white z-10">
              <div className="flex items-center gap-2">
                <FileText className="w-5 h-5 text-teal-600" />
                <h2 className="font-extrabold text-slate-900 text-base">Create New Purchase Order</h2>
              </div>
              <button
                onClick={() => setShowCreateModal(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleCreateSubmit} className="p-6 space-y-5">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">PO Number</label>
                  <input
                    type="text"
                    value={newPoNumber}
                    onChange={(e) => setNewPoNumber(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono font-bold text-teal-700"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Select Supplier</label>
                  <select
                    value={selectedSupplierId}
                    onChange={(e) => setSelectedSupplierId(Number(e.target.value))}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  >
                    {suppliers.map((s) => (
                      <option key={s.id} value={s.id}>
                        {s.name} ({s.supplier_code})
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Store / Lab Unit</label>
                  <input
                    type="text"
                    value={storeName}
                    onChange={(e) => setStoreName(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div className="grid grid-cols-2 gap-2">
                  <div>
                    <label className="block font-bold text-slate-700 mb-1">Order Date</label>
                    <input
                      type="date"
                      value={orderDate}
                      onChange={(e) => setOrderDate(e.target.value)}
                      required
                      className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                    />
                  </div>
                  <div>
                    <label className="block font-bold text-slate-700 mb-1">Expected Date</label>
                    <input
                      type="date"
                      value={expectedDate}
                      onChange={(e) => setExpectedDate(e.target.value)}
                      required
                      className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                    />
                  </div>
                </div>
              </div>

              {/* Items Section */}
              <div className="space-y-3 pt-2">
                <div className="flex items-center justify-between">
                  <label className="font-bold text-slate-800 text-xs uppercase tracking-wider">
                    Requisition Line Items
                  </label>
                  <button
                    type="button"
                    onClick={handleAddItemRow}
                    className="text-xs font-bold text-teal-600 hover:text-teal-700 flex items-center gap-1"
                  >
                    <Plus className="w-3.5 h-3.5" /> Add Another Item
                  </button>
                </div>

                <div className="space-y-2">
                  {items.map((item, idx) => (
                    <div
                      key={item.id}
                      className="p-3 bg-slate-50 rounded-xl border border-slate-200 grid grid-cols-12 gap-2 items-center text-xs"
                    >
                      <div className="col-span-12 sm:col-span-4">
                        <input
                          type="text"
                          placeholder="Item Name (e.g. Acetone 500ml)"
                          value={item.item_name}
                          onChange={(e) => handleItemChange(item.id, 'item_name', e.target.value)}
                          required
                          className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg"
                        />
                      </div>
                      <div className="col-span-12 sm:col-span-3">
                        <input
                          type="text"
                          placeholder="Description / Grade"
                          value={item.description}
                          onChange={(e) => handleItemChange(item.id, 'description', e.target.value)}
                          className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg"
                        />
                      </div>
                      <div className="col-span-4 sm:col-span-2">
                        <input
                          type="number"
                          min="1"
                          placeholder="Qty"
                          value={item.quantity}
                          onChange={(e) => handleItemChange(item.id, 'quantity', Number(e.target.value))}
                          required
                          className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-center"
                        />
                      </div>
                      <div className="col-span-6 sm:col-span-2">
                        <input
                          type="number"
                          step="0.01"
                          min="0"
                          placeholder="Unit Price"
                          value={item.unit_price}
                          onChange={(e) => handleItemChange(item.id, 'unit_price', Number(e.target.value))}
                          required
                          className="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-right"
                        />
                      </div>
                      <div className="col-span-2 sm:col-span-1 text-right">
                        <button
                          type="button"
                          onClick={() => handleRemoveItemRow(item.id)}
                          disabled={items.length === 1}
                          className="p-1.5 text-rose-500 hover:bg-rose-50 rounded disabled:opacity-30"
                          title="Remove Line"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>

                {/* Total computation */}
                <div className="flex justify-end p-3 bg-teal-50/50 rounded-xl border border-teal-100 text-xs">
                  <div className="text-right">
                    <span className="text-slate-600 font-semibold mr-3">Calculated Total:</span>
                    <span className="font-extrabold text-slate-900 text-sm font-mono">
                      ₱{calculatedTotal.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </span>
                  </div>
                </div>
              </div>

              {/* Notes */}
              <div>
                <label className="block font-bold text-slate-700 text-xs mb-1">Requisition Notes & Justification</label>
                <textarea
                  rows={2}
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
                  placeholder="Specify chemical grade, urgency, or test batch reference..."
                  className="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
                />
              </div>

              {/* Footer */}
              <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow shadow-teal-700/20"
                >
                  Save & Submit Purchase Order
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
