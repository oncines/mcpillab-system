import React, { useState, useEffect } from 'react';
import {
  getExactDeviceLocation,
  DeviceLocationResult,
  reverseGeocodeCoords,
} from '../../utils/geolocation';
import {
  MapPin,
  Navigation,
  Search,
  Check,
  RefreshCw,
  Building2,
  Compass,
  Layers,
  Sparkles,
  AlertCircle,
  X,
  ExternalLink,
  ShieldCheck,
} from 'lucide-react';

export interface LocationOption {
  id: string;
  name: string;
  address: string;
  latitude: number;
  longitude: number;
  type: 'gps' | 'facility' | 'custom' | 'search';
  distanceMeters?: number;
  tag?: string;
}

interface LocationPickerModalProps {
  isOpen: boolean;
  onClose: () => void;
  currentAddress: string;
  currentLat: number;
  currentLng: number;
  onSelectLocation: (location: { address: string; latitude: number; longitude: number }) => void;
}

export const LAB_FACILITIES: LocationOption[] = [
  {
    id: 'fac-1',
    name: 'McPIL Main Formulation Laboratory',
    address: 'McPIL Building A, 128 Science Parkway, Sector 4',
    latitude: 14.599512,
    longitude: 120.984222,
    type: 'facility',
    tag: 'Primary Lab',
  },
  {
    id: 'fac-2',
    name: 'McPIL Quality Assurance & Analytical Wing',
    address: 'McPIL Building B, Analytical Chemistry Floor 2',
    latitude: 14.599601,
    longitude: 120.98431,
    type: 'facility',
    tag: 'QC Lab',
  },
  {
    id: 'fac-3',
    name: 'McPIL Chemical Bodega & Central Receiving',
    address: 'McPIL Logistics Center, North Gate Dock 3',
    latitude: 14.59972,
    longitude: 120.98445,
    type: 'facility',
    tag: 'Warehouse',
  },
  {
    id: 'fac-4',
    name: 'McPIL Sterile Cleanroom Compounding Suite',
    address: 'McPIL Suite 101, Controlled Environment Annex',
    latitude: 14.59935,
    longitude: 120.98399,
    type: 'facility',
    tag: 'Cleanroom',
  },
  {
    id: 'fac-5',
    name: 'McPIL Cold-Chain Vaccine Storage Facility',
    address: 'McPIL Cryo-Storage Vault, Sub-Level 1',
    latitude: 14.59942,
    longitude: 120.98415,
    type: 'facility',
    tag: 'Cold Storage',
  },
  {
    id: 'fac-6',
    name: 'McPIL Administrative Headquarters & Lobby',
    address: 'McPIL Corporate Tower, Ground Floor Reception',
    latitude: 14.59948,
    longitude: 120.98418,
    type: 'facility',
    tag: 'Headquarters',
  },
];

// Utility to calculate rough distance in meters between two lat/lng pairs
function getDistanceFromLatLonInMeters(lat1: number, lon1: number, lat2: number, lon2: number): number {
  const R = 6371e3; // Earth radius in metres
  const φ1 = (lat1 * Math.PI) / 180;
  const φ2 = (lat2 * Math.PI) / 180;
  const Δφ = ((lat2 - lat1) * Math.PI) / 180;
  const Δλ = ((lon2 - lon1) * Math.PI) / 180;

  const a =
    Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
    Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return Math.round(R * c);
}

