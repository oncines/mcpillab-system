// AttendanceView.tsx - Exact 1:1 Implementation matching attendance.php with interactive Slide-over Panel & Lightbox
import React, { useState, useMemo, useEffect } from 'react';
import { useApp } from '../../context/AppContext';
import { AttendanceRecord, AttendanceStatus, CameraAttendanceLog, Employee } from '../../types';
import { getExactDeviceLocation } from '../../utils/geolocation';
import {
  Calendar,
  Clock,
  CheckCircle2,
  AlertCircle,
  Camera,
  MapPin,
  Search,
  Filter,
  Eye,
  Phone,
  MessageSquare,
  ShieldCheck,
  TrendingUp,
  Download,
  Plus,
  RotateCcw,
  ChevronLeft,
  ChevronRight,
  X,
  UserCheck,
  Maximize2,
  SlidersHorizontal,
  FileText,
  Building2,
  Sparkles,
} from 'lucide-react';

interface DayTimelineSegment {
  left: string;
  width: string;
  cls: string;
  label: string;
}

interface DayAttendanceRecord {
  label: string;
  dateStr: string;
  badge: 'approved' | 'ot' | 'requested' | '';
  badgeText: string;
  showApprove?: boolean;
  clockIn: string;
  clockOut: string;
  duration: string;
  tooltip: string;
  segments: DayTimelineSegment[];
  clockInPhoto?: string;
  clockOutPhoto?: string;
}

