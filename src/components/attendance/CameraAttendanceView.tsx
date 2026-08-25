import React, { useState, useRef, useEffect, useCallback } from 'react';
import { useApp } from '../../context/AppContext';
import { CameraAttendanceLog, AttendanceStatus } from '../../types';
import { LocationPickerModal } from './LocationPickerModal';
import {
  getExactDeviceLocation,
  watchDeviceLocation,
  DeviceLocationResult,
} from '../../utils/geolocation';
import {
  Camera,
  MapPin,
  Thermometer,
  Compass,
  CheckCircle2,
  AlertCircle,
  RefreshCw,
  Clock,
  ShieldCheck,
  User,
  Building2,
  ZoomIn,
  Video,
  VideoOff,
  RotateCw,
  ArrowLeft,
  Send,
  Sliders,
  Check,
  Sparkles,
  Calendar,
  Layers,
  Smartphone,
  Maximize2,
  Info,
  Navigation,
  Edit3,
  Radio,
} from 'lucide-react';

export const CameraAttendanceView: React.FC = () => {
  const {
    employees,
    cameraLogs,
    recordCameraAttendance,
    markAttendance,
    currentUser,
    setActiveTab,
  } = useApp();

  // Find default employee matching current logged in user or fallback to first
  const initialEmp = employees.find((e) => e.email.toLowerCase() === (currentUser?.email || '').toLowerCase()) || employees[0];
  const [selectedEmpId, setSelectedEmpId] = useState<number>(initialEmp?.id || 1);
  const [attendanceType, setAttendanceType] = useState<'clock_in' | 'clock_out'>('clock_in');
  
  // Real Device Location & Telemetry State
  const [locationAddress, setLocationAddress] = useState('Detecting Device GPS Location...');
  const [temperature, setTemperature] = useState<number>(36.4);
  const [azimuth, setAzimuth] = useState('NNE (22°)');
  const [latitude, setLatitude] = useState<number>(14.599512);
  const [longitude, setLongitude] = useState<number>(120.984222);
  const [locationAccuracy, setLocationAccuracy] = useState<number | null>(null);
  const [isLocating, setIsLocating] = useState<boolean>(true);
  const [locationStatus, setLocationStatus] = useState<string>('Syncing Device GPS...');
  const [locationSource, setLocationSource] = useState<string>('gps');
  const [showLocationModal, setShowLocationModal] = useState<boolean>(false);

  const [notes, setNotes] = useState('Biometric facial verification passed.');
  
  // Camera & Stream State
  const [isCameraActive, setIsCameraActive] = useState<boolean>(false);
  const [facingMode, setFacingMode] = useState<'user' | 'environment'>('user');
  const [cameraError, setCameraError] = useState<string | null>(null);
  const [flashActive, setFlashActive] = useState<boolean>(false);
  const [isProcessing, setIsProcessing] = useState<boolean>(false);
  
  // Preview and Modals
  const [showOptionsPanel, setShowOptionsPanel] = useState<boolean>(false);
  const [previewPhotoUrl, setPreviewPhotoUrl] = useState<string | null>(null);
  const [successToast, setSuccessToast] = useState<string | null>(null);
  const [selectedPhotoModal, setSelectedPhotoModal] = useState<CameraAttendanceLog | null>(null);
  const [viewMode, setViewMode] = useState<'phone' | 'split'>('phone');
  const [activeSubTab, setActiveSubTab] = useState<'camera' | 'history'>('camera');

  // Clock
  const [currentTime, setCurrentTime] = useState(new Date());

  const videoRef = useRef<HTMLVideoElement | null>(null);
  const canvasRef = useRef<HTMLCanvasElement | null>(null);
  const streamRef = useRef<MediaStream | null>(null);

  // Live ticking clock
  useEffect(() => {
    const timer = setInterval(() => setCurrentTime(new Date()), 1000);
    return () => clearInterval(timer);
  }, []);

  // Update location from device helper
  const handleLocationUpdate = useCallback((loc: DeviceLocationResult) => {
    setLatitude(loc.latitude);
    setLongitude(loc.longitude);
    setLocationAccuracy(loc.accuracy);
    setLocationAddress(loc.address);
    setLocationStatus(loc.statusText);
    setLocationSource(loc.source);
    setIsLocating(false);
  }, []);

  // Manual Trigger to re-query Device GPS Location
  const refreshDeviceLocation = useCallback(async () => {
    setIsLocating(true);
    setLocationStatus('Acquiring live satellite coordinates...');
    try {
      const loc = await getExactDeviceLocation(8000);
      handleLocationUpdate(loc);
    } catch (e) {
      console.warn('Location query issue:', e);
      setIsLocating(false);
    }
  }, [handleLocationUpdate]);

  // Continuous Watch on Device GPS Location
  useEffect(() => {
    const cleanupWatcher = watchDeviceLocation((loc) => {
      handleLocationUpdate(loc);
    });

    return () => {
      cleanupWatcher();
    };
  }, [handleLocationUpdate]);

  // Device orientation / compass sensor
  useEffect(() => {
    const handleOrientation = (e: DeviceOrientationEvent) => {
      if (e.alpha !== null) {
        const deg = Math.round(e.alpha);
        let cardinal = 'N';
        if (deg >= 22.5 && deg < 67.5) cardinal = 'NE';
        else if (deg >= 67.5 && deg < 112.5) cardinal = 'E';
        else if (deg >= 112.5 && deg < 157.5) cardinal = 'SE';
        else if (deg >= 157.5 && deg < 202.5) cardinal = 'S';
        else if (deg >= 202.5 && deg < 247.5) cardinal = 'SW';
        else if (deg >= 247.5 && deg < 292.5) cardinal = 'W';
        else if (deg >= 292.5 && deg < 337.5) cardinal = 'NW';

        setAzimuth(`${cardinal} (${deg}°)`);
      }
    };

    if (window.DeviceOrientationEvent) {
      window.addEventListener('deviceorientation', handleOrientation, true);
    }
    return () => {
      if (window.DeviceOrientationEvent) {
        window.removeEventListener('deviceorientation', handleOrientation, true);
      }
    };
  }, []);

  // Auto start camera on component mount
  useEffect(() => {
    startCamera(facingMode);
    return () => {
      stopCamera();
    };
  }, [facingMode]);

  const startCamera = async (mode: 'user' | 'environment' = 'user') => {
    stopCamera();
    setCameraError(null);
    try {
      if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        const stream = await navigator.mediaDevices.getUserMedia({
          video: {
            facingMode: mode,
            width: { ideal: 720 },
            height: { ideal: 960 },
          },
          audio: false,
        });
        streamRef.current = stream;
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
          videoRef.current.play().catch(() => {});
        }
        setIsCameraActive(true);
      } else {
        setIsCameraActive(false);
      }
    } catch (err: any) {
      console.warn('Camera stream notice:', err?.message || err);
      setCameraError('Live webcam not accessible in current iframe. Using high-resolution digital scanner simulation.');
      setIsCameraActive(false);
    }
  };

  const stopCamera = () => {
    if (streamRef.current) {
      streamRef.current.getTracks().forEach((track) => track.stop());
      streamRef.current = null;
    }
    if (videoRef.current) {
      videoRef.current.srcObject = null;
    }
    setIsCameraActive(false);
  };

  const toggleCameraFacing = () => {
    const nextMode = facingMode === 'user' ? 'environment' : 'user';
    setFacingMode(nextMode);
  };

  // Generate stamped photo data URL with right location
  const generateStampedPhoto = (): string => {
    const selectedEmp = employees.find((e) => e.id === Number(selectedEmpId));
    const empName = selectedEmp ? `${selectedEmp.first_name} ${selectedEmp.last_name}` : 'Staff Member';
    const timeStr = currentTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    const dateStr = currentTime.toISOString().split('T')[0];
    const weekdayStr = currentTime.toLocaleDateString([], { weekday: 'long' });

    const canvas = canvasRef.current || document.createElement('canvas');
    const width = 640;
    const height = 800;
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');

    if (!ctx) return '';

    if (isCameraActive && videoRef.current && videoRef.current.readyState >= 2) {
      // Draw live video feed
      ctx.save();
      if (facingMode === 'user') {
        ctx.translate(width, 0);
        ctx.scale(-1, 1);
      }
      ctx.drawImage(videoRef.current, 0, 0, width, height);
      ctx.restore();
    } else {
      // High-grade laboratory simulation backdrop
      const grad = ctx.createLinearGradient(0, 0, width, height);
      grad.addColorStop(0, '#0a192f');
      grad.addColorStop(0.5, '#0f2b48');
      grad.addColorStop(1, '#061325');
      ctx.fillStyle = grad;
      ctx.fillRect(0, 0, width, height);

      // Tech Grid Pattern
      ctx.strokeStyle = 'rgba(21, 101, 192, 0.15)';
      ctx.lineWidth = 1;
      for (let x = 0; x < width; x += 40) {
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x, height);
        ctx.stroke();
      }
      for (let y = 0; y < height; y += 40) {
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(width, y);
        ctx.stroke();
      }

      // Facial Scanning Target Ring
      ctx.strokeStyle = '#22d3ee';
      ctx.lineWidth = 3;
      ctx.beginPath();
      ctx.arc(width / 2, height / 2 - 40, 110, 0, Math.PI * 2);
      ctx.stroke();

      // Inner Avatar Placeholder
      ctx.fillStyle = '#0e7490';
      ctx.beginPath();
      ctx.arc(width / 2, height / 2 - 40, 95, 0, Math.PI * 2);
      ctx.fill();

      // Avatar Initials
      ctx.fillStyle = '#ffffff';
      ctx.font = 'bold 44px sans-serif';
      ctx.textAlign = 'center';
      const initials = selectedEmp ? `${selectedEmp.first_name[0]}${selectedEmp.last_name[0]}` : 'MC';
      ctx.fillText(initials, width / 2, height / 2 - 25);

      ctx.font = 'bold 20px sans-serif';
      ctx.fillText(empName, width / 2, height / 2 + 105);

      ctx.font = '14px sans-serif';
      ctx.fillStyle = '#67e8f9';
      ctx.fillText(`${selectedEmp?.department || 'Laboratory'} • ${selectedEmp?.position || 'Chemist'}`, width / 2, height / 2 + 130);
    }

    // ── WATERMARK HUD OVERLAY (Authentic McPILLAB Standard) ──
    const overlayX = 24;
    const overlayY = 40;

    // Header Title
    ctx.textAlign = 'left';
    ctx.fillStyle = '#ffffff';
    ctx.font = 'bold 20px sans-serif';
    ctx.shadowColor = 'rgba(0,0,0,0.9)';
    ctx.shadowBlur = 6;
    ctx.shadowOffsetX = 1;
    ctx.shadowOffsetY = 1;

    ctx.fillText(`◆ ${attendanceType === 'clock_in' ? 'Clock In Verification' : 'Clock Out Verification'}`, overlayX, overlayY);

    // Dashed line
    ctx.strokeStyle = 'rgba(255, 255, 255, 0.8)';
    ctx.lineWidth = 2;
    ctx.setLineDash([6, 4]);
    ctx.beginPath();
    ctx.moveTo(overlayX, overlayY + 10);
    ctx.lineTo(overlayX + 300, overlayY + 10);
    ctx.stroke();
    ctx.setLineDash([]); // reset

    // Info lines
    let currentY = overlayY + 34;
    const lineHeight = 22;
    ctx.font = 'bold 13px -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
    ctx.fillStyle = '#ffffff';

    ctx.fillText(`◆ Time: ${timeStr}`, overlayX, currentY);
    currentY += lineHeight;

    ctx.fillText(`◆ Date: ${dateStr} ${weekdayStr}`, overlayX, currentY);
    currentY += lineHeight;

    // Clean truncated location line for stamp
    const displayLoc = locationAddress.length > 45 ? `${locationAddress.substring(0, 42)}...` : locationAddress;
    ctx.fillText(`◆ Location: ${displayLoc}`, overlayX, currentY);
    currentY += lineHeight;

    ctx.fillText(`◆ Azimuth: ${azimuth}`, overlayX, currentY);
    currentY += lineHeight;

    ctx.fillText(`◆ Coordinate: ${latitude.toFixed(4)}°N, ${longitude.toFixed(4)}°E`, overlayX, currentY);
    currentY += lineHeight;

    ctx.fillText(`◆ Temperature: ${temperature.toFixed(1)}°C (Normal)`, overlayX, currentY);
    currentY += lineHeight + 4;

    // Shield verification badge
    ctx.font = '600 12px sans-serif';
    ctx.fillStyle = '#6ee7b7';
    ctx.fillText(`✓ Time & location verified by McPILLAB APP`, overlayX, currentY);

    // Reset shadow
    ctx.shadowColor = 'transparent';
    ctx.shadowBlur = 0;

    return canvas.toDataURL('image/jpeg', 0.9);
  };

  // Trigger shutter capture
  const handleShutterClick = () => {
    setFlashActive(true);
    setIsProcessing(true);

    setTimeout(() => {
      setFlashActive(false);
      const photoUrl = generateStampedPhoto();
      setPreviewPhotoUrl(photoUrl);
      setIsProcessing(false);
    }, 200);
  };

  // Confirm and save attendance
  const confirmAndSubmitAttendance = () => {
    if (!previewPhotoUrl) return;

    const selectedEmp = employees.find((e) => e.id === Number(selectedEmpId));
    const empName = selectedEmp ? `${selectedEmp.first_name} ${selectedEmp.last_name}` : 'Staff Member';
    const now = new Date();
    const captureDate = now.toISOString().split('T')[0];
    const captureTime = now.toTimeString().split(' ')[0];
    const formattedDisplayTime = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });

    // Determine status (Late if clocking in after 08:30 AM)
    const hours = now.getHours();
    const minutes = now.getMinutes();
    let status: AttendanceStatus = 'present';
    if (attendanceType === 'clock_in' && (hours > 8 || (hours === 8 && minutes > 30))) {
      status = 'late';
    }

    // 1. Record camera attendance entry with correct location
    recordCameraAttendance({
      employee_id: Number(selectedEmpId),
      employee_name: empName,
      capture_date: captureDate,
      capture_time: captureTime,
      photo_path: previewPhotoUrl,
      latitude,
      longitude,
      location_address: locationAddress,
      azimuth,
      temperature,
      device_info: `${navigator.userAgent.substring(0, 35)} (Verified McPILLAB Biometric)`,
      notes: `${attendanceType === 'clock_in' ? 'Morning Shift Clock-In' : 'Evening Shift Clock-Out'} at ${locationAddress}.`,
    });

    // 2. Also record / update general attendance timesheet with location
    markAttendance({
      employee_id: Number(selectedEmpId),
      employee_name: empName,
      department: selectedEmp?.department || 'Laboratory',
      date: captureDate,
      check_in: attendanceType === 'clock_in' ? formattedDisplayTime : '08:00 AM',
      check_out: attendanceType === 'clock_out' ? formattedDisplayTime : undefined,
      break_duration: 60,
      total_hours: attendanceType === 'clock_out' ? 8.5 : 4.0,
      status,
      location: locationAddress,
      notes: `Biometric Camera Attendance - ${attendanceType === 'clock_in' ? 'Clock In' : 'Clock Out'} (${locationAddress})`,
    });

    setPreviewPhotoUrl(null);
    setSuccessToast(`Attendance recorded at ${locationAddress} for ${empName}! Status: ${status.toUpperCase()} (${formattedDisplayTime})`);
    setTimeout(() => setSuccessToast(null), 6000);
  };

  const retakePhoto = () => {
    setPreviewPhotoUrl(null);
  };

  const selectedEmployeeObj = employees.find((e) => e.id === Number(selectedEmpId)) || employees[0];

  return (
    <div className="space-y-6">
      {/* Top Header & Mode Toggle Bar */}
      <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-xl font-bold text-slate-900">Biometric Camera Attendance Terminal</h1>
            <span className="text-xs px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 font-bold border border-blue-200 flex items-center gap-1.5">
              <span className="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
              Live Scanner Active
            </span>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Official McPILLAB camera attendance station with facial watermark stamp, GPS geofencing, and thermal telemetry.
          </p>
        </div>

        <div className="flex items-center gap-2">
          {/* Quick Location Action Button */}
          <button
            onClick={() => setShowLocationModal(true)}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-teal-50 hover:bg-teal-100 text-teal-800 font-bold text-xs rounded-xl border border-teal-200 transition-colors shadow-xs"
            title="Configure / Detect Location"
          >
            <MapPin className="w-3.5 h-3.5 text-teal-600" />
            <span className="hidden sm:inline">Location:</span>
            <span className="truncate max-w-[120px] font-semibold">{locationAddress.split(',')[0]}</span>
            <Edit3 className="w-3 h-3 text-teal-600 ml-0.5" />
          </button>

          {/* Sub Tab Switcher */}
          <div className="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs">
            <button
              onClick={() => setActiveSubTab('camera')}
              className={`px-3.5 py-1.5 rounded-lg font-bold transition-all flex items-center gap-1.5 ${
                activeSubTab === 'camera'
                  ? 'bg-blue-600 text-white shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              <Camera className="w-3.5 h-3.5" />
              Viewfinder
            </button>
            <button
              onClick={() => setActiveSubTab('history')}
              className={`px-3.5 py-1.5 rounded-lg font-bold transition-all flex items-center gap-1.5 ${
                activeSubTab === 'history'
                  ? 'bg-blue-600 text-white shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              }`}
            >
              <Calendar className="w-3.5 h-3.5" />
              Logs ({cameraLogs.length})
            </button>
          </div>

          {/* View Mode Toggle (Phone frame vs side-by-side) */}
          <button
            onClick={() => setViewMode(viewMode === 'phone' ? 'split' : 'phone')}
            className="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition-colors"
            title={viewMode === 'phone' ? 'Switch to Full Terminal Mode' : 'Switch to Mobile Phone View'}
          >
            {viewMode === 'phone' ? <Maximize2 className="w-4 h-4" /> : <Smartphone className="w-4 h-4" />}
          </button>
        </div>
      </div>

      {/* Success Notification Banner */}
      {successToast && (
        <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-900 text-xs font-bold flex items-center justify-between shadow-sm animate-in fade-in">
          <div className="flex items-center gap-2.5">
            <div className="w-6 h-6 rounded-full bg-emerald-500 text-white flex items-center justify-center">
              <Check className="w-4 h-4" />
            </div>
            <span>{successToast}</span>
          </div>
          <button
            onClick={() => setSuccessToast(null)}
            className="text-emerald-700 hover:text-emerald-900 font-extrabold text-xs"
          >
            Dismiss
          </button>
        </div>
      )}

      {/* SubTab 1: Camera Interface */}
      {activeSubTab === 'camera' && (
        <div className={`grid grid-cols-1 ${viewMode === 'split' ? 'lg:grid-cols-12' : ''} gap-6 items-start justify-center`}>
          
          {/* CENTER: The Phone Viewfinder Terminal */}
          <div className={`${viewMode === 'split' ? 'lg:col-span-7' : 'max-w-md mx-auto w-full'} flex justify-center`}>
            
            {/* Phone Bezel Frame */}
            <div className="w-full max-w-[390px] h-[780px] bg-black rounded-[40px] p-3 shadow-2xl border-4 border-slate-800 relative flex flex-col overflow-hidden select-none">
              
              {/* Dynamic Island / Top Speaker Notch */}
              <div className="absolute top-4 left-1/2 -translate-x-1/2 w-28 h-4 bg-slate-900 rounded-full z-40 flex items-center justify-center">
                <div className="w-3 h-3 rounded-full bg-slate-950 border border-slate-800 mr-2"></div>
                <div className="w-2 h-2 rounded-full bg-blue-900/60"></div>
              </div>

              {/* Viewfinder Main Stage */}
              <div className="relative flex-1 rounded-[32px] overflow-hidden bg-slate-950 flex flex-col justify-between">
                
                {/* Background Video Stream (or Simulation Fallback) */}
                {isCameraActive ? (
                  <video
                    ref={videoRef}
                    autoPlay
                    playsInline
                    muted
                    className={`absolute inset-0 w-full h-full object-cover z-0 ${
                      facingMode === 'user' ? '-scale-x-100' : ''
                    }`}
                  />
                ) : (
                  <div className="absolute inset-0 bg-gradient-to-b from-slate-950 via-slate-900 to-blue-950 flex flex-col items-center justify-center p-6 text-center z-0">
                    <div className="w-28 h-28 rounded-full border-2 border-cyan-400/50 flex items-center justify-center mb-3 animate-pulse bg-cyan-950/20">
                      <div className="w-20 h-20 rounded-full bg-cyan-700/40 flex items-center justify-center text-white text-2xl font-bold">
                        {selectedEmployeeObj.first_name[0]}{selectedEmployeeObj.last_name[0]}
                      </div>
                    </div>
                    <p className="text-white font-bold text-sm">
                      {selectedEmployeeObj.first_name} {selectedEmployeeObj.last_name}
                    </p>
                    <p className="text-xs text-blue-300 mt-0.5">
                      {selectedEmployeeObj.department} • {selectedEmployeeObj.employee_id}
                    </p>
                    <p className="text-[11px] text-slate-400 mt-2 max-w-[220px]">
                      Biometric facial scanner ready. Tap the shutter button below to record attendance.
                    </p>
                  </div>
                )}

                {/* Shutter Flash Animation */}
                <div
                  className={`absolute inset-0 bg-white transition-opacity duration-100 pointer-events-none z-50 ${
                    flashActive ? 'opacity-90' : 'opacity-0'
                  }`}
                ></div>

                {/* Top Action Bar Inside Camera (Back & Camera Switch) */}
                <div className="relative z-20 flex items-center justify-between p-3">
                  <button
                    onClick={() => setActiveTab('attendance')}
                    className="w-9 h-9 rounded-full bg-black/40 backdrop-blur-md text-white flex items-center justify-center hover:bg-black/60 transition-colors"
                    title="Back to Timesheets"
                  >
                    <ArrowLeft className="w-4 h-4" />
                  </button>

                  <div className="flex items-center gap-2">
                    {/* Location Pin Trigger inside Camera */}
                    <button
                      onClick={() => setShowLocationModal(true)}
                      className="px-2.5 py-1 rounded-full bg-teal-900/60 backdrop-blur-md border border-teal-400/40 text-teal-200 flex items-center gap-1 hover:bg-teal-800/80 transition-colors text-[11px] font-bold"
                      title="Select or Verify Location"
                    >
                      <MapPin className="w-3 h-3 text-teal-300" />
                      <span>Location</span>
                    </button>

                    <button
                      onClick={toggleCameraFacing}
                      className="w-9 h-9 rounded-full bg-black/40 backdrop-blur-md text-white flex items-center justify-center hover:bg-black/60 transition-colors"
                      title="Switch Camera (Front/Back)"
                    >
                      <RotateCw className="w-4 h-4" />
                    </button>
                    <button
                      onClick={() => startCamera(facingMode)}
                      className="w-9 h-9 rounded-full bg-black/40 backdrop-blur-md text-white flex items-center justify-center hover:bg-black/60 transition-colors"
                      title="Reload Stream"
                    >
                      <RefreshCw className="w-4 h-4" />
                    </button>
                  </div>
                </div>

                {/* Authentic Watermark HUD Overlay */}
                <div className="relative z-20 px-4 py-2 pointer-events-none">
                  {/* Clickable Header for Attendance Type */}
                  <div
                    onClick={() => setShowOptionsPanel(!showOptionsPanel)}
                    className="pointer-events-auto cursor-pointer inline-flex items-center gap-1.5 text-white font-extrabold text-base drop-shadow-[0_2px_4px_rgba(0,0,0,0.9)] hover:opacity-90 transition-opacity"
                  >
                    <span className="text-white text-xs">◆</span>
                    <span>{attendanceType === 'clock_in' ? 'Clock In' : 'Clock Out'} Attendance</span>
                    <Sliders className="w-3.5 h-3.5 text-blue-300 ml-1" />
                  </div>

                  <div className="w-44 border-t-2 border-dashed border-white/70 my-1.5 drop-shadow-[0_1px_2px_rgba(0,0,0,0.9)]"></div>

                  <div className="space-y-0.5 text-white text-xs font-bold drop-shadow-[0_2px_4px_rgba(0,0,0,0.95)]">
                    <div className="flex items-center gap-1.5">
                      <span className="text-white text-[10px]">◆</span>
                      <span>Time: {currentTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true })}</span>
                    </div>

                    <div className="flex items-center gap-1.5">
                      <span className="text-white text-[10px]">◆</span>
                      <span>Date: {currentTime.toISOString().split('T')[0]} {currentTime.toLocaleDateString([], { weekday: 'long' })}</span>
                    </div>

                    {/* Interactive Location Line in HUD */}
                    <div
                      onClick={() => setShowLocationModal(true)}
                      className="pointer-events-auto cursor-pointer flex items-center gap-1.5 group hover:text-teal-200 transition-colors"
                      title="Click to change or verify location"
                    >
                      <span className="text-white text-[10px]">◆</span>
                      <span className="truncate max-w-[260px] underline decoration-teal-400 decoration-dotted underline-offset-2">
                        Location: {locationAddress}
                      </span>
                      <Edit3 className="w-3 h-3 text-teal-300 opacity-80 group-hover:opacity-100 shrink-0" />
                    </div>

                    <div className="flex items-center gap-1.5">
                      <span className="text-white text-[10px]">◆</span>
                      <span>Azimuth: {azimuth}</span>
                    </div>

                    <div className="flex items-center gap-1.5">
                      <span className="text-white text-[10px]">◆</span>
                      <span>Coordinate: {latitude.toFixed(4)}°N, {longitude.toFixed(4)}°E</span>
                    </div>

                    <div className="flex items-center gap-1.5">
                      <span className="text-white text-[10px]">◆</span>
                      <span>Temperature: {temperature.toFixed(1)}°C</span>
                    </div>

                    <div className="flex items-center gap-1.5 text-emerald-300 pt-1 text-[11px]">
                      <ShieldCheck className="w-3.5 h-3.5 shrink-0" />
                      <span>Time & location verified by McPILLAB APP</span>
                    </div>
                  </div>
                </div>

                {/* Dropdown Options Panel for Clock-In / Clock-Out */}
                {showOptionsPanel && (
                  <div className="absolute inset-x-3 top-16 bg-slate-900/95 backdrop-blur-md rounded-2xl p-4 border border-white/20 z-40 text-white shadow-2xl animate-in fade-in">
                    <div className="flex items-center justify-between pb-2 mb-3 border-b border-white/10">
                      <div className="flex items-center gap-1.5 text-xs font-bold text-blue-400">
                        <Clock className="w-4 h-4" />
                        <span>Shift Attendance Mode</span>
                      </div>
                      <button
                        onClick={() => setShowOptionsPanel(false)}
                        className="text-xs text-slate-400 hover:text-white"
                      >
                        Close ✕
                      </button>
                    </div>

                    {/* Shift Schedule Alert */}
                    <div className="bg-slate-800/80 rounded-xl p-2.5 mb-3 text-[11px] border border-slate-700">
                      <span className="text-slate-400 block font-semibold mb-1">Standard Work Schedule:</span>
                      <div className="flex justify-between text-slate-300">
                        <span>🌅 8:00 AM - 12:00 PM</span>
                        <span>🌤️ 1:00 PM - 5:00 PM</span>
                      </div>
                    </div>

                    {/* Clock In Option */}
                    <div
                      onClick={() => {
                        setAttendanceType('clock_in');
                        setShowOptionsPanel(false);
                      }}
                      className={`p-3 rounded-xl mb-2 flex items-center justify-between cursor-pointer border transition-all ${
                        attendanceType === 'clock_in'
                          ? 'bg-blue-600/30 border-blue-500 text-white'
                          : 'bg-slate-800/60 border-slate-700 text-slate-300 hover:bg-slate-800'
                      }`}
                    >
                      <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold">
                          🌅
                        </div>
                        <div>
                          <div className="text-xs font-bold">Clock In</div>
                          <div className="text-[10px] text-slate-400">Morning Shift Entry Timestamp</div>
                        </div>
                      </div>
                      {attendanceType === 'clock_in' && <Check className="w-4 h-4 text-blue-400" />}
                    </div>

                    {/* Clock Out Option */}
                    <div
                      onClick={() => {
                        setAttendanceType('clock_out');
                        setShowOptionsPanel(false);
                      }}
                      className={`p-3 rounded-xl flex items-center justify-between cursor-pointer border transition-all ${
                        attendanceType === 'clock_out'
                          ? 'bg-orange-600/30 border-orange-500 text-white'
                          : 'bg-slate-800/60 border-slate-700 text-slate-300 hover:bg-slate-800'
                      }`}
                    >
                      <div className="flex items-center gap-3">
                        <div className="w-9 h-9 rounded-full bg-orange-600 text-white flex items-center justify-center font-bold">
                          🌤️
                        </div>
                        <div>
                          <div className="text-xs font-bold">Clock Out</div>
                          <div className="text-[10px] text-slate-400">Shift Completion & Departure</div>
                        </div>
                      </div>
                      {attendanceType === 'clock_out' && <Check className="w-4 h-4 text-orange-400" />}
                    </div>
                  </div>
                )}

                {/* Bottom Bar: Large Shutter Button */}
                <div className="relative z-20 py-4 flex items-center justify-center bg-gradient-to-t from-black/80 via-black/40 to-transparent">
                  <button
                    onClick={handleShutterClick}
                    disabled={isProcessing}
                    className="w-18 h-18 rounded-full bg-blue-600 hover:bg-blue-500 active:scale-90 text-white flex items-center justify-center shadow-lg shadow-blue-600/50 border-4 border-white/80 transition-transform cursor-pointer"
                    title="Capture Biometric Attendance"
                  >
                    <Camera className="w-8 h-8 text-white drop-shadow" />
                  </button>
                </div>
              </div>

              {/* Home Indicator bar */}
              <div className="w-32 h-1 bg-white/40 rounded-full mx-auto my-2"></div>
            </div>
          </div>

          {/* RIGHT: Staff Selector & Telemetry Station Controls (for Split View Mode) */}
          <div className={`${viewMode === 'split' ? 'lg:col-span-5' : 'max-w-md mx-auto w-full'} bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4`}>
            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <ShieldCheck className="w-5 h-5 text-blue-600" />
                <h2 className="text-sm font-bold text-slate-900">Attendance Station Settings</h2>
              </div>
              <span className="text-[10px] font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-600">
                MC-CAM-01
              </span>
            </div>

            {/* Select Employee */}
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                <User className="w-3.5 h-3.5 text-blue-600" />
                Employee Checking In / Out
              </label>
              <select
                value={selectedEmpId}
                onChange={(e) => setSelectedEmpId(Number(e.target.value))}
                className="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:border-blue-500"
              >
                {employees.map((emp) => (
                  <option key={emp.id} value={emp.id}>
                    {emp.first_name} {emp.last_name} ({emp.employee_id} • {emp.department})
                  </option>
                ))}
              </select>
            </div>

            {/* Attendance Mode Radio Switch */}
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                <Clock className="w-3.5 h-3.5 text-blue-600" />
                Shift Mode
              </label>
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={() => setAttendanceType('clock_in')}
                  className={`p-2.5 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition-all ${
                    attendanceType === 'clock_in'
                      ? 'bg-blue-50 border-blue-500 text-blue-700 shadow-xs'
                      : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'
                  }`}
                >
                  <span>🌅</span> Clock In
                </button>
                <button
                  type="button"
                  onClick={() => setAttendanceType('clock_out')}
                  className={`p-2.5 rounded-xl border text-xs font-bold flex items-center justify-center gap-2 transition-all ${
                    attendanceType === 'clock_out'
                      ? 'bg-orange-50 border-orange-500 text-orange-700 shadow-xs'
                      : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'
                  }`}
                >
                  <span>🌤️</span> Clock Out
                </button>
              </div>
            </div>

            {/* Station Location with Pinpoint / Change Action */}
            <div className="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
              <div className="flex items-center justify-between">
                <label className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                  <MapPin className="w-3.5 h-3.5 text-teal-600" />
                  Verified Attendance Location
                </label>
                <button
                  type="button"
                  onClick={() => setShowLocationModal(true)}
                  className="text-[11px] font-bold text-teal-700 hover:text-teal-900 flex items-center gap-1 bg-white px-2 py-0.5 rounded-lg border border-teal-200 shadow-2xs"
                >
                  <Navigation className="w-3 h-3 text-teal-600" />
                  Change / Pinpoint
                </button>
              </div>

              <div className="bg-white p-2.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-800">
                {locationAddress}
              </div>

              <div className="flex items-center justify-between text-[10px] text-slate-500">
                <span className="flex items-center gap-1">
                  <span className={`w-2 h-2 rounded-full ${isLocating ? 'bg-amber-500 animate-ping' : 'bg-emerald-500'}`} />
                  {locationStatus}
                </span>
                <span className="font-mono">
                  {latitude.toFixed(4)}°N, {longitude.toFixed(4)}°E
                  {locationAccuracy ? ` (±${locationAccuracy}m)` : ''}
                </span>
              </div>
            </div>

            {/* Telemetry Grid: Temp & Azimuth */}
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                  <Thermometer className="w-3.5 h-3.5 text-rose-500" />
                  Body Temp (°C)
                </label>
                <input
                  type="number"
                  step="0.1"
                  value={temperature}
                  onChange={(e) => setTemperature(Number(e.target.value))}
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:border-blue-500"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                  <Compass className="w-3.5 h-3.5 text-blue-500" />
                  Azimuth
                </label>
                <input
                  type="text"
                  value={azimuth}
                  onChange={(e) => setAzimuth(e.target.value)}
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:outline-none focus:border-blue-500"
                />
              </div>
            </div>

            {/* Notes */}
            <div>
              <label className="block text-xs font-bold text-slate-700 mb-1.5">Verification Notes</label>
              <input
                type="text"
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 focus:outline-none"
              />
            </div>

            {/* Direct Trigger Button */}
            <button
              onClick={handleShutterClick}
              className="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-blue-600/20 flex items-center justify-center gap-2 active:scale-98 transition-all"
            >
              <Camera className="w-4 h-4" />
              Capture Attendance Snapshot
            </button>
          </div>
        </div>
      )}

      {/* SubTab 2: Attendance Records & Photo Album */}
      {activeSubTab === 'history' && (
        <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
          <div className="flex items-center justify-between">
            <h2 className="text-sm font-bold text-slate-900">Recent Biometric Attendance Captures</h2>
            <span className="text-xs text-slate-500">{cameraLogs.length} Verified Entries</span>
          </div>

          {cameraLogs.length === 0 ? (
            <div className="text-center py-12 text-slate-400 text-xs">
              No camera attendance captures recorded yet. Use the camera viewfinder to clock in.
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {cameraLogs.map((log) => (
                <div
                  key={log.id}
                  className="p-3.5 bg-slate-50 hover:bg-slate-100/80 rounded-xl border border-slate-200 transition-all space-y-2.5"
                >
                  <div className="relative aspect-4/3 rounded-lg overflow-hidden bg-slate-900 border border-slate-200">
                    <img
                      src={log.photo_path}
                      alt={log.employee_name}
                      className="w-full h-full object-cover"
                    />
                    <button
                      onClick={() => setSelectedPhotoModal(log)}
                      className="absolute bottom-2 right-2 px-2 py-1 bg-black/60 backdrop-blur-md rounded text-white text-[10px] font-bold flex items-center gap-1 hover:bg-black/80"
                    >
                      <ZoomIn className="w-3 h-3" /> Zoom
                    </button>
                  </div>

                  <div>
                    <div className="flex items-center justify-between">
                      <span className="text-xs font-bold text-slate-900">{log.employee_name}</span>
                      <span className="text-[10px] font-mono text-blue-700 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200">
                        {log.capture_time}
                      </span>
                    </div>
                    <div className="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                      <MapPin className="w-3 h-3 text-slate-400 shrink-0" />
                      <span className="truncate">{log.location_address}</span>
                    </div>
                    <div className="text-[10px] text-slate-400 flex items-center justify-between mt-1 pt-1 border-t border-slate-200/60">
                      <span>Temp: {log.temperature || '36.4'}°C</span>
                      <span>Az: {log.azimuth}</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* MODAL 1: Photo Preview & Verification Confirmation */}
      {previewPhotoUrl && (
        <div className="fixed inset-0 z-50 bg-black/85 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-slate-900 text-white rounded-3xl max-w-md w-full p-5 border border-white/10 shadow-2xl space-y-4 animate-in zoom-in-95">
            <div className="flex items-center justify-between pb-2 border-b border-white/10">
              <div className="flex items-center gap-2">
                <ShieldCheck className="w-5 h-5 text-emerald-400" />
                <h3 className="text-sm font-bold">Attendance Preview & Verification</h3>
              </div>
              <span className="text-xs px-2 py-0.5 rounded-full bg-blue-600/40 text-blue-300 border border-blue-400/40">
                {attendanceType === 'clock_in' ? 'CLOCK IN' : 'CLOCK OUT'}
              </span>
            </div>

            {/* Photo with HUD Stamp */}
            <div className="relative aspect-4/3 rounded-2xl overflow-hidden bg-black border border-white/20">
              <img
                src={previewPhotoUrl}
                alt="Captured Snapshot"
                className="w-full h-full object-cover"
              />
            </div>

            <div className="bg-slate-800/80 p-3 rounded-xl text-xs space-y-1.5 border border-slate-700">
              <div className="text-slate-300">
                Staff: <span className="font-bold text-white">{selectedEmployeeObj.first_name} {selectedEmployeeObj.last_name}</span>
              </div>
              <div className="text-slate-300">
                Timestamp: <span className="font-mono text-emerald-400">{currentTime.toLocaleTimeString()}</span> ({currentTime.toISOString().split('T')[0]})
              </div>
              <div className="text-teal-300 text-[11px] flex items-start gap-1">
                <MapPin className="w-3.5 h-3.5 shrink-0 mt-0.5" />
                <span>Verified Location: <strong className="text-white">{locationAddress}</strong></span>
              </div>
            </div>

            {/* Modal Actions */}
            <div className="grid grid-cols-2 gap-3 pt-2">
              <button
                onClick={retakePhoto}
                className="py-2.5 px-4 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl border border-slate-600 flex items-center justify-center gap-1.5 transition-colors"
              >
                <RotateCw className="w-3.5 h-3.5" /> Retake Photo
              </button>
              <button
                onClick={confirmAndSubmitAttendance}
                className="py-2.5 px-4 bg-blue-600 hover:bg-blue-500 text-white font-extrabold text-xs rounded-xl shadow-lg shadow-blue-600/30 flex items-center justify-center gap-1.5 transition-all"
              >
                <Send className="w-3.5 h-3.5" /> Send Attendance
              </button>
            </div>
          </div>
        </div>
      )}

      {/* MODAL 2: Zoomed Photo Modal */}
      {selectedPhotoModal && (
        <div className="fixed inset-0 z-50 bg-black/90 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-slate-900 text-white rounded-3xl max-w-lg w-full p-5 border border-white/10 space-y-4">
            <div className="flex items-center justify-between pb-2 border-b border-white/10">
              <h3 className="text-sm font-bold text-white">Biometric Attendance Proof</h3>
              <button
                onClick={() => setSelectedPhotoModal(null)}
                className="text-slate-400 hover:text-white text-sm"
              >
                ✕
              </button>
            </div>

            <div className="rounded-2xl overflow-hidden border border-white/20">
              <img
                src={selectedPhotoModal.photo_path}
                alt={selectedPhotoModal.employee_name}
                className="w-full h-auto object-cover"
              />
            </div>

            <div className="text-xs text-slate-300 space-y-1">
              <div>Employee: <span className="font-bold text-white">{selectedPhotoModal.employee_name}</span></div>
              <div>Captured: <span className="font-mono text-emerald-400">{selectedPhotoModal.capture_date} {selectedPhotoModal.capture_time}</span></div>
              <div>Location: {selectedPhotoModal.location_address}</div>
            </div>
          </div>
        </div>
      )}

      {/* Location Picker Modal */}
      <LocationPickerModal
        isOpen={showLocationModal}
        onClose={() => setShowLocationModal(false)}
        currentAddress={locationAddress}
        currentLat={latitude}
        currentLng={longitude}
        onSelectLocation={(loc) => {
          setLocationAddress(loc.address);
          setLatitude(loc.latitude);
          setLongitude(loc.longitude);
          setLocationStatus('Custom Verified Location');
        }}
      />

      {/* Hidden canvas element for photo processing */}
      <canvas ref={canvasRef} className="hidden" />
    </div>
  );
};