export const LocationPickerModal: React.FC<LocationPickerModalProps> = ({
  isOpen,
  onClose,
  currentAddress,
  currentLat,
  currentLng,
  onSelectLocation,
}) => {
  const [activeTab, setActiveTab] = useState<'gps' | 'facilities' | 'search'>('gps');
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<LocationOption[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [isLocating, setIsLocating] = useState(false);
  const [gpsData, setGpsData] = useState<{
    lat: number;
    lng: number;
    accuracy?: number;
    address: string;
  } | null>(null);
  const [customInput, setCustomInput] = useState(currentAddress);

  // Trigger GPS retrieval on opening GPS tab
  const fetchLiveGPS = async () => {
    setIsLocating(true);
    try {
      const loc = await getExactDeviceLocation(8000);
      setGpsData({
        lat: loc.latitude,
        lng: loc.longitude,
        accuracy: loc.accuracy || undefined,
        address: loc.address,
      });
      setCustomInput(loc.address);
    } catch (e) {
      console.warn('GPS location error:', e);
    } finally {
      setIsLocating(false);
    }
  };

  useEffect(() => {
    if (isOpen) {
      fetchLiveGPS();
    }
  }, [isOpen]);

  // Debounced search query to Nominatim
  useEffect(() => {
    if (!searchQuery.trim() || searchQuery.length < 3) {
      setSearchResults([]);
      return;
    }

    const timer = setTimeout(async () => {
      setIsSearching(true);
      try {
        const res = await fetch(
          `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(
            searchQuery
          )}&limit=6&addressdetails=1`
        );
        const data = await res.json();
        if (Array.isArray(data)) {
          const mapped: LocationOption[] = data.map((item, idx) => ({
            id: `search-${idx}-${item.place_id}`,
            name: item.name || item.display_name.split(',')[0],
            address: item.display_name,
            latitude: parseFloat(item.lat),
            longitude: parseFloat(item.lon),
            type: 'search',
          }));
          setSearchResults(mapped);
        }
      } catch (err) {
        console.warn('Search geocode error:', err);
      } finally {
        setIsSearching(false);
      }
    }, 450);

    return () => clearTimeout(timer);
  }, [searchQuery]);

  if (!isOpen) return null;

  const handlePick = (address: string, lat: number, lng: number) => {
    onSelectLocation({ address, latitude: lat, longitude: lng });
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 bg-black/80 backdrop-blur-xs flex items-center justify-center p-4">
      <div className="bg-white rounded-3xl max-w-xl w-full overflow-hidden shadow-2xl border border-slate-200 flex flex-col max-h-[90vh] animate-in zoom-in-95">
        {/* Header */}
        <div className="bg-gradient-to-r from-teal-900 via-teal-800 to-slate-900 p-4 text-white flex items-center justify-between">
          <div className="flex items-center gap-2.5">
            <div className="w-8 h-8 rounded-xl bg-teal-500/20 border border-teal-400/30 flex items-center justify-center">
              <MapPin className="w-4 h-4 text-teal-300" />
            </div>
            <div>
              <h2 className="text-sm font-bold text-white">Select Attendance Location</h2>
              <p className="text-[11px] text-teal-200/80">
                Precision GPS Geofencing & Laboratory Station Mapping
              </p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Tab Navigation */}
        <div className="flex border-b border-slate-200 bg-slate-50 text-xs font-bold text-slate-600">
          <button
            onClick={() => setActiveTab('gps')}
            className={`flex-1 py-3 px-4 flex items-center justify-center gap-2 border-b-2 transition-all ${
              activeTab === 'gps'
                ? 'border-teal-600 text-teal-700 bg-white shadow-xs'
                : 'border-transparent hover:text-slate-900'
            }`}
          >
            <Navigation className="w-3.5 h-3.5 text-teal-600" />
            Live Device GPS
          </button>
          <button
            onClick={() => setActiveTab('facilities')}
            className={`flex-1 py-3 px-4 flex items-center justify-center gap-2 border-b-2 transition-all ${
              activeTab === 'facilities'
                ? 'border-teal-600 text-teal-700 bg-white shadow-xs'
                : 'border-transparent hover:text-slate-900'
            }`}
          >
            <Building2 className="w-3.5 h-3.5 text-blue-600" />
            Lab Facilities & Stations
          </button>
          <button
            onClick={() => setActiveTab('search')}
            className={`flex-1 py-3 px-4 flex items-center justify-center gap-2 border-b-2 transition-all ${
              activeTab === 'search'
                ? 'border-teal-600 text-teal-700 bg-white shadow-xs'
                : 'border-transparent hover:text-slate-900'
            }`}
          >
            <Search className="w-3.5 h-3.5 text-purple-600" />
            Search Address
          </button>
        </div>

        {/* Content Body */}
        <div className="flex-1 overflow-y-auto p-5 space-y-4 text-xs">
          {/* TAB 1: Live GPS */}
          {activeTab === 'gps' && (
            <div className="space-y-4">
              <div className="p-4 bg-gradient-to-br from-teal-50 to-emerald-50 rounded-2xl border border-teal-200 space-y-3">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2 text-teal-900 font-extrabold text-xs">
                    <span className="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping" />
                    High-Accuracy GPS Sensor
                  </div>
                  <button
                    onClick={fetchLiveGPS}
                    disabled={isLocating}
                    className="px-3 py-1.5 bg-white hover:bg-slate-50 text-teal-700 font-bold rounded-lg border border-teal-300 shadow-xs flex items-center gap-1.5 transition-all"
                  >
                    <RefreshCw className={`w-3 h-3 ${isLocating ? 'animate-spin' : ''}`} />
                    {isLocating ? 'Detecting...' : 'Refresh GPS'}
                  </button>
                </div>

                {gpsData ? (
                  <div className="space-y-2 text-slate-700">
                    <div className="bg-white p-3 rounded-xl border border-teal-100 shadow-2xs space-y-1">
                      <span className="text-[10px] uppercase font-bold text-teal-600 tracking-wider">
                        Reverse-Geocoded Physical Address
                      </span>
                      <p className="font-bold text-slate-900 text-xs leading-relaxed">
                        {gpsData.address}
                      </p>
                    </div>

                    <div className="grid grid-cols-2 gap-2 text-[11px] font-mono">
                      <div className="bg-white p-2.5 rounded-lg border border-teal-100">
                        <span className="text-slate-400 block text-[9px] font-sans">Latitude</span>
                        <span className="font-bold text-slate-900">{gpsData.lat.toFixed(6)}°N</span>
                      </div>
                      <div className="bg-white p-2.5 rounded-lg border border-teal-100">
                        <span className="text-slate-400 block text-[9px] font-sans">Longitude</span>
                        <span className="font-bold text-slate-900">{gpsData.lng.toFixed(6)}°E</span>
                      </div>
                    </div>

                    {gpsData.accuracy !== undefined && (
                      <div className="text-[11px] text-teal-800 font-semibold flex items-center gap-1">
                        <Sparkles className="w-3.5 h-3.5 text-emerald-600" />
                        Estimated GPS Accuracy: ±{Math.round(gpsData.accuracy)} meters
                      </div>
                    )}
                  </div>
                ) : (
                  <div className="py-6 text-center text-slate-500">
                    <Navigation className="w-8 h-8 text-teal-600 mx-auto mb-2 animate-bounce" />
                    <p className="font-bold">Acquiring current GPS satellite fix...</p>
                    <p className="text-[11px] text-slate-400 mt-1">
                      Please allow browser location permissions if prompted.
                    </p>
                  </div>
                )}
              </div>

              {gpsData && (
                <button
                  onClick={() => handlePick(gpsData.address, gpsData.lat, gpsData.lng)}
                  className="w-full py-3 bg-teal-600 hover:bg-teal-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-teal-600/30 flex items-center justify-center gap-2 transition-all"
                >
                  <Check className="w-4 h-4" /> Use Current GPS Location
                </button>
              )}

              {/* Custom manual address editor */}
              <div className="pt-2 border-t border-slate-100">
                <label className="block font-bold text-slate-700 mb-1.5">
                  Fine-tune or edit address text:
                </label>
                <div className="flex gap-2">
                  <input
                    type="text"
                    value={customInput}
                    onChange={(e) => setCustomInput(e.target.value)}
                    className="flex-1 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:border-teal-500"
                    placeholder="Enter station name or physical address..."
                  />
                  <button
                    onClick={() => handlePick(customInput, currentLat, currentLng)}
                    className="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs"
                  >
                    Apply
                  </button>
                </div>
              </div>
            </div>
          )}

          {/* TAB 2: Lab Facilities */}
          {activeTab === 'facilities' && (
            <div className="space-y-2.5">
              <p className="text-[11px] text-slate-500 mb-2">
                Select an authorized company workstation or laboratory location:
              </p>

              {LAB_FACILITIES.map((fac) => {
                const dist = getDistanceFromLatLonInMeters(
                  currentLat,
                  currentLng,
                  fac.latitude,
                  fac.longitude
                );
                const isCurrent =
                  currentAddress.toLowerCase().includes(fac.name.toLowerCase()) ||
                  currentAddress.toLowerCase().includes(fac.address.toLowerCase());

                return (
                  <div
                    key={fac.id}
                    onClick={() => handlePick(`${fac.name} (${fac.address})`, fac.latitude, fac.longitude)}
                    className={`p-3.5 rounded-xl border transition-all cursor-pointer flex items-start justify-between gap-3 ${
                      isCurrent
                        ? 'bg-teal-50 border-teal-500 shadow-xs'
                        : 'bg-white hover:bg-slate-50 border-slate-200'
                    }`}
                  >
                    <div className="flex items-start gap-3">
                      <div
                        className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5 ${
                          isCurrent
                            ? 'bg-teal-600 text-white'
                            : 'bg-blue-50 text-blue-700 border border-blue-100'
                        }`}
                      >
                        <Building2 className="w-4.5 h-4.5" />
                      </div>
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="font-bold text-slate-900 text-xs">{fac.name}</span>
                          {fac.tag && (
                            <span className="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold">
                              {fac.tag}
                            </span>
                          )}
                        </div>
                        <p className="text-[11px] text-slate-500 mt-0.5">{fac.address}</p>
                        <div className="text-[10px] font-mono text-slate-400 mt-1">
                          Coords: {fac.latitude.toFixed(4)}°N, {fac.longitude.toFixed(4)}°E
                        </div>
                      </div>
                    </div>

                    <div className="text-right shrink-0">
                      {isCurrent ? (
                        <span className="inline-flex items-center gap-1 text-[11px] font-extrabold text-teal-700 bg-teal-100/80 px-2 py-1 rounded-lg">
                          <Check className="w-3 h-3" /> Active
                        </span>
                      ) : (
                        <span className="text-[10px] text-slate-400 font-semibold block">
                          ~{dist < 1000 ? `${dist}m` : `${(dist / 1000).toFixed(1)}km`} away
                        </span>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          )}

          {/* TAB 3: Search Address */}
          {activeTab === 'search' && (
            <div className="space-y-4">
              <div className="relative">
                <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Search street, clinic, hospital, district..."
                  className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500"
                />
                {isSearching && (
                  <RefreshCw className="w-3.5 h-3.5 text-teal-600 animate-spin absolute right-3.5 top-1/2 -translate-y-1/2" />
                )}
              </div>

              {/* Search Results */}
              <div className="space-y-2">
                {searchResults.length > 0 ? (
                  searchResults.map((item) => (
                    <div
                      key={item.id}
                      onClick={() => handlePick(item.address, item.latitude, item.longitude)}
                      className="p-3 bg-white hover:bg-teal-50/70 rounded-xl border border-slate-200 transition-all cursor-pointer flex items-center justify-between"
                    >
                      <div className="flex items-center gap-2.5">
                        <MapPin className="w-4 h-4 text-purple-600 shrink-0" />
                        <div>
                          <div className="font-bold text-slate-900 text-xs">{item.name}</div>
                          <div className="text-[11px] text-slate-500 truncate max-w-sm">
                            {item.address}
                          </div>
                        </div>
                      </div>
                      <span className="text-[10px] font-mono text-slate-400">
                        {item.latitude.toFixed(3)}, {item.longitude.toFixed(3)}
                      </span>
                    </div>
                  ))
                ) : searchQuery.length >= 3 && !isSearching ? (
                  <div className="p-4 text-center text-slate-400">
                    No results found for "{searchQuery}". Try a broader term.
                  </div>
                ) : (
                  <div className="p-6 bg-slate-50 rounded-2xl border border-slate-100 text-center text-slate-400 space-y-1">
                    <Search className="w-6 h-6 mx-auto text-slate-300" />
                    <p className="font-semibold text-xs text-slate-600">
                      Search any global place or landmark
                    </p>
                    <p className="text-[11px]">
                      Type at least 3 characters to search verified addresses via OpenStreetMap geocoding.
                    </p>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
          <div className="text-[11px] text-slate-500 flex items-center gap-1.5">
            <MapPin className="w-3.5 h-3.5 text-teal-600" />
            <span>
              Current: <strong className="text-slate-800 truncate max-w-[240px] inline-block align-bottom">{currentAddress}</strong>
            </span>
          </div>
          <button
            onClick={onClose}
            className="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs transition-colors"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  );
};
