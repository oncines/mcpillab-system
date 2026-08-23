import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { AttendanceRecord, AttendanceStatus } from '../../types';
import {
  Clock,
  Calendar,
  CheckCircle2,
  AlertCircle,
  Camera,
  Users,
  Search,
  Filter,
  Plus,
  ArrowRight,
  TrendingUp,
} from 'lucide-react';

export const AttendanceView: React.FC = () => {
  const {
    attendanceRecords,
    employees,
    markAttendance,
    updateAttendanceStatus,
    setActiveTab,
    searchQuery,
  } = useApp();

  const [dateFilter, setDateFilter] = useState(new Date().toISOString().split('T')[0]);
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [localSearch, setLocalSearch] = useState('');
  const [showManualModal, setShowManualModal] = useState(false);

  // Manual entry state
  const [selectedEmpId, setSelectedEmpId] = useState<number>(employees[0]?.id || 1);
  const [entryDate, setEntryDate] = useState(new Date().toISOString().split('T')[0]);
  const [checkIn, setCheckIn] = useState('08:00');
  const [checkOut, setCheckOut] = useState('17:00');
  const [status, setStatus] = useState<AttendanceStatus>('present');
  const [notes, setNotes] = useState('Manual shift attendance entry');

  const handleManualSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const emp = employees.find((e) => e.id === Number(selectedEmpId));
    if (!emp) return;

    markAttendance({
      employee_id: emp.id,
      employee_name: `${emp.first_name} ${emp.last_name}`,
      department: emp.department,
      date: entryDate,
      check_in: checkIn,
      check_out: checkOut,
      break_duration: 60,
      total_hours: 8.0,
      status,
      notes,
    });

    setShowManualModal(false);
  };

  const effectiveSearch = (searchQuery || localSearch).toLowerCase();
  const filteredRecords = attendanceRecords.filter((rec) => {
    const matchesDate = !dateFilter || rec.date === dateFilter;
    const matchesStatus = statusFilter === 'all' || rec.status === statusFilter;
    const matchesSearch =
      rec.employee_name.toLowerCase().includes(effectiveSearch) ||
      rec.department.toLowerCase().includes(effectiveSearch) ||
      rec.notes?.toLowerCase().includes(effectiveSearch);
    return matchesDate && matchesStatus && matchesSearch;
  });

  const presentCount = attendanceRecords.filter((a) => a.date === dateFilter && a.status === 'present').length;
  const lateCount = attendanceRecords.filter((a) => a.date === dateFilter && a.status === 'late').length;
  const absentCount = attendanceRecords.filter((a) => a.date === dateFilter && a.status === 'absent').length;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-xl font-bold text-slate-900">Attendance Monitoring & Timesheets</h1>
            <span className="text-xs px-2.5 py-0.5 rounded-full bg-teal-50 text-teal-700 font-bold border border-teal-200">
              {attendanceRecords.length} Logs
            </span>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Review staff clock-in/out timestamps, compliance, working hours, and biometric verifications.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={() => setActiveTab('camera_attendance')}
            className="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-teal-600 to-emerald-600 hover:opacity-95 text-white font-bold text-xs rounded-xl shadow shadow-teal-700/20 transition-all"
          >
            <Camera className="w-4 h-4" />
            Biometric Camera Terminal
          </button>
          <button
            onClick={() => setShowManualModal(true)}
            className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors"
          >
            <Plus className="w-4 h-4" />
            Manual Entry
          </button>
        </div>
      </div>

      {/* Daily Metrics */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <span className="text-emerald-600 font-bold uppercase tracking-wider text-[10px]">Staff Present Today</span>
          <div className="text-xl font-extrabold text-emerald-700 mt-1 font-mono">{presentCount} On Duty</div>
          <div className="text-[11px] text-slate-500 mt-0.5">On time laboratory technicians & managers</div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <span className="text-amber-600 font-bold uppercase tracking-wider text-[10px]">Late Clock-ins</span>
          <div className="text-xl font-extrabold text-amber-700 mt-1 font-mono">{lateCount} Staff</div>
          <div className="text-[11px] text-amber-600 mt-0.5 font-semibold">Logged after 08:00 AM shift marker</div>
        </div>

        <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
          <span className="text-rose-600 font-bold uppercase tracking-wider text-[10px]">Recorded Absences</span>
          <div className="text-xl font-extrabold text-rose-700 mt-1 font-mono">{absentCount} Personnel</div>
          <div className="text-[11px] text-slate-500 mt-0.5">Leave of absence or unexcused</div>
        </div>
      </div>

      {/* Filter and Date Bar */}
      <div className="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div className="flex flex-wrap items-center gap-2">
          <div className="flex items-center gap-1.5 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg text-xs">
            <Calendar className="w-3.5 h-3.5 text-slate-500" />
            <input
              type="date"
              value={dateFilter}
              onChange={(e) => setDateFilter(e.target.value)}
              className="bg-transparent font-semibold text-slate-800 focus:outline-none"
            />
            {dateFilter && (
              <button
                onClick={() => setDateFilter('')}
                className="text-[10px] text-teal-600 font-bold hover:underline ml-1"
              >
                All Dates
              </button>
            )}
          </div>

          <div className="flex items-center gap-1">
            {['all', 'present', 'late', 'absent', 'half_day'].map((st) => (
              <button
                key={st}
                onClick={() => setStatusFilter(st)}
                className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-colors ${
                  statusFilter === st
                    ? 'bg-slate-900 text-white shadow-sm'
                    : 'bg-slate-100 hover:bg-slate-200 text-slate-600 capitalize'
                }`}
              >
                {st === 'all' ? 'All' : st.replace('_', ' ')}
              </button>
            ))}
          </div>
        </div>

        <div className="relative w-full sm:w-64">
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search employee, department..."
            value={localSearch}
            onChange={(e) => setLocalSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
          />
        </div>
      </div>

      {/* Attendance Table */}
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
              <tr>
                <th className="p-4">Date</th>
                <th className="p-4">Employee Name</th>
                <th className="p-4">Department</th>
                <th className="p-4 text-center">Clock-In</th>
                <th className="p-4 text-center">Clock-Out</th>
                <th className="p-4 text-center">Break</th>
                <th className="p-4 text-center">Total Hours</th>
                <th className="p-4">Status</th>
                <th className="p-4">Verification Notes</th>
                <th className="p-4 text-right">Quick Edit</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {filteredRecords.length === 0 ? (
                <tr>
                  <td colSpan={10} className="p-8 text-center text-slate-400">
                    No attendance records found for this date.
                  </td>
                </tr>
              ) : (
                filteredRecords.map((rec) => (
                  <tr key={rec.id} className="hover:bg-slate-50/80 transition-colors">
                    <td className="p-4 font-mono text-slate-600">{rec.date}</td>
                    <td className="p-4 font-bold text-slate-900">{rec.employee_name}</td>
                    <td className="p-4 text-slate-600">{rec.department}</td>
                    <td className="p-4 text-center font-mono font-semibold text-emerald-700">
                      {rec.check_in || '--:--'}
                    </td>
                    <td className="p-4 text-center font-mono font-semibold text-slate-600">
                      {rec.check_out || '--:--'}
                    </td>
                    <td className="p-4 text-center text-slate-500">{rec.break_duration}m</td>
                    <td className="p-4 text-center font-mono font-bold text-slate-800">{rec.total_hours}h</td>
                    <td className="p-4">
                      <span
                        className={`inline-flex px-2.5 py-1 rounded-full text-[11px] font-bold capitalize ${
                          rec.status === 'present'
                            ? 'bg-emerald-100 text-emerald-800'
                            : rec.status === 'late'
                            ? 'bg-amber-100 text-amber-800'
                            : rec.status === 'absent'
                            ? 'bg-rose-100 text-rose-800'
                            : 'bg-purple-100 text-purple-800'
                        }`}
                      >
                        {rec.status.replace('_', ' ')}
                      </span>
                    </td>
                    <td className="p-4 text-slate-500 text-[11px] max-w-xs truncate">{rec.notes}</td>
                    <td className="p-4 text-right">
                      <select
                        value={rec.status}
                        onChange={(e) => updateAttendanceStatus(rec.id, e.target.value as AttendanceStatus)}
                        className="text-[11px] font-semibold bg-slate-100 border border-slate-200 rounded px-2 py-1"
                      >
                        <option value="present">Present</option>
                        <option value="late">Late</option>
                        <option value="absent">Absent</option>
                        <option value="half_day">Half Day</option>
                      </select>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* MODAL: Manual Entry */}
      {showManualModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
              <div className="flex items-center gap-2">
                <Clock className="w-5 h-5 text-teal-600" />
                <h2 className="font-bold text-slate-900 text-base">Record Attendance Entry</h2>
              </div>
              <button
                onClick={() => setShowManualModal(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleManualSubmit} className="space-y-4 text-xs">
              <div>
                <label className="block font-bold text-slate-700 mb-1">Select Employee</label>
                <select
                  value={selectedEmpId}
                  onChange={(e) => setSelectedEmpId(Number(e.target.value))}
                  className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                >
                  {employees.map((emp) => (
                    <option key={emp.id} value={emp.id}>
                      {emp.first_name} {emp.last_name} ({emp.employee_id} - {emp.department})
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Attendance Date</label>
                  <input
                    type="date"
                    value={entryDate}
                    onChange={(e) => setEntryDate(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Status</label>
                  <select
                    value={status}
                    onChange={(e) => setStatus(e.target.value as any)}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  >
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="absent">Absent</option>
                    <option value="half_day">Half Day</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Check In Time</label>
                  <input
                    type="time"
                    value={checkIn}
                    onChange={(e) => setCheckIn(e.target.value)}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono font-bold"
                  />
                </div>
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Check Out Time</label>
                  <input
                    type="time"
                    value={checkOut}
                    onChange={(e) => setCheckOut(e.target.value)}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono font-bold"
                  />
                </div>
              </div>

              <div>
                <label className="block font-bold text-slate-700 mb-1">Notes / Remarks</label>
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
                  onClick={() => setShowManualModal(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow"
                >
                  Save Timesheet Entry
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
