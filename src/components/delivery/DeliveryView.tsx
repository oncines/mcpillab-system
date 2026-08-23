import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { Delivery, DeliveryStatus } from '../../types';
import {
  Truck,
  Plus,
  Search,
  CheckCircle2,
  Clock,
  MapPin,
  FileText,
  Mail,
  User,
  ArrowRight,
  Package,
  Calendar,
  AlertCircle,
  Eye,
} from 'lucide-react';

export const DeliveryView: React.FC = () => {
  const {
    deliveries,
    purchaseOrders,
    createDelivery,
    updateDeliveryStatus,
    searchQuery,
  } = useApp();

  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [localSearch, setLocalSearch] = useState('');
  const [selectedDelivery, setSelectedDelivery] = useState<Delivery | null>(null);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [emailNotificationToast, setEmailNotificationToast] = useState<string | null>(null);

  // New Delivery Form state
  const nextTrack = `TRK-${Math.floor(10000 + Math.random() * 90000)}`;
  const [trackingNumber, setTrackingNumber] = useState(nextTrack);
  const [selectedPoId, setSelectedPoId] = useState<number>(purchaseOrders[0]?.id || 1);
  const [carrier, setCarrier] = useState('MCPIL Cold-Chain Logistics');
  const [driverName, setDriverName] = useState('Marco Diaz');
  const [driverContact, setDriverContact] = useState('+1-555-0982');
  const [origin, setOrigin] = useState('Central Warehouse Hub B');
  const [destination, setDestination] = useState('MCPIL Pharmaceutical Lab Cleanroom #4');
  const [estDate, setEstDate] = useState(
    new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
  );
  const [itemsSummary, setItemsSummary] = useState('20x USP Chemicals + Formulation Kits');
  const [notes, setNotes] = useState('Temperature controlled cold-chain 2-8°C required.');

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const po = purchaseOrders.find((p) => p.id === Number(selectedPoId));

    const newDel = createDelivery({
      po_id: po ? po.id : 0,
      po_number: po ? po.po_number : 'DIRECT-DISPATCH',
      tracking_number: trackingNumber,
      carrier,
      driver_name: driverName,
      driver_contact: driverContact,
      origin,
      destination,
      status: 'in_transit',
      estimated_delivery: estDate,
      items_summary: itemsSummary,
      notes,
    });

    setShowCreateModal(false);
    setSelectedDelivery(newDel);
    setTrackingNumber(`TRK-${Math.floor(10000 + Math.random() * 90000)}`);

    // Trigger email notification simulation
    setEmailNotificationToast(`Automated Dispatch Email sent to Logistics Coordinator for ${newDel.tracking_number}!`);
    setTimeout(() => setEmailNotificationToast(null), 4000);
  };

  const handleStatusUpdate = (status: DeliveryStatus) => {
    if (!selectedDelivery) return;
    updateDeliveryStatus(selectedDelivery.id, status);
    setSelectedDelivery({
      ...selectedDelivery,
      status,
      actual_delivery: status === 'delivered' ? new Date().toISOString().split('T')[0] : selectedDelivery.actual_delivery,
    });

    setEmailNotificationToast(`Email alert sent: Shipment ${selectedDelivery.tracking_number} marked as ${status.toUpperCase()}!`);
    setTimeout(() => setEmailNotificationToast(null), 4000);
  };

  const effectiveSearch = (searchQuery || localSearch).toLowerCase();
  const filteredDeliveries = deliveries.filter((del) => {
    const matchesStatus = statusFilter === 'all' || del.status === statusFilter;
    const matchesSearch =
      del.tracking_number.toLowerCase().includes(effectiveSearch) ||
      del.po_number.toLowerCase().includes(effectiveSearch) ||
      del.carrier.toLowerCase().includes(effectiveSearch) ||
      del.driver_name.toLowerCase().includes(effectiveSearch) ||
      del.destination.toLowerCase().includes(effectiveSearch);
    return matchesStatus && matchesSearch;
  });

  const inTransitCount = deliveries.filter((d) => d.status === 'in_transit').length;
  const deliveredCount = deliveries.filter((d) => d.status === 'delivered').length;
  const pendingCount = deliveries.filter((d) => d.status === 'pending').length;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-xl font-bold text-slate-900">Laboratory Logistics & Delivery Tracking</h1>
            <span className="text-xs px-2.5 py-0.5 rounded-full bg-teal-50 text-teal-700 font-bold border border-teal-200">
              {deliveries.length} Shipments
            </span>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Real-time cold-chain vehicle tracking, PO fulfillment shipments, and email notification dispatches.
          </p>
        </div>

        <button
          onClick={() => setShowCreateModal(true)}
          className="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-sm shadow-teal-700/20 transition-colors shrink-0"
        >
          <Plus className="w-4 h-4" />
          Dispatch New Delivery
        </button>
      </div>

      {emailNotificationToast && (
        <div className="p-4 bg-blue-50 border border-blue-200 rounded-xl text-blue-900 text-xs font-bold flex items-center gap-2 animate-in fade-in">
          <Mail className="w-4 h-4 text-blue-600 shrink-0" />
          <span>{emailNotificationToast}</span>
        </div>
      )}

      {/* KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <span className="text-blue-600 font-bold uppercase tracking-wider text-[10px]">Active In-Transit</span>
          <div className="text-xl font-extrabold text-blue-700 mt-1 font-mono">{inTransitCount} Shipments</div>
          <div className="text-[11px] text-slate-500 mt-0.5">En route to laboratory cleanrooms & docks</div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <span className="text-emerald-600 font-bold uppercase tracking-wider text-[10px]">Successfully Received</span>
          <div className="text-xl font-extrabold text-emerald-700 mt-1 font-mono">{deliveredCount} Fulfilled</div>
          <div className="text-[11px] text-emerald-600 mt-0.5 font-semibold">Inspected and accepted by QC</div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <span className="text-amber-600 font-bold uppercase tracking-wider text-[10px]">Pending Dispatch</span>
          <div className="text-xl font-extrabold text-amber-700 mt-1 font-mono">{pendingCount} Awaiting Driver</div>
          <div className="text-[11px] text-slate-500 mt-0.5">Prepared in central warehouse</div>
        </div>
      </div>

      {/* Filter and Search */}
      <div className="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div className="flex flex-wrap items-center gap-1.5">
          {['all', 'in_transit', 'delivered', 'pending', 'cancelled'].map((status) => (
            <button
              key={status}
              onClick={() => setStatusFilter(status)}
              className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-colors ${
                statusFilter === status
                  ? 'bg-slate-900 text-white shadow-sm'
                  : 'bg-slate-100 hover:bg-slate-200 text-slate-600 capitalize'
              }`}
            >
              {status === 'all' ? 'All Deliveries' : status.replace('_', ' ')}
            </button>
          ))}
        </div>

        <div className="relative w-full sm:w-64">
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search tracking #, PO, driver..."
            value={localSearch}
            onChange={(e) => setLocalSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
          />
        </div>
      </div>

      {/* Deliveries List */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
              <tr>
                <th className="p-4">Tracking #</th>
                <th className="p-4">PO Link</th>
                <th className="p-4">Logistics Carrier</th>
                <th className="p-4">Driver & Contact</th>
                <th className="p-4">Route (Origin → Destination)</th>
                <th className="p-4">Est. Delivery</th>
                <th className="p-4">Status</th>
                <th className="p-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {filteredDeliveries.length === 0 ? (
                <tr>
                  <td colSpan={8} className="p-8 text-center text-slate-400">
                    No shipments found matching the selected criteria.
                  </td>
                </tr>
              ) : (
                filteredDeliveries.map((del) => (
                  <tr key={del.id} className="hover:bg-slate-50/80 transition-colors">
                    <td className="p-4 font-mono font-bold text-teal-700">{del.tracking_number}</td>
                    <td className="p-4 font-mono text-slate-600">{del.po_number}</td>
                    <td className="p-4 font-medium text-slate-900">{del.carrier}</td>
                    <td className="p-4">
                      <div className="font-semibold text-slate-800">{del.driver_name}</div>
                      <div className="text-[11px] text-slate-400">{del.driver_contact}</div>
                    </td>
                    <td className="p-4">
                      <div className="text-slate-600 truncate max-w-xs">
                        <span className="text-slate-500">{del.origin}</span>
                        <span className="text-teal-600 font-bold mx-1">→</span>
                        <span className="text-slate-900 font-semibold">{del.destination}</span>
                      </div>
                    </td>
                    <td className="p-4 text-slate-600">{del.estimated_delivery}</td>
                    <td className="p-4">
                      <span
                        className={`inline-flex px-2.5 py-1 rounded-full text-[11px] font-bold capitalize ${
                          del.status === 'delivered'
                            ? 'bg-emerald-100 text-emerald-800'
                            : del.status === 'in_transit'
                            ? 'bg-blue-100 text-blue-800'
                            : del.status === 'pending'
                            ? 'bg-amber-100 text-amber-800'
                            : 'bg-rose-100 text-rose-800'
                        }`}
                      >
                        {del.status.replace('_', ' ')}
                      </span>
                    </td>
                    <td className="p-4 text-right">
                      <button
                        onClick={() => setSelectedDelivery(del)}
                        className="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg transition-colors flex items-center gap-1 ml-auto"
                      >
                        <Eye className="w-3.5 h-3.5" /> Details & Track
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* MODAL: Detailed Delivery Timeline & Status Management */}
      {selectedDelivery && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200">
            <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
              <div>
                <div className="flex items-center gap-2">
                  <span className="font-mono text-base font-extrabold text-teal-700">
                    {selectedDelivery.tracking_number}
                  </span>
                  <span
                    className={`px-2.5 py-0.5 rounded-full text-xs font-bold capitalize ${
                      selectedDelivery.status === 'delivered'
                        ? 'bg-emerald-100 text-emerald-800'
                        : selectedDelivery.status === 'in_transit'
                        ? 'bg-blue-100 text-blue-800'
                        : 'bg-amber-100 text-amber-800'
                    }`}
                  >
                    {selectedDelivery.status.replace('_', ' ')}
                  </span>
                </div>
                <p className="text-xs text-slate-500 mt-0.5">PO Reference: {selectedDelivery.po_number}</p>
              </div>
              <button
                onClick={() => setSelectedDelivery(null)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100"
              >
                ✕
              </button>
            </div>

            <div className="space-y-6 text-xs">
              {/* Timeline Flow */}
              <div className="p-4 bg-slate-50 rounded-xl border border-slate-200">
                <h3 className="font-bold text-slate-700 uppercase tracking-wider text-[10px] mb-3">
                  Logistics Milestones
                </h3>
                <div className="flex items-center justify-between relative">
                  {/* Progress Line */}
                  <div className="absolute top-1/2 left-4 right-4 h-0.5 bg-slate-200 -translate-y-1/2 -z-0"></div>

                  <div className="relative z-10 text-center">
                    <div className="w-8 h-8 rounded-full bg-emerald-600 text-white font-bold flex items-center justify-center mx-auto shadow">
                      ✓
                    </div>
                    <div className="font-bold text-slate-900 mt-1">Dispatched</div>
                    <div className="text-[10px] text-slate-500">Warehouse Hub</div>
                  </div>

                  <div className="relative z-10 text-center">
                    <div
                      className={`w-8 h-8 rounded-full font-bold flex items-center justify-center mx-auto shadow ${
                        selectedDelivery.status === 'in_transit' || selectedDelivery.status === 'delivered'
                          ? 'bg-blue-600 text-white'
                          : 'bg-slate-200 text-slate-500'
                      }`}
                    >
                      <Truck className="w-4 h-4" />
                    </div>
                    <div className="font-bold text-slate-900 mt-1">In Transit</div>
                    <div className="text-[10px] text-slate-500">Cold Chain Van</div>
                  </div>

                  <div className="relative z-10 text-center">
                    <div
                      className={`w-8 h-8 rounded-full font-bold flex items-center justify-center mx-auto shadow ${
                        selectedDelivery.status === 'delivered'
                          ? 'bg-emerald-600 text-white'
                          : 'bg-slate-200 text-slate-500'
                      }`}
                    >
                      {selectedDelivery.status === 'delivered' ? '✓' : '3'}
                    </div>
                    <div className="font-bold text-slate-900 mt-1">Delivered</div>
                    <div className="text-[10px] text-slate-500">Lab Dock</div>
                  </div>
                </div>
              </div>

              {/* Driver & Details */}
              <div className="grid grid-cols-2 gap-4">
                <div className="p-4 bg-white border border-slate-200 rounded-xl space-y-2">
                  <div className="font-bold text-slate-500 uppercase tracking-wider text-[10px]">Carrier & Driver</div>
                  <div className="font-extrabold text-slate-900 text-sm">{selectedDelivery.carrier}</div>
                  <div className="text-slate-700">Driver: {selectedDelivery.driver_name}</div>
                  <div className="text-slate-500">Contact: {selectedDelivery.driver_contact}</div>
                </div>

                <div className="p-4 bg-white border border-slate-200 rounded-xl space-y-2">
                  <div className="font-bold text-slate-500 uppercase tracking-wider text-[10px]">Routing & Dates</div>
                  <div>
                    <span className="text-slate-400">From: </span>
                    <span className="font-semibold text-slate-800">{selectedDelivery.origin}</span>
                  </div>
                  <div>
                    <span className="text-slate-400">To: </span>
                    <span className="font-semibold text-slate-800">{selectedDelivery.destination}</span>
                  </div>
                  <div className="text-teal-700 font-bold">
                    Est. Arrival: {selectedDelivery.estimated_delivery}
                  </div>
                </div>
              </div>

              {selectedDelivery.items_summary && (
                <div className="p-3 bg-slate-50 rounded-xl border border-slate-200">
                  <div className="font-bold text-slate-700 uppercase tracking-wider text-[10px] mb-1">
                    Shipment Contents
                  </div>
                  <p className="text-slate-800 font-medium">{selectedDelivery.items_summary}</p>
                  {selectedDelivery.notes && <p className="text-slate-500 text-[11px] mt-1">{selectedDelivery.notes}</p>}
                </div>
              )}

              {/* Status Action Buttons */}
              <div className="p-4 bg-teal-50/60 rounded-xl border border-teal-100 flex flex-wrap items-center justify-between gap-2">
                <div className="text-xs font-bold text-teal-900">Update Shipment Delivery Status:</div>
                <div className="flex items-center gap-2">
                  {selectedDelivery.status !== 'in_transit' && (
                    <button
                      onClick={() => handleStatusUpdate('in_transit')}
                      className="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg"
                    >
                      Mark In-Transit
                    </button>
                  )}
                  {selectedDelivery.status !== 'delivered' && (
                    <button
                      onClick={() => handleStatusUpdate('delivered')}
                      className="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg"
                    >
                      Confirm Delivered
                    </button>
                  )}
                </div>
              </div>

              <div className="flex justify-end pt-3 border-t border-slate-200">
                <button
                  onClick={() => setSelectedDelivery(null)}
                  className="px-4 py-2 bg-slate-800 text-white font-bold text-xs rounded-xl"
                >
                  Close Tracking
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* MODAL: Dispatch New Delivery Form */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
            <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
              <div className="flex items-center gap-2">
                <Truck className="w-5 h-5 text-teal-600" />
                <h2 className="font-bold text-slate-900 text-base">Schedule New Delivery Dispatch</h2>
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
                <label className="block font-bold text-slate-700 mb-1">Tracking Number</label>
                <input
                  type="text"
                  value={trackingNumber}
                  onChange={(e) => setTrackingNumber(e.target.value)}
                  required
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono font-bold text-teal-700"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Associated Purchase Order</label>
                <select
                  value={selectedPoId}
                  onChange={(e) => setSelectedPoId(Number(e.target.value))}
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                >
                  {purchaseOrders.map((po) => (
                    <option key={po.id} value={po.id}>
                      {po.po_number} - {po.store_name} ({po.status})
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Logistics Carrier</label>
                  <input
                    type="text"
                    value={carrier}
                    onChange={(e) => setCarrier(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Driver Name</label>
                  <input
                    type="text"
                    value={driverName}
                    onChange={(e) => setDriverName(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Origin Point</label>
                  <input
                    type="text"
                    value={origin}
                    onChange={(e) => setOrigin(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Destination Facility</label>
                  <input
                    type="text"
                    value={destination}
                    onChange={(e) => setDestination(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Driver Contact #</label>
                  <input
                    type="text"
                    value={driverContact}
                    onChange={(e) => setDriverContact(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Estimated Arrival Date</label>
                  <input
                    type="date"
                    value={estDate}
                    onChange={(e) => setEstDate(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Manifest / Items Summary</label>
                <input
                  type="text"
                  value={itemsSummary}
                  onChange={(e) => setItemsSummary(e.target.value)}
                  placeholder="e.g. 10x Acetone USP, 5x Ethanol"
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Handling Instructions</label>
                <textarea
                  rows={2}
                  value={notes}
                  onChange={(e) => setNotes(e.target.value)}
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
                  Dispatch Shipment
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
