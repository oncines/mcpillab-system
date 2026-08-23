import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { Supplier } from '../../types';
import {
  Building2,
  Plus,
  Search,
  Phone,
  Mail,
  MapPin,
  FileText,
  DollarSign,
  Star,
  ExternalLink,
  Edit2,
  Trash2,
  Receipt,
} from 'lucide-react';

export const SupplierView: React.FC = () => {
  const {
    suppliers,
    purchaseOrders,
    invoices,
    addSupplier,
    searchQuery,
  } = useApp();

  const [localSearch, setLocalSearch] = useState('');
  const [showAddModal, setShowAddModal] = useState(false);
  const [selectedSupplier, setSelectedSupplier] = useState<Supplier | null>(null);

  // Form state
  const [name, setName] = useState('');
  const [code, setCode] = useState(`SUP-00${suppliers.length + 1}`);
  const [contactPerson, setContactPerson] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('+1-555-');
  const [address, setAddress] = useState('');
  const [city, setCity] = useState('Metro Manila');
  const [country, setCountry] = useState('Philippines');
  const [paymentTerms, setPaymentTerms] = useState('Net 30');
  const [status, setStatus] = useState<'active' | 'inactive'>('active');

  const handleAddSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim()) return;

    addSupplier({
      supplier_code: code,
      name: name.trim(),
      contact_person: contactPerson.trim(),
      email: email.trim(),
      phone: phone.trim(),
      address: address.trim(),
      city,
      country,
      payment_terms: paymentTerms,
      status,
    });

    setShowAddModal(false);
    setName('');
    setContactPerson('');
    setEmail('');
  };

  const effectiveSearch = (searchQuery || localSearch).toLowerCase();
  const filteredSuppliers = suppliers.filter((s) => {
    return (
      s.name.toLowerCase().includes(effectiveSearch) ||
      s.supplier_code.toLowerCase().includes(effectiveSearch) ||
      s.contact_person.toLowerCase().includes(effectiveSearch) ||
      s.city.toLowerCase().includes(effectiveSearch)
    );
  });

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-xl font-bold text-slate-900">Pharmaceutical Supplier Network</h1>
            <span className="text-xs px-2.5 py-0.5 rounded-full bg-teal-50 text-teal-700 font-bold border border-teal-200">
              {suppliers.length} Certified Vendors
            </span>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Approved chemical manufacturers, reagent suppliers, and laboratory equipment vendors.
          </p>
        </div>

        <button
          onClick={() => {
            setCode(`SUP-00${suppliers.length + 1}`);
            setShowAddModal(true);
          }}
          className="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-sm shadow-teal-700/20 transition-colors shrink-0"
        >
          <Plus className="w-4 h-4" />
          Onboard New Supplier
        </button>
      </div>

      {/* Search */}
      <div className="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div className="text-xs text-slate-500 font-semibold">
          Active vendor partnerships: <span className="text-teal-700 font-bold">{suppliers.filter((s) => s.status === 'active').length}</span>
        </div>

        <div className="relative w-full sm:w-72">
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search vendor name, code, city..."
            value={localSearch}
            onChange={(e) => setLocalSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
          />
        </div>
      </div>

      {/* Supplier Grid Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
        {filteredSuppliers.map((sup) => {
          const supPOs = purchaseOrders.filter((po) => po.supplier_id === sup.id);
          const totalSpent = supPOs.reduce((sum, po) => sum + po.total_amount, 0);

          return (
            <div
              key={sup.id}
              className="bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-all p-5 flex flex-col justify-between"
            >
              <div>
                <div className="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                  <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded-xl bg-teal-50 text-teal-700 font-black text-xl flex items-center justify-center border border-teal-200">
                      <Building2 className="w-6 h-6" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <h3 className="font-bold text-slate-900 text-sm">{sup.name}</h3>
                        <span className="font-mono text-[10px] px-2 py-0.5 rounded bg-slate-100 text-slate-600 font-bold">
                          {sup.supplier_code}
                        </span>
                      </div>
                      <p className="text-xs text-slate-500 mt-0.5">Payment Terms: {sup.payment_terms}</p>
                    </div>
                  </div>

                  <span
                    className={`px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                      sup.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'
                    }`}
                  >
                    {sup.status}
                  </span>
                </div>

                <div className="mt-4 grid grid-cols-2 gap-3 text-xs text-slate-600">
                  <div className="space-y-1.5">
                    <div className="flex items-center gap-1.5 text-slate-700 font-semibold">
                      <Mail className="w-3.5 h-3.5 text-slate-400" />
                      <span className="truncate">{sup.email}</span>
                    </div>
                    <div className="flex items-center gap-1.5">
                      <Phone className="w-3.5 h-3.5 text-slate-400" />
                      <span>{sup.phone}</span>
                    </div>
                    <div className="text-[11px] text-slate-500">Contact: {sup.contact_person}</div>
                  </div>

                  <div className="space-y-1.5">
                    <div className="flex items-start gap-1.5">
                      <MapPin className="w-3.5 h-3.5 text-slate-400 shrink-0 mt-0.5" />
                      <span className="text-[11px] text-slate-600 leading-tight">
                        {sup.address}, {sup.city}, {sup.country}
                      </span>
                    </div>
                  </div>
                </div>

                {/* Performance Summary Pill */}
                <div className="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex items-center justify-between text-xs font-mono">
                  <div>
                    <div className="text-[10px] text-slate-400 uppercase font-semibold">Requisitions</div>
                    <div className="font-extrabold text-slate-900">{supPOs.length} Purchase Orders</div>
                  </div>
                  <div className="text-right">
                    <div className="text-[10px] text-slate-400 uppercase font-semibold">Total Sourced</div>
                    <div className="font-extrabold text-teal-800">
                      ₱{totalSpent.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                    </div>
                  </div>
                </div>
              </div>

              <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-end">
                <button
                  onClick={() => setSelectedSupplier(sup)}
                  className="text-xs font-bold text-teal-600 hover:text-teal-700 flex items-center gap-1"
                >
                  Inspect Vendor History <ExternalLink className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          );
        })}
      </div>

      {/* MODAL: Supplier Details & Associated Orders */}
      {selectedSupplier && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-2xl w-full max-h-[85vh] overflow-y-auto p-6 shadow-2xl border border-slate-200">
            <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
              <div>
                <h2 className="font-bold text-slate-900 text-base">{selectedSupplier.name}</h2>
                <p className="text-xs text-slate-500 font-mono">{selectedSupplier.supplier_code}</p>
              </div>
              <button
                onClick={() => setSelectedSupplier(null)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100"
              >
                ✕
              </button>
            </div>

            <div className="space-y-5 text-xs">
              <div className="grid grid-cols-2 gap-3 bg-slate-50 p-4 rounded-xl border border-slate-200">
                <div>
                  <span className="text-[10px] text-slate-400 uppercase font-bold">Contact Person</span>
                  <div className="font-bold text-slate-800">{selectedSupplier.contact_person}</div>
                  <div className="text-slate-500">{selectedSupplier.email}</div>
                  <div className="text-slate-500">{selectedSupplier.phone}</div>
                </div>
                <div>
                  <span className="text-[10px] text-slate-400 uppercase font-bold">Location & Billing</span>
                  <div className="text-slate-800">{selectedSupplier.address}</div>
                  <div className="text-slate-800">{selectedSupplier.city}, {selectedSupplier.country}</div>
                  <div className="text-teal-700 font-bold mt-1">Payment: {selectedSupplier.payment_terms}</div>
                </div>
              </div>

              <div>
                <h3 className="font-bold text-slate-800 uppercase tracking-wider text-xs mb-2">
                  Associated Purchase Orders
                </h3>
                {(() => {
                  const supPOs = purchaseOrders.filter((po) => po.supplier_id === selectedSupplier.id);
                  return (
                    <div className="border border-slate-200 rounded-xl overflow-hidden">
                      <table className="w-full text-left text-xs">
                        <thead className="bg-slate-100 text-slate-600 font-semibold">
                          <tr>
                            <th className="p-3">PO Number</th>
                            <th className="p-3">Date</th>
                            <th className="p-3">Store Name</th>
                            <th className="p-3 text-right">Total Amount</th>
                            <th className="p-3">Status</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                          {supPOs.length === 0 ? (
                            <tr>
                              <td colSpan={5} className="p-4 text-center text-slate-400">
                                No purchase orders logged for this supplier.
                              </td>
                            </tr>
                          ) : (
                            supPOs.map((po) => (
                              <tr key={po.id}>
                                <td className="p-3 font-mono font-bold text-teal-700">{po.po_number}</td>
                                <td className="p-3 text-slate-600">{po.order_date}</td>
                                <td className="p-3 font-semibold text-slate-800">{po.store_name}</td>
                                <td className="p-3 text-right font-mono font-bold text-slate-900">
                                  ₱{po.total_amount.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                                </td>
                                <td className="p-3">
                                  <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100">
                                    {po.status}
                                  </span>
                                </td>
                              </tr>
                            ))
                          )}
                        </tbody>
                      </table>
                    </div>
                  );
                })()}
              </div>

              <div className="flex justify-end pt-3 border-t border-slate-200">
                <button
                  onClick={() => setSelectedSupplier(null)}
                  className="px-4 py-2 bg-slate-800 text-white font-bold text-xs rounded-xl"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* MODAL: Onboard Supplier Form */}
      {showAddModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
            <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
              <div className="flex items-center gap-2">
                <Building2 className="w-5 h-5 text-teal-600" />
                <h2 className="font-bold text-slate-900 text-base">Onboard Approved Supplier</h2>
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
                  <label className="block font-bold text-slate-700 mb-1">Company / Supplier Name</label>
                  <input
                    type="text"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    required
                    placeholder="e.g. Apex Medical & Reagent Supplies Inc."
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-bold"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Supplier Code</label>
                  <input
                    type="text"
                    value={code}
                    onChange={(e) => setCode(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono font-bold"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Payment Terms</label>
                  <select
                    value={paymentTerms}
                    onChange={(e) => setPaymentTerms(e.target.value)}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  >
                    <option value="Net 15">Net 15 Days</option>
                    <option value="Net 30">Net 30 Days</option>
                    <option value="Net 60">Net 60 Days</option>
                    <option value="Cash on Delivery">Cash on Delivery (COD)</option>
                  </select>
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Contact Person</label>
                  <input
                    type="text"
                    value={contactPerson}
                    onChange={(e) => setContactPerson(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Phone Number</label>
                  <input
                    type="text"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div className="col-span-2">
                  <label className="block font-bold text-slate-700 mb-1">Email Address</label>
                  <input
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div className="col-span-2">
                  <label className="block font-bold text-slate-700 mb-1">Office Address</label>
                  <input
                    type="text"
                    value={address}
                    onChange={(e) => setAddress(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">City / Province</label>
                  <input
                    type="text"
                    value={city}
                    onChange={(e) => setCity(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Country</label>
                  <input
                    type="text"
                    value={country}
                    onChange={(e) => setCountry(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
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
                  Save Supplier
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
