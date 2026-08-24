import React, { useState, useEffect } from 'react';
import { useApp } from '../../context/AppContext';
import { Delivery, DeliveryStatus, DeliveryItem } from '../../types';
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
  Thermometer,
  ShieldCheck,
  Printer,
  Compass,
  Zap,
  Activity,
  Send,
  Check,
  RefreshCw,
  Box,
  ChevronRight,
  ExternalLink,
  Navigation,
  FileCheck,
  Phone,
  Layers,
} from 'lucide-react';

export const DeliveryView: React.FC = () => {
  const {
    deliveries,
    purchaseOrders,
    suppliers,
    inventory,
    currentUser,
    createDelivery,
    updateDeliveryStatus,
    addDeliveryCheckpoint,
    receiveDeliveryIntoStock,
    searchQuery,
  } = useApp();

  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [localSearch, setLocalSearch] = useState('');
  const [selectedDelivery, setSelectedDelivery] = useState<Delivery | null>(null);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showReceiveModal, setShowReceiveModal] = useState(false);
  const [showCheckpointModal, setShowCheckpointModal] = useState(false);
  const [showEmailModal, setShowEmailModal] = useState(false);
  const [showPrintModal, setShowPrintModal] = useState(false);
  const [toastMessage, setToastMessage] = useState<string | null>(null);

  // Checkpoint Modal form
  const [cpStatus, setCpStatus] = useState<DeliveryStatus>('in_transit');
  const [cpLocation, setCpLocation] = useState('');
  const [cpDescription, setCpDescription] = useState('');
  const [cpTemp, setCpTemp] = useState('4.8');

  // Receive / QC Inspection Modal form
  const [inspectorName, setInspectorName] = useState(currentUser?.full_name || 'Alice Williams (QC Chemist)');
  const [sealIntact, setSealIntact] = useState(true);
  const [tempCompliant, setTempCompliant] = useState(true);
  const [packagingGood, setPackagingGood] = useState(true);
  const [inspectionNotes, setInspectionNotes] = useState('All reagent seals verified intact. Temperature logger within standard 2-8°C specs.');

  // Email Notification Modal state
  const [emailRecipient, setEmailRecipient] = useState('lab.supervisor@mcpil.com');
  const [emailSubject, setEmailSubject] = useState('');
  const [emailBody, setEmailBody] = useState('');

  // New Delivery Form state
  const [trackingNumber, setTrackingNumber] = useState(`TRK-${Math.floor(10000 + Math.random() * 90000)}`);
  const [selectedPoId, setSelectedPoId] = useState<number>(purchaseOrders[0]?.id || 1);
  const [carrier, setCarrier] = useState('MCPIL Dedicated Cold-Chain Logistics');
  const [driverName, setDriverName] = useState('Marco Diaz');
  const [driverContact, setDriverContact] = useState('+1-555-0982');
  const [vehiclePlate, setVehiclePlate] = useState('MC-VAN-204');
  const [origin, setOrigin] = useState('Central Warehouse Reagents Hub B');
  const [destination, setDestination] = useState('MCPIL Pharmaceutical Lab Cleanroom Dock 2');
  const [tempProfile, setTempProfile] = useState<'cold' | 'deep_freeze' | 'ambient' | 'cryo'>('cold');
  const [estDate, setEstDate] = useState(
    new Date(Date.now() + 2 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
  );
  const [estTime, setEstTime] = useState('14:00');
  const [customItems, setCustomItems] = useState<string>('50x USP Chemical Reagents, 25x Sterile Solvents');
  const [deliveryNotes, setDeliveryNotes] = useState('Cold-chain continuous temperature logging active.');

  // Keep selected delivery synchronized with AppContext updates
  useEffect(() => {
    if (selectedDelivery) {
      const refreshed = deliveries.find((d) => d.id === selectedDelivery.id);
      if (refreshed) {
        setSelectedDelivery(refreshed);
      }
    }
  }, [deliveries]);

  const triggerToast = (msg: string) => {
    setToastMessage(msg);
    setTimeout(() => setToastMessage(null), 4500);
  };

  // Pre-fill email dialog when delivery is selected
  const handleOpenEmailModal = (del: Delivery) => {
    setEmailSubject(`[Logistics Update] Shipment ${del.tracking_number} - Status: ${del.status.toUpperCase()}`);
    setEmailBody(
      `Dear MCPIL Laboratory Operations Team,\n\n` +
      `This is an automated dispatch advisory for shipment ${del.tracking_number} (${del.po_number}).\n\n` +
      `• Carrier: ${del.carrier}\n` +
      `• Driver: ${del.driver_name || 'Assigned Logistics Driver'} (${del.driver_contact || 'N/A'})\n` +
      `• Vehicle Plate: ${del.vehicle_plate || 'VAN-01'}\n` +
      `• Current Status: ${del.status.toUpperCase()}\n` +
      `• Current Location: ${del.current_location || del.origin || 'In Transit'}\n` +
      `• Cold-Chain Temperature: ${del.temperature_celsius ? `${del.temperature_celsius}°C` : '4.6°C'}\n` +
      `• Estimated Arrival: ${del.estimated_delivery || del.expected_date}\n\n` +
      `Items Manifest:\n${del.items.map((i) => ` - ${i.item_name} (Qty: ${i.quantity_ordered})`).join('\n') || del.items_summary || 'Standard Chemical Consumables'}\n\n` +
      `Please ensure cleanroom receiving bay is prepped upon docking.\n\n` +
      `Regards,\nMCPIL Logistics & Quality Assurance Portal`
    );
    setShowEmailModal(true);
  };

  const handleSendEmail = (e: React.FormEvent) => {
    e.preventDefault();
    setShowEmailModal(false);
    triggerToast(`Email dispatched successfully to ${emailRecipient}!`);
  };

  const handleCreateSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const po = purchaseOrders.find((p) => p.id === Number(selectedPoId));
    const targetSupplier = suppliers.find((s) => s.id === po?.supplier_id) || suppliers[0];

    const tempMap = {
      cold: { temp: 4.5, range: '2°C - 8°C (Cold-Chain Active)' },
      deep_freeze: { temp: -20.0, range: '-20°C to -10°C (Deep Freeze)' },
      ambient: { temp: 21.0, range: '15°C - 25°C (Controlled Room Temp)' },
      cryo: { temp: -78.5, range: '-80°C to -70°C (Cryogenic Dry Ice)' },
    };

    const selectedTemp = tempMap[tempProfile];

    // Build items from PO if available, or custom
    const itemsList: DeliveryItem[] =
      po && po.items.length > 0
        ? po.items.map((pi, idx) => ({
            id: `del-item-${Date.now()}-${idx}`,
            item_name: pi.item_name,
            quantity_ordered: pi.quantity,
            quantity_delivered: 0,
            quantity_pending: pi.quantity,
            condition_status: 'good',
            notes: 'Awaiting cleanroom intake verification.',
          }))
        : [
            {
              id: `del-item-${Date.now()}-1`,
              item_name: customItems,
              quantity_ordered: 1,
              quantity_delivered: 0,
              quantity_pending: 1,
              condition_status: 'good',
              notes: 'Manifest verified from dispatch slip.',
            },
          ];

    const newDelivery = createDelivery({
      delivery_number: `DEL-${new Date().getFullYear()}-${String(deliveries.length + 1).padStart(3, '0')}`,
      po_id: po ? po.id : 0,
      po_number: po ? po.po_number : 'DIRECT-DISPATCH',
      supplier_id: targetSupplier ? targetSupplier.id : 1,
      supplier_name: targetSupplier ? targetSupplier.name : 'MCPIL Internal Depot',
      delivery_date: new Date().toISOString().split('T')[0],
      expected_date: estDate,
      estimated_delivery: `${estDate} ${estTime}`,
      status: 'in_transit',
      tracking_number: trackingNumber,
      carrier,
      driver_name: driverName,
      driver_contact: driverContact,
      origin,
      destination,
      temperature_celsius: selectedTemp.temp,
      temp_range: selectedTemp.range,
      current_location: `${origin} (Departure Bay)`,
      speed_kmh: 45,
      vehicle_plate: vehiclePlate,
      items_summary: customItems,
      notes: deliveryNotes,
      created_by: currentUser?.id || 1,
      created_by_name: currentUser?.full_name || 'Admin',
      items: itemsList,
    });

    setShowCreateModal(false);
    setSelectedDelivery(newDelivery);
    setTrackingNumber(`TRK-${Math.floor(10000 + Math.random() * 90000)}`);
    triggerToast(`Shipment ${newDelivery.tracking_number} dispatched & GPS telemetry online!`);
  };

  const handleAddCheckpointSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedDelivery) return;

    addDeliveryCheckpoint(selectedDelivery.id, {
      status: cpStatus,
      location: cpLocation || selectedDelivery.current_location || 'En Route Waypoint',
      description: cpDescription || `Passed checkpoint: ${cpLocation}`,
      temperature: parseFloat(cpTemp) || selectedDelivery.temperature_celsius || 4.5,
    });

    setShowCheckpointModal(false);
    setCpLocation('');
    setCpDescription('');
    triggerToast(`New checkpoint logged for ${selectedDelivery.tracking_number}!`);
  };

  const handleReceiveStockSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedDelivery) return;

    if (!sealIntact || !tempCompliant) {
      alert('Warning: QC Inspection requires physical seal integrity and temperature compliance to accept stock into Bodega.');
      return;
    }

    receiveDeliveryIntoStock(selectedDelivery.id, inspectorName, inspectionNotes);
    setShowReceiveModal(false);
    triggerToast(`Shipment ${selectedDelivery.tracking_number} received! Inventory stock updated at Bodega.`);
  };

  const handleSimulateGpsTick = () => {
    if (!selectedDelivery) return;
    const waypoints = [
      { loc: 'Interstate Highway 95 - Mile Marker 155', speed: 72, temp: 4.4, desc: 'Highway transit cruising speed maintained.' },
      { loc: 'Metro Cleanroom Logistics Gateway #3', speed: 35, temp: 4.5, desc: 'Approaching industrial district access gate.' },
      { loc: 'MCPIL Compound Cleanroom Receiving Dock 2', speed: 10, temp: 4.6, desc: 'Arrived at perimeter security inspection bay.' },
    ];
    const pick = waypoints[Math.floor(Math.random() * waypoints.length)];

    addDeliveryCheckpoint(selectedDelivery.id, {
      status: 'in_transit',
      location: pick.loc,
      description: `GPS Telemetry Ping: ${pick.desc} (${pick.speed} km/h)`,
      temperature: pick.temp,
    });
    triggerToast(`Simulated GPS ping received: ${pick.loc} • ${pick.speed} km/h • ${pick.temp}°C`);
  };

  const effectiveSearch = (searchQuery || localSearch).toLowerCase();
  const filteredDeliveries = deliveries.filter((del) => {
    const matchesStatus =
      statusFilter === 'all'
        ? true
        : statusFilter === 'cold_chain'
        ? del.temp_range && del.temp_range.includes('Cold')
        : del.status === statusFilter;

    const matchesSearch =
      del.tracking_number.toLowerCase().includes(effectiveSearch) ||
      del.po_number.toLowerCase().includes(effectiveSearch) ||
      del.carrier.toLowerCase().includes(effectiveSearch) ||
      (del.driver_name && del.driver_name.toLowerCase().includes(effectiveSearch)) ||
      (del.supplier_name && del.supplier_name.toLowerCase().includes(effectiveSearch)) ||
      (del.destination && del.destination.toLowerCase().includes(effectiveSearch)) ||
      del.items.some((i) => i.item_name.toLowerCase().includes(effectiveSearch));

    return matchesStatus && matchesSearch;
  });

  const inTransitCount = deliveries.filter((d) => d.status === 'in_transit').length;
  const deliveredCount = deliveries.filter((d) => d.status === 'delivered').length;
  const pendingCount = deliveries.filter((d) => d.status === 'pending').length;

  return (
    <div className="space-y-6">
      {/* Toast Notification */}
      {toastMessage && (
        <div className="fixed top-5 right-5 z-50 p-4 bg-slate-900 text-white border border-teal-500/50 rounded-2xl shadow-2xl flex items-center gap-3 animate-in fade-in slide-in-from-top-2">
          <div className="w-8 h-8 rounded-xl bg-teal-500/20 text-teal-400 flex items-center justify-center shrink-0">
            <CheckCircle2 className="w-4 h-4" />
          </div>
          <div>
            <div className="text-xs font-bold text-white">Logistics Notification</div>
            <div className="text-xs text-slate-300">{toastMessage}</div>
          </div>
        </div>
      )}

      {/* Header Banner */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <div>
          <div className="flex items-center gap-2.5">
            <div className="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center border border-teal-200 shadow-xs">
              <Truck className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="text-xl font-bold text-slate-900">Laboratory Logistics & Live Delivery Tracker</h1>
                <span className="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">
                  <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                  IoT Telemetry Live
                </span>
              </div>
              <p className="text-xs text-slate-500 mt-0.5">
                Real-time cold-chain vehicle GPS tracking, automated email alerts, and instant Bodega inventory stock receiving.
              </p>
            </div>
          </div>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={() => setShowCreateModal(true)}
            className="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white font-bold text-xs rounded-xl shadow-sm transition-colors cursor-pointer"
          >
            <Plus className="w-4 h-4" />
            Dispatch New Delivery
          </button>
        </div>
      </div>

      {/* KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <span className="text-blue-600 font-bold uppercase tracking-wider text-[10px] flex items-center gap-1">
              <Navigation className="w-3.5 h-3.5" /> En Route (In-Transit)
            </span>
            <div className="text-2xl font-black text-slate-900 mt-1 font-mono">{inTransitCount}</div>
            <div className="text-[11px] text-slate-500">Active cold-chain vans</div>
          </div>
          <div className="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
            <Truck className="w-5 h-5" />
          </div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <span className="text-emerald-600 font-bold uppercase tracking-wider text-[10px] flex items-center gap-1">
              <CheckCircle2 className="w-3.5 h-3.5" /> Successfully Received
            </span>
            <div className="text-2xl font-black text-slate-900 mt-1 font-mono">{deliveredCount}</div>
            <div className="text-[11px] text-emerald-600 font-medium">QC inspected & in stock</div>
          </div>
          <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
            <ShieldCheck className="w-5 h-5" />
          </div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <span className="text-amber-600 font-bold uppercase tracking-wider text-[10px] flex items-center gap-1">
              <Clock className="w-3.5 h-3.5" /> Awaiting Pickup
            </span>
            <div className="text-2xl font-black text-slate-900 mt-1 font-mono">{pendingCount}</div>
            <div className="text-[11px] text-slate-500">Supplier staging bay</div>
          </div>
          <div className="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
            <Box className="w-5 h-5" />
          </div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <span className="text-purple-600 font-bold uppercase tracking-wider text-[10px] flex items-center gap-1">
              <Thermometer className="w-3.5 h-3.5" /> Cold-Chain Integrity
            </span>
            <div className="text-2xl font-black text-slate-900 mt-1 font-mono">100%</div>
            <div className="text-[11px] text-slate-500">Within 2°C - 8°C specs</div>
          </div>
          <div className="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
            <Activity className="w-5 h-5" />
          </div>
        </div>
      </div>

      {/* Filter and Search Bar */}
      <div className="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <div className="flex flex-wrap items-center gap-1.5">
          {[
            { id: 'all', label: 'All Deliveries' },
            { id: 'in_transit', label: 'In Transit' },
            { id: 'delivered', label: 'Delivered / In Stock' },
            { id: 'pending', label: 'Pending Dispatch' },
            { id: 'cold_chain', label: 'Cold-Chain (2-8°C)' },
          ].map((tab) => (
            <button
              key={tab.id}
              onClick={() => setStatusFilter(tab.id)}
              className={`px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer ${
                statusFilter === tab.id
                  ? 'bg-slate-900 text-white shadow-xs'
                  : 'bg-slate-100 hover:bg-slate-200 text-slate-600'
              }`}
            >
              {tab.label}
            </button>
          ))}
        </div>

        <div className="relative w-full sm:w-72">
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search tracking #, PO, driver, reagent..."
            value={localSearch}
            onChange={(e) => setLocalSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-500 font-medium"
          />
        </div>
      </div>

      {/* Deliveries Table */}
      <div className="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50/80 text-slate-600 font-bold border-b border-slate-200 uppercase tracking-wider text-[10px]">
              <tr>
                <th className="p-4">Tracking & Status</th>
                <th className="p-4">PO & Supplier</th>
                <th className="p-4">Carrier & Vehicle</th>
                <th className="p-4">Route & Current Location</th>
                <th className="p-4">Cold-Chain Temp</th>
                <th className="p-4">Est. Delivery</th>
                <th className="p-4 text-right">Interactive Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 font-medium">
              {filteredDeliveries.length === 0 ? (
                <tr>
                  <td colSpan={7} className="p-12 text-center text-slate-400">
                    <Truck className="w-8 h-8 mx-auto text-slate-300 mb-2" />
                    <p className="font-semibold text-slate-600">No shipments found matching the selected filter.</p>
                    <p className="text-[11px] text-slate-400 mt-1">Try changing the status filter or dispatch a new shipment.</p>
                  </td>
                </tr>
              ) : (
                filteredDeliveries.map((del) => (
                  <tr key={del.id} className="hover:bg-slate-50/80 transition-colors">
                    <td className="p-4">
                      <div className="flex items-center gap-2">
                        <div className="font-mono font-bold text-teal-700 text-sm">{del.tracking_number}</div>
                        <span
                          className={`inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold capitalize ${
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
                      </div>
                      <div className="text-[10px] text-slate-400 font-mono mt-0.5">{del.delivery_number}</div>
                    </td>

                    <td className="p-4">
                      <div className="font-bold text-slate-900">{del.supplier_name}</div>
                      <div className="text-[11px] text-teal-700 font-mono font-bold">{del.po_number}</div>
                    </td>

                    <td className="p-4">
                      <div className="font-semibold text-slate-800">{del.carrier}</div>
                      <div className="text-[11px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                        <User className="w-3 h-3 text-slate-400" />
                        <span>{del.driver_name || 'Assigned Driver'}</span>
                        {del.vehicle_plate && (
                          <span className="px-1 py-0.2 bg-slate-100 rounded text-[9px] font-mono text-slate-600">
                            {del.vehicle_plate}
                          </span>
                        )}
                      </div>
                    </td>

                    <td className="p-4 max-w-xs">
                      <div className="text-slate-600 truncate text-[11px]">
                        <span className="text-slate-400">From:</span> {del.origin || 'Supplier Depot'}
                      </div>
                      <div className="text-slate-900 font-semibold truncate text-[11px] mt-0.5">
                        <span className="text-slate-400">To:</span> {del.destination || 'MCPIL Cleanroom'}
                      </div>
                      {del.current_location && (
                        <div className="text-[10px] text-blue-600 font-medium flex items-center gap-1 mt-0.5">
                          <MapPin className="w-3 h-3" />
                          <span className="truncate">{del.current_location}</span>
                        </div>
                      )}
                    </td>

                    <td className="p-4">
                      {del.temperature_celsius !== undefined ? (
                        <div className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-teal-50 border border-teal-200 rounded-lg text-teal-800 font-mono font-bold">
                          <Thermometer className="w-3.5 h-3.5 text-teal-600 shrink-0" />
                          <span>{del.temperature_celsius}°C</span>
                        </div>
                      ) : (
                        <span className="text-slate-400 font-mono">4.5°C</span>
                      )}
                      <div className="text-[10px] text-slate-400 mt-0.5">{del.temp_range || '2-8°C Validated'}</div>
                    </td>

                    <td className="p-4">
                      <div className="font-semibold text-slate-800">{del.estimated_delivery || del.expected_date}</div>
                      <div className="text-[10px] text-slate-400">
                        {del.status === 'delivered' ? 'Completed' : 'Estimated Arrival'}
                      </div>
                    </td>

                    <td className="p-4 text-right">
                      <div className="flex items-center justify-end gap-1.5">
                        <button
                          onClick={() => setSelectedDelivery(del)}
                          className="px-3 py-1.5 bg-slate-100 hover:bg-teal-50 hover:text-teal-700 text-slate-700 font-bold rounded-xl transition-colors flex items-center gap-1 cursor-pointer"
                        >
                          <Eye className="w-3.5 h-3.5" /> Track & Live GPS
                        </button>

                        <button
                          onClick={() => handleOpenEmailModal(del)}
                          title="Send Email Alert"
                          className="p-1.5 bg-slate-100 hover:bg-blue-50 hover:text-blue-700 text-slate-600 rounded-xl transition-colors cursor-pointer"
                        >
                          <Mail className="w-3.5 h-3.5" />
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

      {/* ===================== MODAL: LIVE TRACKING & GPS TELEMETRY DRAWER ===================== */}
      {selectedDelivery && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 z-50 animate-in fade-in duration-150 overflow-y-auto">
          <div className="bg-white rounded-3xl max-w-4xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 my-auto max-h-[92vh] overflow-y-auto">
            {/* Modal Header */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-200">
              <div>
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-2xl bg-teal-600 text-white flex items-center justify-center font-bold shadow-md shadow-teal-600/20">
                    <Truck className="w-5 h-5" />
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="font-mono text-lg font-black text-slate-900">
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
                    <p className="text-xs text-slate-500 font-medium mt-0.5">
                      Purchase Order: <span className="text-teal-700 font-bold font-mono">{selectedDelivery.po_number}</span> • Carrier: {selectedDelivery.carrier}
                    </p>
                  </div>
                </div>
              </div>

              <div className="flex items-center gap-2">
                <button
                  onClick={() => setShowPrintModal(true)}
                  className="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors cursor-pointer"
                >
                  <Printer className="w-3.5 h-3.5" /> Print Manifest
                </button>
                <button
                  onClick={() => handleOpenEmailModal(selectedDelivery)}
                  className="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors cursor-pointer"
                >
                  <Mail className="w-3.5 h-3.5" /> Email Alert
                </button>
                <button
                  onClick={() => setSelectedDelivery(null)}
                  className="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 cursor-pointer"
                >
                  ✕
                </button>
              </div>
            </div>

            <div className="space-y-6 text-xs">
              {/* LIVE GPS & COLD-CHAIN TELEMETRY SIMULATOR PANEL */}
              <div className="bg-slate-950 text-white rounded-2xl p-5 border border-slate-800 relative overflow-hidden shadow-lg">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 mb-4 border-b border-slate-800">
                  <div className="flex items-center gap-2">
                    <span className="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                    <span className="font-bold text-white uppercase tracking-wider text-xs">
                      Live Vehicle GPS Telemetry & Temperature Monitor
                    </span>
                  </div>

                  <div className="flex items-center gap-2">
                    <button
                      onClick={handleSimulateGpsTick}
                      className="px-3 py-1 bg-teal-600/80 hover:bg-teal-600 text-white rounded-lg font-bold text-[11px] flex items-center gap-1.5 cursor-pointer transition-colors shadow-xs"
                    >
                      <RefreshCw className="w-3 h-3" /> Simulate GPS Ping
                    </button>
                    <button
                      onClick={() => setShowCheckpointModal(true)}
                      className="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-teal-400 rounded-lg font-bold text-[11px] flex items-center gap-1.5 cursor-pointer border border-teal-500/30"
                    >
                      <Plus className="w-3 h-3" /> Log Checkpoint
                    </button>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  {/* Realtime Temperature Sensor Widget */}
                  <div className="bg-slate-900/90 rounded-xl p-4 border border-slate-800 flex flex-col justify-between">
                    <div className="flex items-center justify-between text-slate-400">
                      <span className="text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                        <Thermometer className="w-3.5 h-3.5 text-teal-400" /> Sensor Pod #CP-08
                      </span>
                      <span className="text-[10px] px-2 py-0.5 rounded bg-emerald-950 text-emerald-400 border border-emerald-800 font-mono">
                        NORMAL
                      </span>
                    </div>

                    <div className="my-2">
                      <div className="text-3xl font-black text-teal-400 font-mono">
                        {selectedDelivery.temperature_celsius ? `${selectedDelivery.temperature_celsius}°C` : '4.6°C'}
                      </div>
                      <p className="text-[10px] text-slate-400 mt-0.5">
                        Target Profile: <span className="text-white font-medium">{selectedDelivery.temp_range || '2°C - 8°C Cold-Chain'}</span>
                      </p>
                    </div>

                    <div className="pt-2 border-t border-slate-800 flex items-center justify-between text-[10px] text-slate-400">
                      <span>Battery: 96%</span>
                      <span>Door Seal: LOCKED</span>
                    </div>
                  </div>

                  {/* Vehicle Route & Speed */}
                  <div className="bg-slate-900/90 rounded-xl p-4 border border-slate-800 flex flex-col justify-between">
                    <div className="flex items-center justify-between text-slate-400">
                      <span className="text-[10px] font-bold uppercase tracking-wider flex items-center gap-1">
                        <Compass className="w-3.5 h-3.5 text-blue-400" /> GPS Navigation
                      </span>
                      <span className="text-[10px] font-mono text-slate-300">
                        {selectedDelivery.speed_kmh !== undefined ? `${selectedDelivery.speed_kmh} km/h` : '64 km/h'}
                      </span>
                    </div>

                    <div className="my-2">
                      <div className="text-xs font-bold text-white flex items-center gap-1.5">
                        <MapPin className="w-3.5 h-3.5 text-blue-400 shrink-0" />
                        <span className="truncate">{selectedDelivery.current_location || 'En Route to MCPIL'}</span>
                      </div>
                      <div className="text-[10px] text-slate-400 mt-1 font-mono">
                        GPS: 14.59951° N, 120.98422° E (Cleanroom Corridor)
                      </div>
                    </div>

                    <div className="pt-2 border-t border-slate-800 flex items-center justify-between text-[10px] text-slate-400">
                      <span>Plate: {selectedDelivery.vehicle_plate || 'MC-VAN-204'}</span>
                      <span>Driver: {selectedDelivery.driver_name || 'Marco Diaz'}</span>
                    </div>
                  </div>

                  {/* Route Progress Visual Bar */}
                  <div className="bg-slate-900/90 rounded-xl p-4 border border-slate-800 flex flex-col justify-between">
                    <div className="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                      Fulfillment Milestone
                    </div>

                    <div className="my-2">
                      <div className="flex items-center justify-between text-xs font-bold mb-1">
                        <span className="text-teal-400">
                          {selectedDelivery.status === 'delivered' ? '100% Completed' : '75% In Transit'}
                        </span>
                        <span className="text-slate-400 text-[10px]">
                          ETA: {selectedDelivery.estimated_delivery || selectedDelivery.expected_date}
                        </span>
                      </div>
                      <div className="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div
                          className={`h-full rounded-full transition-all duration-500 ${
                            selectedDelivery.status === 'delivered' ? 'bg-emerald-500 w-full' : 'bg-teal-500 w-3/4'
                          }`}
                        ></div>
                      </div>
                    </div>

                    <div className="text-[10px] text-slate-400">
                      Destination: <span className="text-white font-medium">{selectedDelivery.destination || 'Cleanroom Dock'}</span>
                    </div>
                  </div>
                </div>
              </div>

              {/* TIMELINE & MILESTONES */}
              <div className="p-5 bg-slate-50 rounded-2xl border border-slate-200">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="font-bold text-slate-800 uppercase tracking-wider text-xs flex items-center gap-2">
                    <Layers className="w-4 h-4 text-teal-600" />
                    Shipment Waypoint History & Telemetry Logs ({selectedDelivery.timeline.length})
                  </h3>
                  <button
                    onClick={() => setShowCheckpointModal(true)}
                    className="text-xs text-teal-700 font-bold hover:underline cursor-pointer flex items-center gap-1"
                  >
                    + Add Custom Waypoint
                  </button>
                </div>

                <div className="relative pl-6 space-y-4 before:content-[''] before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                  {selectedDelivery.timeline.map((event, idx) => (
                    <div key={idx} className="relative group">
                      <div
                        className={`absolute -left-6 top-1 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold ${
                          event.status === 'delivered'
                            ? 'bg-emerald-600 text-white'
                            : event.status === 'in_transit'
                            ? 'bg-blue-600 text-white'
                            : 'bg-amber-500 text-white'
                        }`}
                      >
                        ✓
                      </div>
                      <div className="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                          <div className="font-bold text-slate-900 text-xs">{event.description}</div>
                          <div className="text-[11px] font-mono text-slate-400 flex items-center gap-1">
                            <Clock className="w-3 h-3" />
                            {event.timestamp}
                          </div>
                        </div>
                        {event.location && (
                          <div className="text-[11px] text-teal-700 font-medium flex items-center gap-1 mt-1">
                            <MapPin className="w-3 h-3 text-teal-600" />
                            {event.location}
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* SHIPMENT ITEMS MANIFEST */}
              <div className="bg-white rounded-2xl border border-slate-200 p-5 space-y-3">
                <div className="flex items-center justify-between">
                  <h3 className="font-bold text-slate-800 uppercase tracking-wider text-xs flex items-center gap-2">
                    <Package className="w-4 h-4 text-teal-600" />
                    Reagents & Material Manifest
                  </h3>
                  <span className="text-xs text-slate-500 font-mono">{selectedDelivery.items.length} items</span>
                </div>

                <div className="divide-y divide-slate-100 border border-slate-100 rounded-xl overflow-hidden">
                  {selectedDelivery.items.map((item) => (
                    <div key={item.id} className="p-3 bg-slate-50/50 flex items-center justify-between text-xs">
                      <div>
                        <div className="font-bold text-slate-900">{item.item_name}</div>
                        <div className="text-[11px] text-slate-500">{item.notes || 'Pharmaceutical Grade'}</div>
                      </div>
                      <div className="text-right">
                        <div className="font-mono font-bold text-slate-900">{item.quantity_ordered} Units</div>
                        <span
                          className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                            selectedDelivery.status === 'delivered'
                              ? 'bg-emerald-100 text-emerald-800'
                              : 'bg-blue-50 text-blue-700'
                          }`}
                        >
                          {selectedDelivery.status === 'delivered' ? 'Accepted into Bodega' : 'Pending Intake'}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* ACTION TOOLBAR: INTAKE & STATUS TRANSITIONS */}
              <div className="p-5 bg-teal-50/70 rounded-2xl border border-teal-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <div className="font-bold text-teal-950 text-sm">Warehouse Receiving & Status Workflow</div>
                  <p className="text-xs text-teal-800 mt-0.5">
                    {selectedDelivery.status === 'delivered'
                      ? `Goods verified & received by ${selectedDelivery.recipient_signature || 'QC Chemist'}.`
                      : 'Advance status or receive directly into Bodega stock.'}
                  </p>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                  {selectedDelivery.status !== 'in_transit' && selectedDelivery.status !== 'delivered' && (
                    <button
                      onClick={() => {
                        updateDeliveryStatus(selectedDelivery.id, 'in_transit', 'Dispatched on road via cold-chain van');
                        triggerToast(`Shipment ${selectedDelivery.tracking_number} marked IN TRANSIT!`);
                      }}
                      className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs cursor-pointer transition-colors"
                    >
                      Mark In-Transit
                    </button>
                  )}

                  {selectedDelivery.status !== 'delivered' && (
                    <button
                      onClick={() => setShowReceiveModal(true)}
                      className="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-sm flex items-center gap-1.5 cursor-pointer transition-colors"
                    >
                      <CheckCircle2 className="w-4 h-4" />
                      Receive & Post to Bodega Stock
                    </button>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ===================== MODAL: QC INSPECTION & INTAKE (POST TO STOCK) ===================== */}
      {showReceiveModal && selectedDelivery && (
        <div className="fixed inset-0 bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4 z-60 animate-in fade-in duration-150">
          <div className="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl border border-slate-200">
            <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
              <div className="flex items-center gap-2.5">
                <div className="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                  <ShieldCheck className="w-5 h-5" />
                </div>
                <div>
                  <h2 className="font-bold text-slate-900 text-base">Bodega Intake & QC Inspection</h2>
                  <p className="text-xs text-slate-500 font-mono">{selectedDelivery.tracking_number}</p>
                </div>
              </div>
              <button
                onClick={() => setShowReceiveModal(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 cursor-pointer"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleReceiveStockSubmit} className="space-y-4 text-xs">
              <div className="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-2xl text-emerald-900 space-y-2">
                <div className="font-bold text-xs flex items-center gap-1.5">
                  <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                  Automated Inventory Synchronization
                </div>
                <p className="text-[11px] text-emerald-800">
                  Accepting this delivery will automatically increment chemical stock counts in Bodega and record GLP audit transactions.
                </p>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Inspector / Receiver Full Name</label>
                <input
                  type="text"
                  value={inspectorName}
                  onChange={(e) => setInspectorName(e.target.value)}
                  required
                  className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-900 focus:ring-2 focus:ring-teal-500"
                />
              </div>

              <div className="space-y-2 pt-1">
                <div className="font-bold text-slate-700 uppercase tracking-wider text-[10px]">
                  Quality Assurance Verification Checklist
                </div>

                <label className="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer hover:bg-slate-100">
                  <input
                    type="checkbox"
                    checked={sealIntact}
                    onChange={(e) => setSealIntact(e.target.checked)}
                    className="rounded text-teal-600 focus:ring-teal-500 w-4 h-4"
                  />
                  <span className="font-semibold text-slate-800">
                    Tamper-evident security seals are intact and uncompromised
                  </span>
                </label>

                <label className="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer hover:bg-slate-100">
                  <input
                    type="checkbox"
                    checked={tempCompliant}
                    onChange={(e) => setTempCompliant(e.target.checked)}
                    className="rounded text-teal-600 focus:ring-teal-500 w-4 h-4"
                  />
                  <span className="font-semibold text-slate-800">
                    Cold-chain temperature data logger verified within 2°C - 8°C specs
                  </span>
                </label>

                <label className="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer hover:bg-slate-100">
                  <input
                    type="checkbox"
                    checked={packagingGood}
                    onChange={(e) => setPackagingGood(e.target.checked)}
                    className="rounded text-teal-600 focus:ring-teal-500 w-4 h-4"
                  />
                  <span className="font-semibold text-slate-800">
                    Reagent bottles and hazard labeling free from physical damage
                  </span>
                </label>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Intake Notes & Observations</label>
                <textarea
                  rows={2}
                  value={inspectionNotes}
                  onChange={(e) => setInspectionNotes(e.target.value)}
                  className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-900 focus:ring-2 focus:ring-teal-500"
                />
              </div>

              <div className="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <button
                  type="button"
                  onClick={() => setShowReceiveModal(false)}
                  className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow cursor-pointer flex items-center gap-1.5"
                >
                  <FileCheck className="w-4 h-4" /> Confirm Inspection & Stock Intake
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ===================== MODAL: ADD WAYPOINT CHECKPOINT ===================== */}
      {showCheckpointModal && selectedDelivery && (
        <div className="fixed inset-0 bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4 z-60 animate-in fade-in duration-150">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <div className="flex items-center justify-between pb-3 mb-4 border-b border-slate-200">
              <div className="flex items-center gap-2">
                <MapPin className="w-5 h-5 text-teal-600" />
                <h2 className="font-bold text-slate-900 text-base">Record Waypoint Checkpoint</h2>
              </div>
              <button
                onClick={() => setShowCheckpointModal(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 cursor-pointer"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleAddCheckpointSubmit} className="space-y-4 text-xs">
              <div>
                <label className="block font-bold text-slate-700 mb-1">Status Milestone</label>
                <select
                  value={cpStatus}
                  onChange={(e) => setCpStatus(e.target.value as DeliveryStatus)}
                  className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-900"
                >
                  <option value="in_transit">In Transit (Highway Checkpoint)</option>
                  <option value="pending">Pending (Staging)</option>
                  <option value="delivered">Arrived at Cleanroom Dock</option>
                </select>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Checkpoint Location</label>
                <input
                  type="text"
                  value={cpLocation}
                  onChange={(e) => setCpLocation(e.target.value)}
                  placeholder="e.g. Metro Expressway Toll Gate 4"
                  required
                  className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-900"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Telemetry Description</label>
                <input
                  type="text"
                  value={cpDescription}
                  onChange={(e) => setCpDescription(e.target.value)}
                  placeholder="e.g. Vehicle passed transit scan. Temperature logged."
                  required
                  className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-900"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Recorded Cold-Chain Temp (°C)</label>
                <input
                  type="number"
                  step="0.1"
                  value={cpTemp}
                  onChange={(e) => setCpTemp(e.target.value)}
                  className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono font-bold text-teal-700"
                />
              </div>

              <div className="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <button
                  type="button"
                  onClick={() => setShowCheckpointModal(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow cursor-pointer"
                >
                  Save Waypoint
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ===================== MODAL: EMAIL DISPATCH ADVISORY ===================== */}
      {showEmailModal && (
        <div className="fixed inset-0 bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4 z-60 animate-in fade-in duration-150">
          <div className="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-200">
            <div className="flex items-center justify-between pb-3 mb-4 border-b border-slate-200">
              <div className="flex items-center gap-2">
                <Mail className="w-5 h-5 text-blue-600" />
                <h2 className="font-bold text-slate-900 text-base">Send Automated Email Dispatch Notice</h2>
              </div>
              <button
                onClick={() => setShowEmailModal(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 cursor-pointer"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleSendEmail} className="space-y-4 text-xs">
              <div>
                <label className="block font-bold text-slate-700 mb-1">To Email Recipient</label>
                <input
                  type="email"
                  value={emailRecipient}
                  onChange={(e) => setEmailRecipient(e.target.value)}
                  required
                  className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-900"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Email Subject</label>
                <input
                  type="text"
                  value={emailSubject}
                  onChange={(e) => setEmailSubject(e.target.value)}
                  required
                  className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-900"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Email Body Content</label>
                <textarea
                  rows={8}
                  value={emailBody}
                  onChange={(e) => setEmailBody(e.target.value)}
                  className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono text-[11px] text-slate-800 leading-relaxed focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div className="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <button
                  type="button"
                  onClick={() => setShowEmailModal(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow cursor-pointer flex items-center gap-1.5"
                >
                  <Send className="w-3.5 h-3.5" /> Dispatch Email
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ===================== MODAL: PRINT DELIVERY RECEIPT & MANIFEST ===================== */}
      {showPrintModal && selectedDelivery && (
        <div className="fixed inset-0 bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4 z-60 animate-in fade-in duration-150">
          <div className="bg-white rounded-3xl max-w-2xl w-full p-8 shadow-2xl border border-slate-200 space-y-6 text-slate-900">
            <div className="flex items-center justify-between border-b border-slate-200 pb-4">
              <div>
                <div className="text-xs font-bold uppercase tracking-widest text-teal-700">
                  MCPIL PHARMACEUTICAL LABORATORY
                </div>
                <h2 className="text-xl font-black text-slate-900 mt-0.5">Cold-Chain Delivery & Receiving Slip</h2>
                <p className="text-xs text-slate-500 font-mono">Document Ref: {selectedDelivery.delivery_number}</p>
              </div>
              <div className="text-right">
                <div className="font-mono text-base font-extrabold text-teal-800">{selectedDelivery.tracking_number}</div>
                <div className="text-xs text-slate-500">{new Date().toLocaleDateString()}</div>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4 text-xs">
              <div className="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div className="font-bold text-slate-500 uppercase text-[10px]">Carrier & Driver</div>
                <div className="font-bold text-slate-900 mt-1">{selectedDelivery.carrier}</div>
                <div>Driver: {selectedDelivery.driver_name || 'N/A'}</div>
                <div>Vehicle: {selectedDelivery.vehicle_plate || 'VAN-204'}</div>
                <div>Contact: {selectedDelivery.driver_contact || 'N/A'}</div>
              </div>

              <div className="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div className="font-bold text-slate-500 uppercase text-[10px]">Routing & PO Info</div>
                <div className="font-bold text-teal-700 mt-1">PO Link: {selectedDelivery.po_number}</div>
                <div>Origin: {selectedDelivery.origin}</div>
                <div>Destination: {selectedDelivery.destination}</div>
                <div>Temp Profile: {selectedDelivery.temp_range || '2-8°C'}</div>
              </div>
            </div>

            {/* Item list */}
            <div>
              <table className="w-full text-left text-xs border border-slate-200 rounded-xl overflow-hidden">
                <thead className="bg-slate-100 text-slate-700 font-bold">
                  <tr>
                    <th className="p-2.5">Item Description</th>
                    <th className="p-2.5 text-center">Qty Ordered</th>
                    <th className="p-2.5 text-center">Qty Delivered</th>
                    <th className="p-2.5 text-right">QC Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {selectedDelivery.items.map((i, idx) => (
                    <tr key={idx}>
                      <td className="p-2.5 font-medium">{i.item_name}</td>
                      <td className="p-2.5 text-center font-mono">{i.quantity_ordered}</td>
                      <td className="p-2.5 text-center font-mono">
                        {selectedDelivery.status === 'delivered' ? i.quantity_ordered : 0}
                      </td>
                      <td className="p-2.5 text-right font-bold text-emerald-700">PASS (INTACT)</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {/* Signatures */}
            <div className="grid grid-cols-2 gap-8 pt-6 border-t border-slate-200 text-xs">
              <div>
                <div className="h-12 border-b border-slate-400 border-dashed"></div>
                <div className="font-bold text-slate-800 mt-1.5">Driver Dispatch Signature</div>
                <div className="text-[10px] text-slate-400">{selectedDelivery.driver_name || 'Marco Diaz'}</div>
              </div>

              <div>
                <div className="h-12 border-b border-slate-400 border-dashed"></div>
                <div className="font-bold text-slate-800 mt-1.5">Authorized QC Chemist Signature</div>
                <div className="text-[10px] text-slate-400">
                  {selectedDelivery.recipient_signature || 'Alice Williams (QC Lead Chemist)'}
                </div>
              </div>
            </div>

            <div className="flex items-center justify-end gap-2 pt-2">
              <button
                onClick={() => setShowPrintModal(false)}
                className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer"
              >
                Close
              </button>
              <button
                onClick={() => {
                  window.print();
                  setShowPrintModal(false);
                }}
                className="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow cursor-pointer flex items-center gap-1.5"
              >
                <Printer className="w-4 h-4" /> Print Document
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ===================== MODAL: DISPATCH NEW DELIVERY ===================== */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150 overflow-y-auto">
          <div className="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-7 shadow-2xl border border-slate-200 my-auto">
            <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
              <div className="flex items-center gap-2.5">
                <div className="w-10 h-10 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center font-bold border border-teal-200">
                  <Truck className="w-5 h-5" />
                </div>
                <div>
                  <h2 className="font-bold text-slate-900 text-base">Schedule New Delivery Dispatch</h2>
                  <p className="text-xs text-slate-500">Initiate cold-chain transport with GPS telemetry tracking</p>
                </div>
              </div>
              <button
                onClick={() => setShowCreateModal(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 cursor-pointer"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleCreateSubmit} className="space-y-4 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Generated Tracking #</label>
                  <input
                    type="text"
                    value={trackingNumber}
                    onChange={(e) => setTrackingNumber(e.target.value)}
                    required
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono font-bold text-teal-700"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Target Cold-Chain Profile</label>
                  <select
                    value={tempProfile}
                    onChange={(e) => setTempProfile(e.target.value as any)}
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-bold text-slate-800"
                  >
                    <option value="cold">Cold-Chain (2°C to 8°C)</option>
                    <option value="deep_freeze">Deep Freeze (-20°C to -10°C)</option>
                    <option value="ambient">Ambient Room Temp (15°C to 25°C)</option>
                    <option value="cryo">Cryogenic Dry Ice (-80°C)</option>
                  </select>
                </div>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Associated Purchase Order Link</label>
                <select
                  value={selectedPoId}
                  onChange={(e) => setSelectedPoId(Number(e.target.value))}
                  className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800"
                >
                  {purchaseOrders.map((po) => (
                    <option key={po.id} value={po.id}>
                      {po.po_number} - {po.store_name} ({po.status} - ₱{po.total_amount.toLocaleString()})
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-3 gap-3">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Carrier Name</label>
                  <input
                    type="text"
                    value={carrier}
                    onChange={(e) => setCarrier(e.target.value)}
                    required
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Driver Name</label>
                  <input
                    type="text"
                    value={driverName}
                    onChange={(e) => setDriverName(e.target.value)}
                    required
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Vehicle Plate</label>
                  <input
                    type="text"
                    value={vehiclePlate}
                    onChange={(e) => setVehiclePlate(e.target.value)}
                    required
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-mono font-bold text-slate-800"
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
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Destination Cleanroom</label>
                  <input
                    type="text"
                    value={destination}
                    onChange={(e) => setDestination(e.target.value)}
                    required
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800"
                  />
                </div>
              </div>

              <div className="grid grid-cols-3 gap-3">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Driver Contact #</label>
                  <input
                    type="text"
                    value={driverContact}
                    onChange={(e) => setDriverContact(e.target.value)}
                    required
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Est. Arrival Date</label>
                  <input
                    type="date"
                    value={estDate}
                    onChange={(e) => setEstDate(e.target.value)}
                    required
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Est. Time</label>
                  <input
                    type="time"
                    value={estTime}
                    onChange={(e) => setEstTime(e.target.value)}
                    required
                    className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800"
                  />
                </div>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Items / Manifest Summary</label>
                <input
                  type="text"
                  value={customItems}
                  onChange={(e) => setCustomItems(e.target.value)}
                  placeholder="e.g. 50x Acetone USP Grade, 20x Hydrochloric Acid"
                  className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800"
                />
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Special Handling Instructions</label>
                <textarea
                  rows={2}
                  value={deliveryNotes}
                  onChange={(e) => setDeliveryNotes(e.target.value)}
                  className="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl font-medium text-slate-800"
                />
              </div>

              <div className="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow cursor-pointer flex items-center gap-1.5"
                >
                  <Truck className="w-4 h-4" /> Dispatch Shipment & Activate GPS
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