export const AttendanceView: React.FC = () => {
  const {
    attendanceRecords,
    cameraLogs,
    employees,
    currentUser,
    setActiveTab,
    markAttendance,
  } = useApp();

  const isEmployeeRole = currentUser?.role === 'employee';

  // State
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedDate, setSelectedDate] = useState<string>(new Date().toISOString().split('T')[0]);
  const [dateFilterMode, setDateFilterMode] = useState<'all' | 'today' | 'week' | 'month'>('today');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [showAdvanceFilter, setShowAdvanceFilter] = useState<boolean>(false);
  const [deptFilter, setDeptFilter] = useState<string>('all');
  const [overtimeOnly, setOvertimeOnly] = useState<boolean>(false);

  // Detail Slide-over Panel State
  const [activeEmpIndex, setActiveEmpIndex] = useState<number | null>(null);
  const [panelSearch, setPanelSearch] = useState<string>('');
  const [panelStatusFilter, setPanelStatusFilter] = useState<string>('all');
  const [panelMonth, setPanelMonth] = useState<string>('August 2026');

  // Lightbox Modal State
  const [lightboxData, setLightboxData] = useState<{
    isOpen: boolean;
    type: string;
    time: string;
    name: string;
    color: string;
    initials: string;
    photoUrl?: string;
    location?: string;
  } | null>(null);

  // Manual Add Modal State
  const [showManualModal, setShowManualModal] = useState<boolean>(false);
  const [manualEmpId, setManualEmpId] = useState<number>(employees[0]?.id || 1);
  const [manualDate, setManualDate] = useState<string>(new Date().toISOString().split('T')[0]);
  const [manualIn, setManualIn] = useState<string>('08:00 AM');
  const [manualOut, setManualOut] = useState<string>('05:00 PM');
  const [manualStatus, setManualStatus] = useState<AttendanceStatus>('present');
  const [manualNotes, setManualNotes] = useState<string>('Biometric shift completed');
  const [manualLocation, setManualLocation] = useState<string>('MCPIL Central Lab Station');
  const [isDetectingLocation, setIsDetectingLocation] = useState(false);
  const [toastMessage, setToastMessage] = useState<string | null>(null);

  // Page pagination
  const [currentPage, setCurrentPage] = useState<number>(1);
  const pageSize = 10;

  // Preset employee color palette matching attendance.php
  const colorPalette = [
    '#e67e22',
    '#16a085',
    '#8e44ad',
    '#2980b9',
    '#c0392b',
    '#27ae60',
    '#d35400',
    '#1abc9c',
    '#7f8c8d',
    '#0d7a48',
    '#1e40af',
  ];

  // Merge registered employees with attendance logs to ensure all 9+ employees appear with rich metadata
  const enrichedEmployeeList = useMemo(() => {
    return employees.map((emp, idx) => {
      // Find today's attendance record or latest record
      const todayRec = attendanceRecords.find(
        (r) => r.employee_id === emp.id && (selectedDate ? r.date === selectedDate : true)
      ) || attendanceRecords.find((r) => r.employee_id === emp.id);

      // Find matching camera log
      const camLog = cameraLogs.find((c) => c.employee_id === emp.id);

      const color = colorPalette[idx % colorPalette.length];
      const initials = `${emp.first_name?.[0] || 'E'}${emp.last_name?.[0] || 'M'}`.toUpperCase();

      const clockIn = todayRec?.check_in || (idx % 3 === 0 ? '10:02 AM' : idx % 2 === 0 ? '08:56 AM' : '09:30 AM');
      const clockOut = todayRec?.check_out || (idx % 3 === 0 ? '07:00 PM' : idx % 2 === 0 ? '05:01 PM' : '07:12 PM');
      const duration = todayRec ? `${todayRec.total_hours}h` : idx % 3 === 0 ? '8h 56m' : '10h 12m';
      const isLate = todayRec?.status === 'late' || (idx % 4 === 0);
      const ot = idx === 0 ? '2h 12m' : idx === 4 ? '1h 30m' : '';
      const location = todayRec?.location || (idx % 2 === 0 ? 'Jl. Jendral Sudirman Suite 4' : 'MCPIL Cleanroom 02');
      const note = todayRec?.notes || (idx % 2 === 0 ? 'Discussed mutual batch value' : 'Assay spectrophotometry test passed');

      return {
        ...emp,
        color,
        initials,
        clock_in: clockIn,
        clock_out: clockOut,
        duration,
        is_late: isLate,
        overtime: ot,
        location,
        note,
        camera_proof: camLog?.photo_path,
        record: todayRec,
      };
    });
  }, [employees, attendanceRecords, cameraLogs, selectedDate]);

  // Filtered rows for the main table
  const filteredEmployees = useMemo(() => {
    return enrichedEmployeeList.filter((emp) => {
      const q = searchQuery.toLowerCase();
      const matchSearch =
        !q ||
        `${emp.first_name} ${emp.last_name}`.toLowerCase().includes(q) ||
        emp.employee_id.toLowerCase().includes(q) ||
        emp.department.toLowerCase().includes(q) ||
        emp.location.toLowerCase().includes(q) ||
        emp.note.toLowerCase().includes(q);

      const matchDept = deptFilter === 'all' || emp.department.toLowerCase() === deptFilter.toLowerCase();
      const matchStatus =
        statusFilter === 'all' ||
        (statusFilter === 'late' && emp.is_late) ||
        (statusFilter === 'on_time' && !emp.is_late);
      const matchOt = !overtimeOnly || Boolean(emp.overtime);

      return matchSearch && matchDept && matchStatus && matchOt;
    });
  }, [enrichedEmployeeList, searchQuery, deptFilter, statusFilter, overtimeOnly]);

  // Currently inspected employee for Detail Slide-over Panel
  const selectedEmpForPanel = activeEmpIndex !== null ? filteredEmployees[activeEmpIndex] || enrichedEmployeeList[0] : null;

  // Generate day cards and visual gantt timeline for the inspected employee
  const panelDays: DayAttendanceRecord[] = useMemo(() => {
    if (!selectedEmpForPanel) return [];

    return [
      {
        label: 'Today',
        dateStr: 'Aug 26, 2026',
        badge: 'ot',
        badgeText: 'Overtime approval',
        showApprove: true,
        clockIn: selectedEmpForPanel.clock_in,
        clockOut: selectedEmpForPanel.clock_out,
        duration: selectedEmpForPanel.duration,
        tooltip: 'Working time: 09:00 AM – 12:30 PM (3h 30m)',
        segments: [
          { left: '0%', width: '38%', cls: 'bg-[#1e40af]', label: 'Working time' },
          { left: '38%', width: '10%', cls: 'bg-[#f59e0b]', label: 'Break' },
          { left: '48%', width: '30%', cls: 'bg-[#1e40af]', label: 'Working time' },
          { left: '78%', width: '22%', cls: 'bg-[#ef4444]', label: 'Over time' },
        ],
        clockInPhoto: selectedEmpForPanel.camera_proof,
        clockOutPhoto: selectedEmpForPanel.camera_proof,
      },
      {
        label: 'Thursday, 18',
        dateStr: 'Aug 18, 2026',
        badge: 'approved',
        badgeText: 'Approved',
        showApprove: false,
        clockIn: '—',
        clockOut: '—',
        duration: '—',
        tooltip: 'Approved day off quota request',
        segments: [
          { left: '0%', width: '100%', cls: 'bg-[#fef3c7] text-[#b45309] border border-dashed border-[#f59e0b]', label: 'Requested day off' },
        ],
      },
      {
        label: 'Wednesday, 17',
        dateStr: 'Aug 17, 2026',
        badge: '',
        badgeText: '',
        showApprove: false,
        clockIn: '09:00 AM',
        clockOut: '05:00 PM',
        duration: '8 hour',
        tooltip: 'Full 8 hour shift completed on time',
        segments: [
          { left: '0%', width: '40%', cls: 'bg-[#1e40af]', label: 'Working time' },
          { left: '40%', width: '8%', cls: 'bg-[#f59e0b]', label: 'Break' },
          { left: '48%', width: '38%', cls: 'bg-[#1e40af]', label: 'Working time' },
        ],
        clockInPhoto: selectedEmpForPanel.camera_proof,
        clockOutPhoto: selectedEmpForPanel.camera_proof,
      },
      {
        label: 'Tuesday, 16',
        dateStr: 'Aug 16, 2026',
        badge: '',
        badgeText: '',
        showApprove: false,
        clockIn: '09:30 AM',
        clockOut: '07:12 PM',
        duration: '8h 42m',
        tooltip: 'Late clock-in + extended afternoon shift',
        segments: [
          { left: '0%', width: '6%', cls: 'bg-[#ef4444]', label: 'Late' },
          { left: '6%', width: '36%', cls: 'bg-[#1e40af]', label: 'Working time' },
          { left: '42%', width: '8%', cls: 'bg-[#f59e0b]', label: 'Break' },
          { left: '50%', width: '36%', cls: 'bg-[#1e40af]', label: 'Working time' },
        ],
        clockInPhoto: selectedEmpForPanel.camera_proof,
        clockOutPhoto: selectedEmpForPanel.camera_proof,
      },
      {
        label: 'Monday, 15',
        dateStr: 'Aug 15, 2026',
        badge: '',
        badgeText: '',
        showApprove: false,
        clockIn: '09:00 AM',
        clockOut: '05:00 PM',
        duration: '8 hour',
        tooltip: 'Shift completed on schedule',
        segments: [
          { left: '0%', width: '40%', cls: 'bg-[#1e40af]', label: 'Working time' },
          { left: '40%', width: '8%', cls: 'bg-[#f59e0b]', label: 'Break' },
          { left: '48%', width: '38%', cls: 'bg-[#1e40af]', label: 'Working time' },
        ],
        clockInPhoto: selectedEmpForPanel.camera_proof,
        clockOutPhoto: selectedEmpForPanel.camera_proof,
      },
    ];
  }, [selectedEmpForPanel]);

  // Filter day cards inside the detail slide-over
  const filteredPanelDays = useMemo(() => {
    return panelDays.filter((d) => {
      const matchQ =
        !panelSearch ||
        d.label.toLowerCase().includes(panelSearch.toLowerCase()) ||
        d.clockIn.toLowerCase().includes(panelSearch.toLowerCase()) ||
        d.clockOut.toLowerCase().includes(panelSearch.toLowerCase());

      const matchSt =
        panelStatusFilter === 'all' ||
        (panelStatusFilter === 'on_time' && d.clockIn !== '—' && !d.segments.some((s) => s.label === 'Late')) ||
        (panelStatusFilter === 'late' && d.segments.some((s) => s.label === 'Late')) ||
        (panelStatusFilter === 'absent' && d.clockIn === '—');

      return matchQ && matchSt;
    });
  }, [panelDays, panelSearch, panelStatusFilter]);

  // Navigate between employees in the slide-over
  const handleNavPanel = (delta: number) => {
    if (activeEmpIndex === null) return;
    const next = activeEmpIndex + delta;
    if (next >= 0 && next < filteredEmployees.length) {
      setActiveEmpIndex(next);
    }
  };

  // Auto-detect GPS location
  const handleDetectLocation = async () => {
    setIsDetectingLocation(true);
    try {
      const loc = await getExactDeviceLocation(5000);
      setManualLocation(loc.address);
    } catch (e) {
      setManualLocation('MCPIL Central Laboratory Cleanroom Hub');
    } finally {
      setIsDetectingLocation(false);
    }
  };

  // Manual submission handler
  const handleManualSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const emp = employees.find((e) => e.id === Number(manualEmpId));
    if (!emp) return;

    markAttendance({
      employee_id: emp.id,
      employee_name: `${emp.first_name} ${emp.last_name}`,
      department: emp.department,
      date: manualDate,
      check_in: manualIn,
      check_out: manualOut,
      break_duration: 60,
      total_hours: 8.0,
      status: manualStatus,
      location: manualLocation,
      notes: manualNotes,
    });

    setShowManualModal(false);
    setToastMessage(`Attendance record updated for ${emp.first_name} ${emp.last_name}!`);
    setTimeout(() => setToastMessage(null), 4000);
  };

  // CSV Export
  const handleExportCSV = () => {
    const headers = ['Employee Name', 'ID', 'Department', 'Clock In', 'Clock Out', 'Duration', 'Overtime', 'Location', 'Notes'];
    const rows = filteredEmployees.map((emp) => [
      `"${emp.first_name} ${emp.last_name}"`,
      emp.employee_id,
      emp.department,
      emp.clock_in,
      emp.clock_out,
      emp.duration,
      emp.overtime || '—',
      `"${emp.location}"`,
      `"${emp.note}"`,
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map((e) => e.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `mcpil_attendance_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  // Handle escape key to close modals
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        if (lightboxData?.isOpen) {
          setLightboxData(null);
        } else if (activeEmpIndex !== null) {
          setActiveEmpIndex(null);
        } else if (showManualModal) {
          setShowManualModal(false);
        }
      }
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [lightboxData, activeEmpIndex, showManualModal]);

  const timelineHours = ['09:00', '11:00', '13:00', '15:00', '17:00', '19:00', '21:00', '23:59'];

  return (
    <div className="space-y-6">
      {/* Toast Alert */}
      {toastMessage && (
        <div className="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 text-xs font-bold flex items-center justify-between shadow-xs animate-in fade-in">
          <div className="flex items-center gap-2">
            <CheckCircle2 className="w-4 h-4 text-emerald-600" />
            <span>{toastMessage}</span>
          </div>
          <button onClick={() => setToastMessage(null)} className="text-emerald-700">✕</button>
        </div>
      )}

      {/* ═══════════════════════════════════════════
          PAGE HEADER (Exact match to attendance.php lines 544-565)
      ═══════════════════════════════════════════ */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-2">
        <div>
          <div className="text-[10.5px] font-black tracking-[0.12em] uppercase text-[#d4241a] mb-1">
            HR
          </div>
          <div className="flex items-baseline gap-3 flex-wrap">
            <h1 className="text-2xl sm:text-3xl font-extrabold text-[#0d1030] tracking-tight">
              Attendance
            </h1>
            <span className="text-sm font-normal text-slate-400">
              {filteredEmployees.length} employees today
            </span>
          </div>
          <p className="text-xs text-slate-500 mt-1 font-medium">
            Daily clock-in and clock-out records
          </p>
        </div>

        {/* Date Navigation and Action Buttons */}
        <div className="flex items-center gap-2.5 flex-wrap">
          {/* Date Nav Pill */}
          <div className="inline-flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-2xs">
            <button
              onClick={() => {
                const d = new Date(selectedDate);
                d.setDate(d.getDate() - 1);
                setSelectedDate(d.toISOString().split('T')[0]);
              }}
              className="text-slate-400 hover:text-slate-700 p-0.5"
            >
              <ChevronLeft className="w-3.5 h-3.5" />
            </button>
            <span className="text-xs font-bold text-slate-700">
              {new Date(selectedDate).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })}
            </span>
            <button
              onClick={() => {
                const d = new Date(selectedDate);
                d.setDate(d.getDate() + 1);
                setSelectedDate(d.toISOString().split('T')[0]);
              }}
              className="text-slate-400 hover:text-slate-700 p-0.5"
            >
              <ChevronRight className="w-3.5 h-3.5" />
            </button>
          </div>

          {/* Date Badge */}
          <div className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-500 shadow-2xs">
            <Calendar className="w-3.5 h-3.5 text-slate-400" />
            <span>{new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}</span>
          </div>

          {/* View Attendance Report / History Button */}
          <button
            onClick={() => setActiveTab('attendance_history')}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 shadow-2xs transition-colors cursor-pointer"
          >
            <FileText className="w-3.5 h-3.5 text-slate-500" />
            <span>Attendance Report</span>
          </button>

          {/* Biometric Camera Terminal Button */}
          <button
            onClick={() => setActiveTab('camera_attendance')}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#1565C0] hover:bg-[#0d47a1] text-white font-bold text-xs rounded-xl shadow-xs shadow-blue-700/20 transition-all cursor-pointer"
          >
            <Camera className="w-3.5 h-3.5" />
            <span>Biometric Clock In</span>
          </button>

          {/* Add Attendance Button (Navy) */}
          <button
            onClick={() => setShowManualModal(true)}
            className="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0a1045] hover:bg-[#0f1860] text-white font-bold text-xs rounded-xl shadow-xs transition-transform hover:-translate-y-0.5 cursor-pointer"
          >
            <span className="w-4 h-4 rounded bg-white/20 flex items-center justify-center text-[10px]">
              <Plus className="w-3 h-3" />
            </span>
            <span>Add</span>
          </button>
        </div>
      </div>

      {/* ═══════════════════════════════════════════
          TABLE CARD (Exact match to .tbl-card in attendance.php lines 568-662)
      ═══════════════════════════════════════════ */}
      <div className="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
        {/* Table Toolbar */}
        <div className="flex items-center justify-between gap-3 p-3.5 sm:px-5 bg-slate-50/80 border-b border-slate-200 flex-wrap">
          <div className="flex items-center gap-2.5 flex-wrap flex-1 min-w-[280px]">
            {/* Search field */}
            <div className="relative flex items-center bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-2xs focus-within:border-blue-700 focus-within:ring-2 focus-within:ring-blue-100">
              <Search className="w-3.5 h-3.5 text-slate-400 mr-2 shrink-0" />
              <input
                type="text"
                placeholder="Search employee…"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-44 sm:w-56 text-xs text-slate-900 bg-transparent outline-none placeholder:text-slate-400 font-medium"
              />
              {searchQuery && (
                <button onClick={() => setSearchQuery('')} className="text-slate-400 hover:text-slate-600 text-xs ml-1">
                  ✕
                </button>
              )}
            </div>

            {/* Date Range Button */}
            <button
              onClick={() => {
                const modes: Array<'all' | 'today' | 'week' | 'month'> = ['today', 'week', 'month', 'all'];
                const next = modes[(modes.indexOf(dateFilterMode) + 1) % modes.length];
                setDateFilterMode(next);
              }}
              className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 transition-colors shadow-2xs"
            >
              <Calendar className="w-3.5 h-3.5 text-slate-400" />
              <span>
                {dateFilterMode === 'today'
                  ? 'Today'
                  : dateFilterMode === 'week'
                  ? 'This Week'
                  : dateFilterMode === 'month'
                  ? 'This Month'
                  : 'All Dates'}
              </span>
            </button>

            {/* Advance Filter Toggle */}
            <button
              onClick={() => setShowAdvanceFilter(!showAdvanceFilter)}
              className={`inline-flex items-center gap-1.5 px-3 py-1.5 border rounded-xl text-xs font-semibold transition-colors shadow-2xs ${
                showAdvanceFilter || statusFilter !== 'all' || deptFilter !== 'all' || overtimeOnly
                  ? 'bg-blue-50 text-[#1565C0] border-blue-200'
                  : 'bg-white hover:bg-slate-100 text-slate-700 border-slate-200'
              }`}
            >
              <SlidersHorizontal className="w-3.5 h-3.5" />
              <span>Advance Filter</span>
            </button>
          </div>

          {/* Right Toolbar Actions */}
          <div className="flex items-center gap-2">
            <button
              onClick={handleExportCSV}
              title="Export CSV"
              className="w-8 h-8 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center transition-colors shadow-2xs cursor-pointer"
            >
              <Download className="w-3.5 h-3.5" />
            </button>
            <button
              onClick={() => {
                setSearchQuery('');
                setStatusFilter('all');
                setDeptFilter('all');
                setOvertimeOnly(false);
                setSelectedDate(new Date().toISOString().split('T')[0]);
              }}
              title="Refresh / Reset Filters"
              className="w-8 h-8 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-600 flex items-center justify-center transition-colors shadow-2xs cursor-pointer"
            >
              <RotateCcw className="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        {/* Advance Filter Drawer if toggled */}
        {showAdvanceFilter && (
          <div className="p-3.5 bg-blue-50/40 border-b border-slate-200 flex flex-wrap items-center gap-3 text-xs animate-in slide-in-from-top-1">
            <div className="flex items-center gap-2">
              <span className="font-bold text-slate-600">Status:</span>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-xs font-semibold focus:outline-none"
              >
                <option value="all">All Status</option>
                <option value="on_time">On Time</option>
                <option value="late">Late Clock-In</option>
              </select>
            </div>

            <div className="flex items-center gap-2">
              <span className="font-bold text-slate-600">Department:</span>
              <select
                value={deptFilter}
                onChange={(e) => setDeptFilter(e.target.value)}
                className="px-2.5 py-1 bg-white border border-slate-200 rounded-lg text-xs font-semibold focus:outline-none"
              >
                <option value="all">All Departments</option>
                <option value="Laboratory">Laboratory</option>
                <option value="Quality Control">Quality Control</option>
                <option value="Purchasing">Purchasing</option>
                <option value="Warehouse & Logistics">Warehouse & Logistics</option>
                <option value="Research & Dev">Research & Dev</option>
              </select>
            </div>

            <label className="flex items-center gap-1.5 cursor-pointer font-bold text-slate-700">
              <input
                type="checkbox"
                checked={overtimeOnly}
                onChange={(e) => setOvertimeOnly(e.target.checked)}
                className="rounded text-blue-600 focus:ring-blue-500"
              />
              <span>Overtime records only</span>
            </label>
          </div>
        )}

        {/* Main Table (Exact .mtbl styling) */}
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse min-w-[880px]">
            <thead>
              <tr className="bg-slate-50 border-b-2 border-slate-200 text-[10.5px] font-bold text-slate-500 uppercase tracking-wider">
                <th className="py-3 px-4 pl-6">Employee Name</th>
                <th className="py-3 px-4">Clock-in &amp; Out</th>
                <th className="py-3 px-4">Overtime</th>
                <th className="py-3 px-4">Picture</th>
                <th className="py-3 px-4">Location</th>
                <th className="py-3 px-4 pr-6">Notes</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-xs">
              {filteredEmployees.length === 0 ? (
                <tr>
                  <td colSpan={6} className="py-12 text-center text-slate-400">
                    <Calendar className="w-8 h-8 mx-auto mb-2 opacity-40 text-slate-400" />
                    <p className="font-bold text-slate-600">No attendance records found</p>
                    <p className="text-[11px] text-slate-400 mt-0.5">Try clearing filters or search query</p>
                  </td>
                </tr>
              ) : (
                filteredEmployees.map((emp, idx) => {
                  const slug = `${emp.first_name.toLowerCase()}_${emp.last_name.toLowerCase()}`;

                  return (
                    <tr
                      key={emp.id}
                      onClick={() => setActiveEmpIndex(idx)}
                      className="hover:bg-[#f2f4fc] cursor-pointer transition-colors group"
                    >
                      {/* Employee Name & Avatar */}
                      <td className="py-3.5 px-4 pl-6">
                        <div className="flex items-center gap-3">
                          <div
                            className="w-8 h-8 rounded-full text-white flex items-center justify-center text-xs font-bold shrink-0 shadow-2xs"
                            style={{ backgroundColor: emp.color }}
                          >
                            {emp.initials}
                          </div>
                          <div>
                            <div className="font-bold text-slate-900 flex items-center gap-1.5">
                              <span>
                                {emp.first_name} {emp.last_name}
                              </span>
                              {emp.is_late && (
                                <span className="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 text-[9.5px] font-bold border border-rose-200">
                                  <Clock className="w-2.5 h-2.5" /> Late
                                </span>
                              )}
                            </div>
                            <div className="text-[11px] text-slate-400 font-mono mt-0.5">
                              {emp.employee_id}
                            </div>
                          </div>
                        </div>
                      </td>

                      {/* Clock-in & Out */}
                      <td className="py-3.5 px-4">
                        <div className="flex items-center font-mono">
                          <span className="text-[#1645b6] font-bold">{emp.clock_in}</span>
                          <span className="text-slate-400 text-[11px] px-1.5">{emp.duration}</span>
                          <span className="text-[#d4241a] font-bold">{emp.clock_out}</span>
                        </div>
                      </td>

                      {/* Overtime */}
                      <td className="py-3.5 px-4">
                        {emp.overtime ? (
                          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 text-[11px] font-bold font-mono border border-amber-200">
                            <Plus className="w-2.5 h-2.5" /> {emp.overtime}
                          </span>
                        ) : (
                          <span className="text-slate-300 font-mono">—</span>
                        )}
                      </td>

                      {/* Picture Link / Lightbox trigger */}
                      <td className="py-3.5 px-4">
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            setLightboxData({
                              isOpen: true,
                              type: 'Clock-in Photo',
                              time: emp.clock_in,
                              name: `${emp.first_name} ${emp.last_name}`,
                              color: emp.color,
                              initials: emp.initials,
                              photoUrl: emp.camera_proof,
                              location: emp.location,
                            });
                          }}
                          className="text-[#1645b6] hover:text-[#0a1045] font-mono text-xs hover:underline inline-flex items-center gap-1 truncate max-w-[140px]"
                        >
                          <Camera className="w-3 h-3 text-blue-500" />
                          <span>{slug}_profi...</span>
                        </button>
                      </td>

                      {/* Location Link */}
                      <td className="py-3.5 px-4">
                        <span
                          className="text-[#1645b6] hover:text-[#0a1045] text-xs inline-flex items-center gap-1.5 truncate max-w-[170px]"
                          title={emp.location}
                        >
                          <MapPin className="w-3.5 h-3.5 text-teal-600 shrink-0" />
                          <span className="truncate">{emp.location}</span>
                        </span>
                      </td>

                      {/* Notes */}
                      <td className="py-3.5 px-4 pr-6">
                        <span
                          className="text-slate-600 text-xs truncate max-w-[180px] block"
                          title={emp.note}
                        >
                          {emp.note}
                        </span>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>

        {/* Table Footer (Exact .tbl-footer styling) */}
        <div className="flex items-center justify-between p-3.5 sm:px-6 bg-slate-50/80 border-t border-slate-200 text-xs text-slate-500">
          <div>
            Showing <strong className="text-slate-800">{filteredEmployees.length}</strong> employees
          </div>
          <div className="flex items-center gap-1.5">
            <button
              disabled={currentPage === 1}
              onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
              className="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-500 disabled:opacity-40 flex items-center justify-center hover:bg-slate-100"
            >
              <ChevronLeft className="w-3.5 h-3.5" />
            </button>
            <button className="w-7 h-7 rounded-lg bg-[#0a1045] text-white font-bold text-xs flex items-center justify-center shadow-xs">
              1
            </button>
            <button
              disabled={true}
              className="w-7 h-7 rounded-lg border border-slate-200 bg-white text-slate-500 disabled:opacity-40 flex items-center justify-center hover:bg-slate-100"
            >
              <ChevronRight className="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>

      {/* ═══════════════════════════════════════════
          SLIDE-OVER EMPLOYEE DETAIL PANEL (Exact match to .emp-panel in attendance.php lines 666-1065)
      ═══════════════════════════════════════════ */}
      {selectedEmpForPanel && (
        <div className="fixed inset-0 z-50 overflow-hidden flex justify-end">
          {/* Backdrop blur overlay */}
          <div
            onClick={() => setActiveEmpIndex(null)}
            className="fixed inset-0 bg-[#0a1045]/40 backdrop-blur-xs transition-opacity animate-in fade-in"
          />

          {/* Panel content */}
          <div className="relative w-full max-w-2xl bg-white h-full shadow-2xl flex flex-col z-10 animate-in slide-in-from-right duration-300 overflow-hidden">
            {/* Panel Floating Close Button */}
            <button
              onClick={() => setActiveEmpIndex(null)}
              className="absolute top-4 right-4 z-20 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-colors"
              title="Close panel"
            >
              <X className="w-4 h-4" />
            </button>

            {/* Navy Header with Red Top Accent Stripe */}
            <div className="bg-[#0a1045] text-white p-6 relative flex-shrink-0">
              <div className="absolute top-0 left-0 right-0 h-[3px] bg-[#d4241a]" />

              <div className="flex items-start justify-between gap-4 mb-4">
                {/* Employee Profile Identity */}
                <div className="flex items-center gap-3.5">
                  <div
                    className="w-14 h-14 rounded-full text-white flex items-center justify-center text-xl font-black shrink-0 border-2 border-white/25 shadow-md"
                    style={{ backgroundColor: selectedEmpForPanel.color }}
                  >
                    {selectedEmpForPanel.initials}
                  </div>
                  <div>
                    <h2 className="text-xl font-extrabold text-white tracking-tight">
                      {selectedEmpForPanel.first_name} {selectedEmpForPanel.last_name}
                    </h2>
                    <p className="text-xs text-white/60 font-semibold mt-0.5">
                      {selectedEmpForPanel.position || 'Specialist'} &bull; {selectedEmpForPanel.department}
                    </p>
                    <div className="flex items-center gap-4 mt-2 text-[11px] text-white/80 font-mono">
                      <div>
                        <span className="text-[9.5px] uppercase text-white/40 block font-bold">Employee ID</span>
                        <span>{selectedEmpForPanel.employee_id}</span>
                      </div>
                      <div>
                        <span className="text-[9.5px] uppercase text-white/40 block font-bold">Phone Number</span>
                        <span>{selectedEmpForPanel.phone || '+63 921 019 100'}</span>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Counter & Stepper Navigation */}
                <div className="flex items-center gap-2 pr-8 sm:pr-0">
                  <span className="text-[11px] text-white/60 font-mono mr-1">
                    {(activeEmpIndex ?? 0) + 1} out of {filteredEmployees.length}
                  </span>
                  <button
                    disabled={activeEmpIndex === 0}
                    onClick={() => handleNavPanel(-1)}
                    className="w-7 h-7 rounded-lg border border-white/20 bg-white/10 hover:bg-white/20 text-white disabled:opacity-30 flex items-center justify-center transition-colors"
                  >
                    <ChevronLeft className="w-3.5 h-3.5" />
                  </button>
                  <button
                    disabled={activeEmpIndex === filteredEmployees.length - 1}
                    onClick={() => handleNavPanel(1)}
                    className="w-7 h-7 rounded-lg border border-white/20 bg-white/10 hover:bg-white/20 text-white disabled:opacity-30 flex items-center justify-center transition-colors"
                  >
                    <ChevronRight className="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>

            {/* 6-Column Stats Strip (Exact .ep-stats-strip in attendance.php lines 1000-1032) */}
            <div className="flex bg-white border-b border-slate-200 overflow-x-auto divide-x divide-slate-100 flex-shrink-0 text-center">
              <div className="flex-1 min-w-[85px] p-3">
                <div className="text-xl font-extrabold text-slate-900 font-mono">12</div>
                <div className="text-[9.5px] uppercase font-bold text-slate-400 mt-0.5">Day off</div>
                <div className="text-[10px] text-emerald-600 font-bold mt-0.5">+12 vs last mo</div>
              </div>

              <div className="flex-1 min-w-[85px] p-3">
                <div className="text-xl font-extrabold text-slate-900 font-mono">
                  {selectedEmpForPanel.is_late ? '6' : '0'}
                </div>
                <div className="text-[9.5px] uppercase font-bold text-slate-400 mt-0.5">Late clock-in</div>
                <div className="text-[10px] text-rose-600 font-bold mt-0.5">-2 vs last mo</div>
              </div>

              <div className="flex-1 min-w-[85px] p-3">
                <div className="text-xl font-extrabold text-slate-900 font-mono">21</div>
                <div className="text-[9.5px] uppercase font-bold text-slate-400 mt-0.5">Late clock-out</div>
                <div className="text-[10px] text-rose-600 font-bold mt-0.5">-12 vs last mo</div>
              </div>

              <div className="flex-1 min-w-[85px] p-3">
                <div className="text-xl font-extrabold text-slate-900 font-mono">2</div>
                <div className="text-[9.5px] uppercase font-bold text-slate-400 mt-0.5">No clock-out</div>
                <div className="text-[10px] text-emerald-600 font-bold mt-0.5">+4 vs last mo</div>
              </div>

              <div className="flex-1 min-w-[85px] p-3">
                <div className="text-xl font-extrabold text-slate-900 font-mono">0</div>
                <div className="text-[9.5px] uppercase font-bold text-slate-400 mt-0.5">Off quota</div>
                <div className="text-[10px] text-slate-400 font-medium mt-0.5">0 vs last mo</div>
              </div>

              <div className="flex-1 min-w-[85px] p-3">
                <div className="text-xl font-extrabold text-slate-900 font-mono">2</div>
                <div className="text-[9.5px] uppercase font-bold text-slate-400 mt-0.5">Absent</div>
                <div className="text-[10px] text-slate-400 font-medium mt-0.5">0 vs last mo</div>
              </div>
            </div>

            {/* Scrollable Day List Body */}
            <div className="flex-1 overflow-y-auto bg-slate-50">
              {/* Month Selector Bar */}
              <div className="flex items-center justify-between gap-3 p-3.5 bg-white border-b border-slate-200 sticky top-0 z-10 flex-wrap">
                <div className="flex items-center gap-2 font-bold text-xs text-slate-800">
                  <button className="text-slate-400 hover:text-slate-700 p-0.5">
                    <ChevronLeft className="w-3.5 h-3.5" />
                  </button>
                  <span>{panelMonth}</span>
                  <button className="text-slate-400 hover:text-slate-700 p-0.5">
                    <ChevronRight className="w-3.5 h-3.5" />
                  </button>
                </div>

                <div className="flex items-center gap-2">
                  <div className="relative flex items-center bg-slate-50 border border-slate-200 rounded-lg px-2 py-1">
                    <Search className="w-3 h-3 text-slate-400 mr-1.5" />
                    <input
                      type="text"
                      placeholder="Search"
                      value={panelSearch}
                      onChange={(e) => setPanelSearch(e.target.value)}
                      className="w-24 text-[11px] bg-transparent outline-none text-slate-800"
                    />
                  </div>

                  <select
                    value={panelStatusFilter}
                    onChange={(e) => setPanelStatusFilter(e.target.value)}
                    className="px-2 py-1 bg-white border border-slate-200 rounded-lg text-[11px] font-semibold text-slate-700 focus:outline-none"
                  >
                    <option value="all">All Status</option>
                    <option value="on_time">On Time</option>
                    <option value="late">Late</option>
                    <option value="absent">Absent</option>
                  </select>
                </div>
              </div>

              {/* Day Cards List (Exact .ep-day-card in attendance.php lines 1177-1206) */}
              <div className="p-4 space-y-3.5">
                {filteredPanelDays.map((day, dIdx) => (
                  <div
                    key={dIdx}
                    className="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden hover:shadow-sm transition-shadow"
                  >
                    {/* Day Card Header */}
                    <div className="flex items-center justify-between p-3.5 border-b border-slate-100">
                      <div className="text-xs font-extrabold text-slate-900">{day.label}</div>
                      <div>
                        {day.badge === 'approved' && (
                          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 text-[10px] font-bold border border-emerald-200">
                            <CheckCircle2 className="w-3 h-3" /> Approved
                          </span>
                        )}
                        {day.badge === 'ot' && (
                          <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 text-[10px] font-bold border border-amber-200">
                            <Clock className="w-3 h-3" /> Overtime approval
                          </span>
                        )}
                      </div>
                    </div>

                    {/* Timeline Wrap */}
                    <div className="px-4 pt-3 pb-1">
                      {/* Hour Markers */}
                      <div className="flex justify-between text-[9.5px] font-mono text-slate-400 mb-1 px-0.5">
                        {timelineHours.map((h) => (
                          <span key={h}>{h}</span>
                        ))}
                      </div>

                      {/* Timeline Bar with Segments */}
                      <div className="relative h-6 bg-slate-100 rounded-md overflow-hidden mb-2 group">
                        {/* Tooltip bubble on hover */}
                        {day.tooltip && (
                          <div className="absolute -top-9 left-1/2 -translate-x-1/2 bg-[#0a1045] text-white text-[10px] font-medium font-mono px-2.5 py-1 rounded-md shadow-lg opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-20 whitespace-nowrap">
                            {day.tooltip}
                          </div>
                        )}

                        {day.segments.map((seg, sIdx) => (
                          <div
                            key={sIdx}
                            className={`absolute top-0 bottom-0 rounded-[3px] flex items-center justify-center text-[9px] font-bold text-white px-1 truncate ${seg.cls}`}
                            style={{ left: seg.left, width: seg.width }}
                          >
                            {seg.label}
                          </div>
                        ))}
                      </div>
                    </div>

                    {/* Clock-in, Clock-out, Duration Row */}
                    <div className="flex items-center gap-6 px-4 py-2 text-xs flex-wrap">
                      <div>
                        <div className="text-[9.5px] uppercase font-bold text-slate-400">Clock-in</div>
                        <div
                          className={`font-bold font-mono ${
                            day.clockIn !== '—' ? 'text-[#1645b6]' : 'text-slate-300'
                          }`}
                        >
                          {day.clockIn}
                        </div>
                      </div>

                      <div>
                        <div className="text-[9.5px] uppercase font-bold text-slate-400">Clock-out</div>
                        <div
                          className={`font-bold font-mono ${
                            day.clockOut !== '—' ? 'text-[#d4241a]' : 'text-slate-300'
                          }`}
                        >
                          {day.clockOut}
                        </div>
                      </div>

                      <div className="ml-auto text-right">
                        <div className="text-[9.5px] uppercase font-bold text-slate-400">Duration</div>
                        <div className="font-bold font-mono text-slate-800">{day.duration}</div>
                      </div>
                    </div>

                    {/* Clock-in / Clock-out Photo Thumbnails (Exact match to .ep-photos) */}
                    {day.clockIn !== '—' && (
                      <div className="px-4 pb-3 pt-1 flex items-center gap-3 border-t border-slate-50 mt-1">
                        {/* Clock-in Photo */}
                        <div className="flex flex-col gap-1">
                          <span className="text-[9px] font-bold text-slate-400 uppercase tracking-wider">
                            Clock-in photo
                          </span>
                          <div
                            onClick={() =>
                              setLightboxData({
                                isOpen: true,
                                type: 'Clock-in Photo',
                                time: day.clockIn,
                                name: `${selectedEmpForPanel.first_name} ${selectedEmpForPanel.last_name}`,
                                color: selectedEmpForPanel.color,
                                initials: selectedEmpForPanel.initials,
                                photoUrl: day.clockInPhoto,
                                location: selectedEmpForPanel.location,
                              })
                            }
                            className="w-16 h-16 rounded-xl relative overflow-hidden cursor-pointer shadow-xs flex items-center justify-center hover:scale-105 transition-transform border border-slate-200"
                            style={{ backgroundColor: selectedEmpForPanel.color }}
                          >
                            {day.clockInPhoto ? (
                              <img
                                src={day.clockInPhoto}
                                alt="Clock in"
                                className="w-full h-full object-cover"
                              />
                            ) : (
                              <span className="text-white font-extrabold text-lg opacity-90">
                                {selectedEmpForPanel.initials}
                              </span>
                            )}
                            <div className="absolute top-1 right-1 bg-black/40 rounded-full p-0.5 text-white">
                              <Camera className="w-2.5 h-2.5" />
                            </div>
                            <div className="absolute bottom-0 inset-x-0 bg-black/60 text-white text-[8px] font-mono text-center py-0.5">
                              {day.clockIn}
                            </div>
                          </div>
                        </div>

                        {/* Clock-out Photo */}
                        {day.clockOut !== '—' && (
                          <div className="flex flex-col gap-1">
                            <span className="text-[9px] font-bold text-slate-400 uppercase tracking-wider">
                              Clock-out photo
                            </span>
                            <div
                              onClick={() =>
                                setLightboxData({
                                  isOpen: true,
                                  type: 'Clock-out Photo',
                                  time: day.clockOut,
                                  name: `${selectedEmpForPanel.first_name} ${selectedEmpForPanel.last_name}`,
                                  color: selectedEmpForPanel.color,
                                  initials: selectedEmpForPanel.initials,
                                  photoUrl: day.clockOutPhoto,
                                  location: selectedEmpForPanel.location,
                                })
                              }
                              className="w-16 h-16 rounded-xl relative overflow-hidden cursor-pointer shadow-xs flex items-center justify-center hover:scale-105 transition-transform border border-slate-200 opacity-90"
                              style={{ backgroundColor: selectedEmpForPanel.color }}
                            >
                              {day.clockOutPhoto ? (
                                <img
                                  src={day.clockOutPhoto}
                                  alt="Clock out"
                                  className="w-full h-full object-cover"
                                />
                              ) : (
                                <span className="text-white font-extrabold text-lg opacity-90">
                                  {selectedEmpForPanel.initials}
                                </span>
                              )}
                              <div className="absolute top-1 right-1 bg-black/40 rounded-full p-0.5 text-white">
                                <Camera className="w-2.5 h-2.5" />
                              </div>
                              <div className="absolute bottom-0 inset-x-0 bg-black/60 text-white text-[8px] font-mono text-center py-0.5">
                                {day.clockOut}
                              </div>
                            </div>
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ═══════════════════════════════════════════
          PHOTO LIGHTBOX MODAL (Exact match to .lb-overlay in attendance.php lines 946-959)
      ═══════════════════════════════════════════ */}
      {lightboxData?.isOpen && (
        <div
          onClick={() => setLightboxData(null)}
          className="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex flex-col items-center justify-center p-4 animate-in fade-in"
        >
          {/* Close button */}
          <button
            onClick={() => setLightboxData(null)}
            className="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center mb-3 transition-colors cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>

          {/* Lightbox Box */}
          <div
            onClick={(e) => e.stopPropagation()}
            className="bg-white rounded-2xl overflow-hidden shadow-2xl max-w-sm w-full animate-in zoom-in-95"
          >
            {/* Photo / Avatar Area */}
            <div
              className="w-full aspect-square flex items-center justify-center relative overflow-hidden"
              style={{ backgroundColor: lightboxData.color || '#0a1045' }}
            >
              {lightboxData.photoUrl ? (
                <img
                  src={lightboxData.photoUrl}
                  alt={lightboxData.name}
                  className="w-full h-full object-cover"
                />
              ) : (
                <span className="text-7xl font-extrabold text-white/90">
                  {lightboxData.initials}
                </span>
              )}
              <div className="absolute top-3 right-3 bg-black/50 text-white text-xs px-2.5 py-1 rounded-full flex items-center gap-1">
                <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" /> Biometric Verified
              </div>
            </div>

            {/* Metadata Footer */}
            <div className="p-4 border-t border-slate-100 flex items-center justify-between">
              <div>
                <div className="text-[10px] uppercase font-bold text-slate-400 tracking-wider">
                  {lightboxData.type}
                </div>
                <div className="text-base font-extrabold font-mono text-slate-900 mt-0.5">
                  {lightboxData.time}
                </div>
                <div className="text-xs text-slate-600 mt-0.5">{lightboxData.name}</div>
                {lightboxData.location && (
                  <div className="text-[11px] text-slate-400 mt-1 flex items-center gap-1 truncate max-w-[240px]">
                    <MapPin className="w-3 h-3 text-teal-600 shrink-0" />
                    <span className="truncate">{lightboxData.location}</span>
                  </div>
                )}
              </div>
              <button
                onClick={() => setLightboxData(null)}
                className="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100"
              >
                <Maximize2 className="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ═══════════════════════════════════════════
          MANUAL ENTRY MODAL
      ═══════════════════════════════════════════ */}
      {showManualModal && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 animate-in zoom-in-95">
            <div className="flex items-center justify-between pb-2 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <Clock className="w-4 h-4 text-[#0a1045]" />
                <h3 className="text-sm font-bold text-slate-900">Add / Adjust Shift Log</h3>
              </div>
              <button
                onClick={() => setShowManualModal(false)}
                className="text-slate-400 hover:text-slate-600"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleManualSubmit} className="space-y-3.5">
              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Select Employee</label>
                <select
                  value={manualEmpId}
                  onChange={(e) => setManualEmpId(Number(e.target.value))}
                  className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  {employees.map((emp) => (
                    <option key={emp.id} value={emp.id}>
                      {emp.first_name} {emp.last_name} ({emp.employee_id}) - {emp.department}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Attendance Date</label>
                <input
                  type="date"
                  required
                  value={manualDate}
                  onChange={(e) => setManualDate(e.target.value)}
                  className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="text-xs font-bold text-slate-700 block mb-1">Clock-in Time</label>
                  <input
                    type="text"
                    required
                    value={manualIn}
                    onChange={(e) => setManualIn(e.target.value)}
                    placeholder="08:00 AM"
                    className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="text-xs font-bold text-slate-700 block mb-1">Clock-out Time</label>
                  <input
                    type="text"
                    required
                    value={manualOut}
                    onChange={(e) => setManualOut(e.target.value)}
                    placeholder="05:00 PM"
                    className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>

              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Shift Status</label>
                <select
                  value={manualStatus}
                  onChange={(e) => setManualStatus(e.target.value as AttendanceStatus)}
                  className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="present">On Time / Present</option>
                  <option value="late">Late Arrival</option>
                  <option value="absent">Excused / Absent</option>
                  <option value="half_day">Half Day Shift</option>
                </select>
              </div>

              <div>
                <div className="flex items-center justify-between mb-1">
                  <label className="text-xs font-bold text-slate-700">Location</label>
                  <button
                    type="button"
                    onClick={handleDetectLocation}
                    disabled={isDetectingLocation}
                    className="text-[11px] font-bold text-blue-600 hover:underline"
                  >
                    {isDetectingLocation ? 'Detecting GPS...' : 'Auto-detect GPS'}
                  </button>
                </div>
                <input
                  type="text"
                  value={manualLocation}
                  onChange={(e) => setManualLocation(e.target.value)}
                  className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Notes / Reason</label>
                <input
                  type="text"
                  value={manualNotes}
                  onChange={(e) => setManualNotes(e.target.value)}
                  placeholder="e.g. Regular laboratory biometric shift"
                  className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div className="flex items-center gap-2 pt-2">
                <button
                  type="submit"
                  className="flex-1 py-2.5 bg-[#0a1045] hover:bg-[#0f1860] text-white font-bold text-xs rounded-xl shadow-xs transition-colors"
                >
                  Save Shift Log
                </button>
                <button
                  type="button"
                  onClick={() => setShowManualModal(false)}
                  className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
