// AttendanceHistoryView.tsx - Exact 1:1 Implementation matching attendance_history.php
import React, { useState, useMemo } from 'react';
import { useApp } from '../../context/AppContext';
import { AttendanceRecord, AttendanceStatus, CameraAttendanceLog } from '../../types';
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
  Compass,
  Thermometer,
  ChevronRight,
  ArrowLeft,
  X,
  UserCheck,
} from 'lucide-react';

export const AttendanceHistoryView: React.FC = () => {
  const {
    attendanceRecords,
    cameraLogs,
    employees,
    currentUser,
    setActiveTab,
    markAttendance,
  } = useApp();

  const isEmployeeRole = currentUser?.role === 'employee';

  // Identify current target employee (logged in user or selected for inspection)
  const currentEmp = useMemo(() => {
    if (!currentUser) return employees[0];
    return (
      employees.find((e) => e.email.toLowerCase() === (currentUser.email || '').toLowerCase()) ||
      employees.find((e) => e.employee_id === currentUser.employee_id) ||
      employees[0]
    );
  }, [employees, currentUser]);

  const [selectedEmpId, setSelectedEmpId] = useState<number>(currentEmp?.id || 1);
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [monthFilter, setMonthFilter] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [selectedPhotoModal, setSelectedPhotoModal] = useState<CameraAttendanceLog | null>(null);
  const [showCallModal, setShowCallModal] = useState<boolean>(false);
  const [showAdjustmentModal, setShowAdjustmentModal] = useState<boolean>(false);
  const [toastMessage, setToastMessage] = useState<string | null>(null);

  // Adjustment form state
  const [adjDate, setAdjDate] = useState(new Date().toISOString().split('T')[0]);
  const [adjCheckIn, setAdjCheckIn] = useState('08:00 AM');
  const [adjCheckOut, setAdjCheckOut] = useState('05:00 PM');
  const [adjStatus, setAdjStatus] = useState<AttendanceStatus>('present');
  const [adjNotes, setAdjNotes] = useState('Regular biometric shift completion');

  const activeEmployee = employees.find((e) => e.id === Number(selectedEmpId)) || currentEmp || employees[0];

  // Filter records belonging to the target employee
  const employeeRecords = useMemo(() => {
    return attendanceRecords.filter((rec) => rec.employee_id === activeEmployee.id);
  }, [attendanceRecords, activeEmployee.id]);

  // Merge camera logs with attendance records to provide photo proofs
  const enrichedRecords = useMemo(() => {
    return employeeRecords.map((rec) => {
      // Find matching camera logs on this date for this employee
      const matchCam = cameraLogs.find(
        (cl) => cl.employee_id === rec.employee_id && cl.capture_date === rec.date
      );
      return {
        ...rec,
        camera_photo: matchCam?.photo_path,
        camera_meta: matchCam,
      };
    });
  }, [employeeRecords, cameraLogs]);

  // Calculate high-fidelity Statistics matching attendance_history.php
  const stats = useMemo(() => {
    const totalDays = enrichedRecords.length;
    let onTimeCount = 0;
    let lateCount = 0;
    let absentCount = 0;
    let holidayCount = 0;
    let totalHours = 0;
    const checkInTimes: number[] = [];
    const checkOutTimes: number[] = [];

    enrichedRecords.forEach((rec) => {
      const st = rec.status;
      if (st === 'present') onTimeCount++;
      else if (st === 'late') lateCount++;
      else if (st === 'absent') absentCount++;
      else if (st === 'half_day') lateCount++;

      if (rec.check_in && rec.check_in !== '-') {
        // Parse time into minutes from midnight for average
        const timeParts = rec.check_in.match(/(\d+):(\d+)\s*(AM|PM)?/i);
        if (timeParts) {
          let h = parseInt(timeParts[1], 10);
          const m = parseInt(timeParts[2], 10);
          const ampm = timeParts[3]?.toUpperCase();
          if (ampm === 'PM' && h < 12) h += 12;
          if (ampm === 'AM' && h === 12) h = 0;
          checkInTimes.push(h * 60 + m);
        }
      }

      if (rec.check_out && rec.check_out !== '-') {
        const timeParts = rec.check_out.match(/(\d+):(\d+)\s*(AM|PM)?/i);
        if (timeParts) {
          let h = parseInt(timeParts[1], 10);
          const m = parseInt(timeParts[2], 10);
          const ampm = timeParts[3]?.toUpperCase();
          if (ampm === 'PM' && h < 12) h += 12;
          if (ampm === 'AM' && h === 12) h = 0;
          checkOutTimes.push(h * 60 + m);
        }
      }

      totalHours += Number(rec.total_hours) || 8;
    });

    const onTimePct = totalDays > 0 ? Math.round((onTimeCount / totalDays) * 100) : 0;
    const latePct = totalDays > 0 ? Math.round((lateCount / totalDays) * 100) : 0;
    const absentPct = totalDays > 0 ? Math.round((absentCount / totalDays) * 100) : 0;

    const formatAvgTime = (minutesArr: number[]): string => {
      if (minutesArr.length === 0) return '08:00 AM';
      const avg = Math.round(minutesArr.reduce((a, b) => a + b, 0) / minutesArr.length);
      const h = Math.floor(avg / 60);
      const m = avg % 60;
      const period = h >= 12 ? 'PM' : 'AM';
      const displayH = h % 12 === 0 ? 12 : h % 12;
      return `${displayH}:${m.toString().padStart(2, '0')} ${period}`;
    };

    return {
      totalDays,
      totalHours: Math.round(totalHours),
      onTimeCount,
      lateCount,
      absentCount,
      onTimePct,
      latePct,
      absentPct,
      avgCheckIn: formatAvgTime(checkInTimes),
      avgCheckOut: formatAvgTime(checkOutTimes),
    };
  }, [enrichedRecords]);

  // Filter and Search applied records
  const filteredRecords = useMemo(() => {
    return enrichedRecords.filter((rec) => {
      const matchStatus =
        statusFilter === 'all' ||
        (statusFilter === 'on_time' && rec.status === 'present') ||
        rec.status === statusFilter;

      const dateObj = new Date(rec.date);
      const monthYearStr = dateObj.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
      const matchMonth = monthFilter === 'all' || monthYearStr.toLowerCase() === monthFilter.toLowerCase();

      const q = searchQuery.toLowerCase();
      const matchSearch =
        !q ||
        rec.date.toLowerCase().includes(q) ||
        (rec.notes || '').toLowerCase().includes(q) ||
        (rec.location || '').toLowerCase().includes(q) ||
        rec.status.toLowerCase().includes(q);

      return matchStatus && matchMonth && matchSearch;
    });
  }, [enrichedRecords, statusFilter, monthFilter, searchQuery]);

  // Group filtered records by month/period (e.g. "August 2026", "July 2026")
  const groupedPeriods = useMemo(() => {
    const groups: { [key: string]: typeof filteredRecords } = {};
    // Sort descending by date
    const sorted = [...filteredRecords].sort((a, b) => new Date(b.date).getTime() - new Date(a.date).getTime());
    sorted.forEach((rec) => {
      const d = new Date(rec.date);
      const periodKey = d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }).toUpperCase();
      if (!groups[periodKey]) {
        groups[periodKey] = [];
      }
      groups[periodKey].push(rec);
    });
    return groups;
  }, [filteredRecords]);

  // Unique list of months for filter dropdown
  const availableMonths = useMemo(() => {
    const set = new Set<string>();
    enrichedRecords.forEach((r) => {
      const d = new Date(r.date);
      set.add(d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }));
    });
    return Array.from(set);
  }, [enrichedRecords]);

  const handleAdjustmentSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    markAttendance({
      employee_id: activeEmployee.id,
      employee_name: `${activeEmployee.first_name} ${activeEmployee.last_name}`,
      department: activeEmployee.department,
      date: adjDate,
      check_in: adjCheckIn,
      check_out: adjCheckOut,
      break_duration: 60,
      total_hours: 8.0,
      status: adjStatus,
      location: 'McPIL Central Laboratory Station',
      notes: adjNotes,
    });

    setShowAdjustmentModal(false);
    setToastMessage(`Attendance record updated for ${adjDate}!`);
    setTimeout(() => setToastMessage(null), 4000);
  };

  const handleExportCSV = () => {
    const headers = ['Date', 'Employee', 'Department', 'Check In', 'Check Out', 'Total Hours', 'Status', 'Notes', 'Location'];
    const rows = filteredRecords.map((r) => [
      r.date,
      r.employee_name,
      r.department,
      r.check_in || '-',
      r.check_out || '-',
      `${r.total_hours} hrs`,
      r.status,
      `"${(r.notes || '').replace(/"/g, '""')}"`,
      `"${(r.location || '').replace(/"/g, '""')}"`,
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map((e) => e.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `attendance_history_${activeEmployee.employee_id}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return (
    <div className="space-y-6">
      {/* Toast alert */}
      {toastMessage && (
        <div className="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 text-xs font-bold flex items-center justify-between shadow-xs animate-in fade-in">
          <div className="flex items-center gap-2">
            <CheckCircle2 className="w-4 h-4 text-emerald-600" />
            <span>{toastMessage}</span>
          </div>
          <button onClick={() => setToastMessage(null)} className="text-emerald-700">✕</button>
        </div>
      )}

      {/* TOP BAR / PAGE HEADER (Exact match to attendance_history.php) */}
      <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            <span>Attendance History</span>
          </h1>
          <div className="flex items-center gap-2 text-xs text-slate-500 mt-1 font-medium">
            <Calendar className="w-3.5 h-3.5 text-[#1565C0]" />
            <span>Today {new Date().toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })}</span>
          </div>

          {/* Legend dots */}
          <div className="flex items-center gap-4 mt-3 text-xs font-semibold text-slate-600 flex-wrap">
            <div className="flex items-center gap-1.5">
              <span className="w-2.5 h-2.5 rounded-full bg-[#1565C0]"></span>
              <span>On time {stats.onTimePct}%</span>
            </div>
            <div className="flex items-center gap-1.5">
              <span className="w-2.5 h-2.5 rounded-full bg-[#f59e0b]"></span>
              <span>Late {stats.latePct}%</span>
            </div>
            <div className="flex items-center gap-1.5">
              <span className="w-2.5 h-2.5 rounded-full bg-[#ef4444]"></span>
              <span>Absent {stats.absentPct}%</span>
            </div>
          </div>
        </div>

        {/* Quick Action Buttons */}
        <div className="flex items-center gap-2 flex-wrap">
          {!isEmployeeRole && (
            <select
              value={selectedEmpId}
              onChange={(e) => setSelectedEmpId(Number(e.target.value))}
              className="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              {employees.map((emp) => (
                <option key={emp.id} value={emp.id}>
                  {emp.first_name} {emp.last_name} ({emp.employee_id})
                </option>
              ))}
            </select>
          )}

          <button
            onClick={() => setActiveTab('camera_attendance')}
            className="px-4 py-2 bg-[#1565C0] hover:bg-[#0d47a1] text-white font-bold text-xs rounded-xl shadow-xs shadow-blue-700/20 flex items-center gap-2 transition-all cursor-pointer"
          >
            <Camera className="w-3.5 h-3.5" />
            <span>Clock In / Terminal</span>
          </button>

          <button
            onClick={() => setShowAdjustmentModal(true)}
            className="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors flex items-center gap-1.5 cursor-pointer"
            title="Log / Adjust Attendance"
          >
            <Plus className="w-3.5 h-3.5" />
            <span>Manual Entry</span>
          </button>

          <button
            onClick={handleExportCSV}
            className="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl border border-slate-200 transition-colors"
            title="Export CSV Timesheet"
          >
            <Download className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* PROFILE SUMMARY CARD (Exact 1:1 match to lines 569-599 in attendance_history.php) */}
      <div className="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-6">
        {/* Left: Avatar & Identity */}
        <div className="flex items-center gap-4 min-w-[240px]">
          <div className="w-13 h-13 rounded-full bg-gradient-to-br from-[#0d1b3e] to-[#2f69ff] text-white flex items-center justify-center font-extrabold text-base shadow-sm shrink-0">
            {activeEmployee.first_name[0]}{activeEmployee.last_name[0]}
          </div>

          <div className="min-w-0">
            <h2 className="text-base font-extrabold text-slate-900 leading-tight">
              {activeEmployee.first_name} {activeEmployee.last_name}
            </h2>
            <p className="text-xs text-slate-500 font-semibold mt-0.5">
              {activeEmployee.position || 'Staff'} &bull; {activeEmployee.department}
            </p>
            <p className="text-[11px] text-slate-400 font-mono mt-0.5 truncate">
              {activeEmployee.email || 'employee@mcpillab.com'}
            </p>
          </div>

          <div className="flex items-center gap-1.5 ml-2">
            <button
              onClick={() => setShowCallModal(true)}
              className="w-9 h-9 rounded-full border border-slate-200 hover:border-blue-500 hover:bg-blue-50 hover:text-blue-600 text-slate-500 flex items-center justify-center transition-all cursor-pointer shadow-xs"
              title={`Call ${activeEmployee.phone || 'Employee'}`}
            >
              <Phone className="w-3.5 h-3.5" />
            </button>
            <button
              onClick={() => setActiveTab('chat_interface' as any || 'dashboard')}
              className="w-9 h-9 rounded-full border border-slate-200 hover:border-blue-500 hover:bg-blue-50 hover:text-blue-600 text-slate-500 flex items-center justify-center transition-all cursor-pointer shadow-xs"
              title="Open Chat / Message"
            >
              <MessageSquare className="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        {/* Divider */}
        <div className="hidden lg:block w-px h-12 bg-slate-200"></div>

        {/* Right: 4 Stat Pills */}
        <div className="flex items-center gap-4 sm:gap-8 flex-wrap justify-between flex-1 max-w-xl">
          <div className="text-center min-w-[75px]">
            <div className="text-xl font-black text-slate-900 tracking-tight font-mono">
              {stats.totalDays} <span className="text-xs font-bold text-slate-500">days</span>
            </div>
            <div className="text-[11px] font-semibold text-slate-400 mt-0.5">Total Attendance</div>
          </div>

          <div className="text-center min-w-[75px]">
            <div className="text-xl font-black text-slate-900 tracking-tight font-mono">
              {stats.totalHours} <span className="text-xs font-bold text-slate-500">hours</span>
            </div>
            <div className="text-[11px] font-semibold text-slate-400 mt-0.5">Total hours</div>
          </div>

          <div className="text-center min-w-[75px]">
            <div className="text-xl font-black text-[#1565C0] tracking-tight font-mono">
              {stats.avgCheckIn}
            </div>
            <div className="text-[11px] font-semibold text-slate-400 mt-0.5">Avg check in</div>
          </div>

          <div className="text-center min-w-[75px]">
            <div className="text-xl font-black text-slate-800 tracking-tight font-mono">
              {stats.avgCheckOut}
            </div>
            <div className="text-[11px] font-semibold text-slate-400 mt-0.5">Avg check out</div>
          </div>
        </div>
      </div>

      {/* FILTER & SEARCH BAR */}
      <div className="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <div className="flex flex-wrap items-center gap-2">
          {/* Status Tabs */}
          <div className="flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs">
            {[
              { key: 'all', label: 'All Records' },
              { key: 'on_time', label: 'On time' },
              { key: 'late', label: 'Late' },
              { key: 'absent', label: 'Absent' },
            ].map((tab) => (
              <button
                key={tab.key}
                onClick={() => setStatusFilter(tab.key)}
                className={`px-3 py-1.5 rounded-lg font-bold transition-all ${
                  statusFilter === tab.key
                    ? 'bg-white text-slate-900 shadow-xs'
                    : 'text-slate-600 hover:text-slate-900'
                }`}
              >
                {tab.label}
              </button>
            ))}
          </div>

          {/* Month Selector */}
          <select
            value={monthFilter}
            onChange={(e) => setMonthFilter(e.target.value)}
            className="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:outline-none"
          >
            <option value="all">All Months</option>
            {availableMonths.map((m) => (
              <option key={m} value={m}>
                {m}
              </option>
            ))}
          </select>
        </div>

        {/* Search input */}
        <div className="relative w-full sm:w-64">
          <Search className="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search dates, notes, locations..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full pl-8 pr-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      {/* ATTENDANCE PERIOD GROUPS & GRIDS (Exact 1:1 match to .attendance-grid & .att-card) */}
      {Object.keys(groupedPeriods).length === 0 ? (
        <div className="bg-white rounded-2xl border border-slate-200 p-12 text-center text-slate-400">
          <Calendar className="w-10 h-10 mx-auto mb-3 opacity-30 text-slate-400" />
          <p className="text-sm font-bold text-slate-600">No attendance records found</p>
          <p className="text-xs text-slate-400 mt-1">
            Try adjusting your search query, status filters, or capture attendance from the camera terminal.
          </p>
        </div>
      ) : (
        <div className="space-y-8">
          {Object.entries(groupedPeriods).map(([periodTitle, records]) => (
            <div key={periodTitle} className="space-y-3.5">
              {/* Period Heading (Exact .period-heading) */}
              <div className="text-[13px] font-black text-slate-600 tracking-wider uppercase pb-2.5 border-b-2 border-slate-200 flex items-center justify-between">
                <span>{periodTitle}</span>
                <span className="text-[11px] font-semibold text-slate-400 lowercase font-mono">
                  {records.length} days recorded
                </span>
              </div>

              {/* Attendance 3-Column Grid (.attendance-grid) */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {records.map((rec) => {
                  const statusKey =
                    rec.status === 'present'
                      ? 'on-time'
                      : rec.status === 'late'
                      ? 'late'
                      : rec.status === 'absent'
                      ? 'absent'
                      : 'holiday';

                  const badgeLabel =
                    rec.status === 'present'
                      ? 'On time'
                      : rec.status === 'late'
                      ? 'Late'
                      : rec.status === 'absent'
                      ? 'Absent'
                      : 'Holiday';

                  const dateObj = new Date(rec.date);
                  const formattedDate = dateObj.toLocaleDateString('en-US', {
                    weekday: 'short',
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                  });

                  const checkInDisplay = rec.check_in && rec.check_in !== '-' ? rec.check_in : null;
                  const checkOutDisplay = rec.check_out && rec.check_out !== '-' ? rec.check_out : null;
                  const totalHrsDisplay =
                    rec.total_hours > 0 ? `${rec.total_hours}hr` : checkInDisplay && checkOutDisplay ? '8hr' : '0hr';

                  return (
                    <div
                      key={rec.id}
                      className={`bg-white rounded-2xl border border-slate-200 p-4 relative overflow-hidden shadow-2xs hover:shadow-md transition-all hover:-translate-y-0.5 flex flex-col justify-between group ${
                        rec.status === 'absent' ? 'opacity-75 bg-slate-50/50' : ''
                      }`}
                    >
                      {/* Top Accent 3px Bar */}
                      <div
                        className={`absolute top-0 left-0 right-0 h-[3.5px] ${
                          statusKey === 'on-time'
                            ? 'bg-[#1565C0]'
                            : statusKey === 'late'
                            ? 'bg-[#f59e0b]'
                            : statusKey === 'absent'
                            ? 'bg-[#ef4444]'
                            : 'bg-[#8b5cf6]'
                        }`}
                      ></div>

                      <div>
                        {/* Card Top: Date & Status Badge */}
                        <div className="flex items-center justify-between mb-3 pt-0.5">
                          <span className="text-[13px] font-extrabold text-slate-900">{formattedDate}</span>
                          <span
                            className={`text-[10px] font-extrabold px-2.5 py-0.5 rounded-full flex items-center gap-1.5 ${
                              statusKey === 'on-time'
                                ? 'bg-blue-50 text-[#1565C0] border border-blue-200'
                                : statusKey === 'late'
                                ? 'bg-amber-50 text-amber-800 border border-amber-200'
                                : statusKey === 'absent'
                                ? 'bg-rose-50 text-rose-800 border border-rose-200'
                                : 'bg-purple-50 text-purple-800 border border-purple-200'
                            }`}
                          >
                            <span
                              className={`w-1.5 h-1.5 rounded-full ${
                                statusKey === 'on-time'
                                  ? 'bg-[#1565C0]'
                                  : statusKey === 'late'
                                  ? 'bg-[#f59e0b]'
                                  : statusKey === 'absent'
                                  ? 'bg-[#ef4444]'
                                  : 'bg-[#8b5cf6]'
                              }`}
                            ></span>
                            {badgeLabel}
                          </span>
                        </div>

                        {/* Middle 3 Columns: Check In, Check Out, Total */}
                        <div className="grid grid-cols-3 gap-2 mb-3 bg-slate-50/80 p-2.5 rounded-xl border border-slate-100">
                          <div>
                            <div className="text-[9.5px] uppercase tracking-wider font-bold text-slate-400 mb-0.5">
                              Check In
                            </div>
                            <div
                              className={`text-[13px] font-black font-mono leading-tight ${
                                checkInDisplay ? 'text-slate-900' : 'text-slate-400 font-normal'
                              }`}
                            >
                              {checkInDisplay || '—'}
                            </div>
                          </div>

                          <div>
                            <div className="text-[9.5px] uppercase tracking-wider font-bold text-slate-400 mb-0.5">
                              Check Out
                            </div>
                            <div
                              className={`text-[13px] font-black font-mono leading-tight ${
                                checkOutDisplay ? 'text-slate-900' : 'text-slate-400 font-normal'
                              }`}
                            >
                              {checkOutDisplay || '—'}
                            </div>
                          </div>

                          <div>
                            <div className="text-[9.5px] uppercase tracking-wider font-bold text-slate-400 mb-0.5">
                              Total
                            </div>
                            <div className="text-[13px] font-black font-mono leading-tight text-slate-900">
                              {totalHrsDisplay}
                            </div>
                          </div>
                        </div>

                        {/* Location and Telemetry (if available) */}
                        {rec.location && (
                          <div className="text-[11px] text-slate-500 flex items-center gap-1.5 mb-2 truncate">
                            <MapPin className="w-3 h-3 text-teal-600 shrink-0" />
                            <span className="truncate">{rec.location}</span>
                          </div>
                        )}

                        {/* Notes line */}
                        <div className="text-[11.5px] text-slate-600 flex items-start gap-1.5 mb-2">
                          <span className="font-bold text-slate-400 shrink-0">Notes:</span>
                          <span
                            className={`truncate ${
                              rec.notes ? 'italic text-indigo-600 font-medium' : 'text-slate-400 not-italic'
                            }`}
                          >
                            {rec.notes || 'No notes'}
                          </span>
                        </div>
                      </div>

                      {/* Biometric Photo proof indicator / button */}
                      {rec.camera_photo && rec.camera_meta && (
                        <div className="pt-2 border-t border-slate-100 flex items-center justify-between">
                          <button
                            onClick={() => setSelectedPhotoModal(rec.camera_meta!)}
                            className="text-[11px] font-bold text-[#1565C0] hover:text-[#0d47a1] flex items-center gap-1.5 transition-colors"
                          >
                            <Camera className="w-3.5 h-3.5 text-blue-600" />
                            <span>View Biometric Photo Proof</span>
                          </button>

                          <span className="text-[10px] text-emerald-600 font-bold flex items-center gap-0.5 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                            <ShieldCheck className="w-3 h-3" /> Verified
                          </span>
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* CALL MODAL */}
      {showCallModal && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4 animate-in zoom-in-95">
            <div className="flex items-center justify-between pb-2 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <Phone className="w-4 h-4 text-[#1565C0]" />
                <h3 className="text-sm font-bold text-slate-900">Direct Contact</h3>
              </div>
              <button onClick={() => setShowCallModal(false)} className="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <div className="text-center py-3">
              <div className="w-14 h-14 rounded-full bg-blue-50 text-[#1565C0] flex items-center justify-center mx-auto mb-2 font-black text-lg">
                {activeEmployee.first_name[0]}{activeEmployee.last_name[0]}
              </div>
              <div className="text-sm font-bold text-slate-900">{activeEmployee.first_name} {activeEmployee.last_name}</div>
              <div className="text-xs text-slate-500">{activeEmployee.position} &bull; {activeEmployee.department}</div>
              <div className="text-base font-bold font-mono text-[#1565C0] mt-2">
                {activeEmployee.phone || '+63 917 123 4567'}
              </div>
            </div>

            <div className="flex gap-2">
              <a
                href={`tel:${activeEmployee.phone || '+639171234567'}`}
                className="flex-1 py-2.5 bg-[#1565C0] hover:bg-[#0d47a1] text-white font-bold text-xs rounded-xl text-center shadow-xs"
              >
                Start Voice Call
              </a>
              <button
                onClick={() => setShowCallModal(false)}
                className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ADJUSTMENT / MANUAL ATTENDANCE MODAL */}
      {showAdjustmentModal && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 animate-in zoom-in-95">
            <div className="flex items-center justify-between pb-2 border-b border-slate-100">
              <div className="flex items-center gap-2">
                <Clock className="w-4 h-4 text-[#1565C0]" />
                <h3 className="text-sm font-bold text-slate-900">Add / Adjust Shift Log</h3>
              </div>
              <button onClick={() => setShowAdjustmentModal(false)} className="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <form onSubmit={handleAdjustmentSubmit} className="space-y-3.5">
              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Employee</label>
                <div className="p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 flex items-center gap-2">
                  <UserCheck className="w-4 h-4 text-[#1565C0]" />
                  <span>{activeEmployee.first_name} {activeEmployee.last_name} ({activeEmployee.employee_id})</span>
                </div>
              </div>

              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Attendance Date</label>
                <input
                  type="date"
                  required
                  value={adjDate}
                  onChange={(e) => setAdjDate(e.target.value)}
                  className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="text-xs font-bold text-slate-700 block mb-1">Check In Time</label>
                  <input
                    type="text"
                    required
                    value={adjCheckIn}
                    onChange={(e) => setAdjCheckIn(e.target.value)}
                    placeholder="08:00 AM"
                    className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="text-xs font-bold text-slate-700 block mb-1">Check Out Time</label>
                  <input
                    type="text"
                    required
                    value={adjCheckOut}
                    onChange={(e) => setAdjCheckOut(e.target.value)}
                    placeholder="05:00 PM"
                    className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
              </div>

              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Shift Status</label>
                <select
                  value={adjStatus}
                  onChange={(e) => setAdjStatus(e.target.value as AttendanceStatus)}
                  className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="present">On Time / Present</option>
                  <option value="late">Late Arrival</option>
                  <option value="absent">Excused / Absent</option>
                  <option value="half_day">Half Day Shift</option>
                </select>
              </div>

              <div>
                <label className="text-xs font-bold text-slate-700 block mb-1">Notes / Reason</label>
                <input
                  type="text"
                  value={adjNotes}
                  onChange={(e) => setAdjNotes(e.target.value)}
                  placeholder="e.g. Regular biometric shift completion"
                  className="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>

              <div className="flex items-center gap-2 pt-2">
                <button
                  type="submit"
                  className="flex-1 py-2.5 bg-[#1565C0] hover:bg-[#0d47a1] text-white font-bold text-xs rounded-xl shadow-xs transition-colors"
                >
                  Save Attendance Record
                </button>
                <button
                  type="button"
                  onClick={() => setShowAdjustmentModal(false)}
                  className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* PHOTO PROOF MODAL (With McPILLAB Watermark & GPS Inspection) */}
      {selectedPhotoModal && (
        <div className="fixed inset-0 z-50 bg-black/90 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-slate-900 text-white rounded-3xl max-w-lg w-full p-5 border border-white/10 space-y-4 animate-in zoom-in-95">
            <div className="flex items-center justify-between pb-2 border-b border-white/10">
              <div className="flex items-center gap-2">
                <ShieldCheck className="w-4 h-4 text-teal-400" />
                <h3 className="text-sm font-bold text-white">Biometric Attendance Proof</h3>
              </div>
              <button
                onClick={() => setSelectedPhotoModal(null)}
                className="text-slate-400 hover:text-white text-sm"
              >
                ✕
              </button>
            </div>

            <div className="rounded-2xl overflow-hidden border border-white/20 bg-black">
              <img
                src={selectedPhotoModal.photo_path}
                alt={selectedPhotoModal.employee_name}
                className="w-full h-auto object-contain max-h-[55vh] mx-auto block"
              />
            </div>

            <div className="bg-white/5 rounded-xl p-3.5 border border-white/10 space-y-1.5 text-xs text-slate-300">
              <div className="flex justify-between">
                <span className="text-slate-400">Employee:</span>
                <span className="font-bold text-white">{selectedPhotoModal.employee_name}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-400">Timestamp:</span>
                <span className="font-mono text-emerald-400">{selectedPhotoModal.capture_date} at {selectedPhotoModal.capture_time}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-400">Location:</span>
                <span className="text-slate-200 text-right max-w-[280px] truncate">{selectedPhotoModal.location_address}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-400">Telemetry:</span>
                <span className="font-mono text-slate-300">
                  {selectedPhotoModal.azimuth || 'N 0°'} &bull; {selectedPhotoModal.temperature?.toFixed(1) || '36.4'}°C &bull; {selectedPhotoModal.latitude?.toFixed(4)}°N, {selectedPhotoModal.longitude?.toFixed(4)}°E
                </span>
              </div>
            </div>

            <button
              onClick={() => setSelectedPhotoModal(null)}
              className="w-full py-2.5 bg-white/10 hover:bg-white/20 text-white font-bold text-xs rounded-xl transition-colors"
            >
              Close Proof
            </button>
          </div>
        </div>
      )}
    </div>
  );
};
