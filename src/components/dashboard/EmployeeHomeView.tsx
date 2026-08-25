import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import {
  Clock,
  Camera,
  Boxes,
  Truck,
  FileText,
  BarChart3,
  Calendar,
  CheckCircle2,
  AlertCircle,
  TrendingUp,
  MapPin,
  Thermometer,
  ShieldCheck,
  Plus,
  ThumbsUp,
  MessageSquare,
  Share2,
  Sparkles,
  ArrowRight,
  ExternalLink,
  ChevronRight,
  Send,
  User as UserIcon,
  FlaskConical,
  Award,
  CalendarCheck,
  BellRing,
} from 'lucide-react';

export const EmployeeHomeView: React.FC = () => {
  const {
    currentUser,
    purchaseOrders,
    inventory,
    deliveries,
    employees,
    attendanceRecords,
    cameraLogs,
    setActiveTab,
  } = useApp();

  const [postLikes, setPostLikes] = useState<{ [key: string]: boolean }>({
    welcome: false,
    attendance: true,
    delivery: false,
    inventory: false,
  });

  const [quickNotes, setQuickNotes] = useState<string[]>([]);
  const [noteInput, setNoteInput] = useState('');
  const [showNoteForm, setShowNoteForm] = useState(false);

  // Time & greeting
  const currentHour = new Date().getHours();
  const greeting =
    currentHour < 12
      ? 'Good morning'
      : currentHour < 18
      ? 'Good afternoon'
      : 'Good evening';

  const employeeName = currentUser?.full_name || 'Laboratory Employee';
  const firstName = employeeName.split(' ')[0];

  // Employee's own attendance today
  const myEmpRecord = employees.find(
    (e) => e.email === currentUser?.email || e.id === currentUser?.id
  );
  const todayStr = new Date().toISOString().split('T')[0];
  const myTodayAttendance = attendanceRecords.find(
    (a) =>
      (a.employee_id === currentUser?.id ||
        (myEmpRecord && a.employee_id === myEmpRecord.id)) &&
      a.date === todayStr
  );

  const myRecentCameraLog = cameraLogs.find(
    (c) =>
      c.employee_name.toLowerCase().includes(firstName.toLowerCase()) ||
      (myEmpRecord && c.employee_id === myEmpRecord.id)
  ) || cameraLogs[0];

  const lowStockCount = inventory.filter(
    (i) => i.total_stock <= i.min_stock_level
  ).length;
  const recentDeliveries = deliveries.slice(0, 4);

  const toggleLike = (key: string) => {
    setPostLikes((prev) => ({ ...prev, [key]: !prev[key] }));
  };

  const handleAddQuickNote = (e: React.FormEvent) => {
    e.preventDefault();
    if (!noteInput.trim()) return;
    setQuickNotes((prev) => [noteInput.trim(), ...prev]);
    setNoteInput('');
    setShowNoteForm(false);
  };

  return (
    <div className="space-y-6">
      {/* 1. Header Banner & Quick Shift Overview */}
      <div className="bg-gradient-to-r from-teal-900 via-teal-800 to-slate-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
        <div className="absolute right-0 top-0 bottom-0 w-1/3 bg-gradient-to-l from-emerald-500/10 to-transparent pointer-events-none" />
        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
          <div className="space-y-1">
            <div className="flex items-center gap-2 text-teal-300 text-xs font-semibold uppercase tracking-wider">
              <FlaskConical className="w-4 h-4" />
              McPIL Pharmaceutical Laboratory • Employee Workspace
            </div>
            <h1 className="text-2xl lg:text-3xl font-extrabold tracking-tight">
              {greeting}, {firstName}!
            </h1>
            <p className="text-sm text-teal-100/80 max-w-xl">
              Welcome to your digital workstation. Review shift attendance, access live inventory, view real-time delivery logs, and collaborate with your team.
            </p>
          </div>

          <div className="flex flex-wrap items-center gap-2.5">
            <button
              onClick={() => setActiveTab('camera_attendance')}
              className="px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-white font-extrabold text-xs rounded-xl shadow-lg shadow-teal-950/30 flex items-center gap-2 transition-all active:scale-95"
            >
              <Camera className="w-4 h-4" />
              Clock In / Camera
            </button>
            <button
              onClick={() => setActiveTab('attendance')}
              className="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white font-bold text-xs rounded-xl border border-white/20 flex items-center gap-2 transition-all"
            >
              <CalendarCheck className="w-4 h-4" />
              Attendance Log
            </button>
          </div>
        </div>
      </div>

      {/* 2. Interactive Stories / Quick Action Carousel */}
      <div>
        <div className="flex items-center justify-between mb-3 px-1">
          <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">
            Quick Stations & Feeds
          </span>
          <span className="text-[11px] text-teal-700 font-semibold">
            Employee Role Active
          </span>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
          {/* Story 1: Clock In / Camera */}
          <div
            onClick={() => setActiveTab('camera_attendance')}
            className="group bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-md hover:border-teal-400 transition-all cursor-pointer flex flex-col h-40"
          >
            <div className="flex-1 bg-gradient-to-br from-blue-50 to-teal-100 flex items-center justify-center relative p-3">
              <div className="w-12 h-12 rounded-full bg-teal-600 text-white flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                <Camera className="w-6 h-6" />
              </div>
              <span className="absolute top-2 right-2 px-1.5 py-0.5 rounded bg-emerald-600 text-white font-mono text-[9px] font-bold animate-pulse">
                LIVE
              </span>
            </div>
            <div className="p-2.5 bg-white border-t border-slate-100 text-center">
              <span className="text-xs font-bold text-slate-900 block truncate">
                Clock In Camera
              </span>
              <span className="text-[10px] text-teal-700 font-medium">
                Biometric Terminal
              </span>
            </div>
          </div>

          {/* Story 2: Inventory & Reagents */}
          <div
            onClick={() => setActiveTab('inventory')}
            className="group bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-md hover:border-emerald-400 transition-all cursor-pointer flex flex-col h-40"
          >
            <div className="flex-1 bg-gradient-to-br from-emerald-50 to-green-100 flex items-center justify-center p-3">
              <div className="w-12 h-12 rounded-full bg-emerald-600 text-white flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                <Boxes className="w-6 h-6" />
              </div>
            </div>
            <div className="p-2.5 bg-white border-t border-slate-100 text-center">
              <span className="text-xs font-bold text-slate-900 block truncate">
                Lab Inventory
              </span>
              <span className="text-[10px] text-slate-500 font-medium">
                {inventory.length} Chemical Lots
              </span>
            </div>
          </div>

          {/* Story 3: Attendance History */}
          <div
            onClick={() => setActiveTab('attendance')}
            className="group bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-md hover:border-purple-400 transition-all cursor-pointer flex flex-col h-40"
          >
            <div className="flex-1 bg-gradient-to-br from-purple-50 to-indigo-100 flex items-center justify-center p-3">
              <div className="w-12 h-12 rounded-full bg-purple-600 text-white flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                <CalendarCheck className="w-6 h-6" />
              </div>
            </div>
            <div className="p-2.5 bg-white border-t border-slate-100 text-center">
              <span className="text-xs font-bold text-slate-900 block truncate">
                Timesheet & Logs
              </span>
              <span className="text-[10px] text-slate-500 font-medium">
                Shift Records
              </span>
            </div>
          </div>

          {/* Story 4: Live Delivery Tracking */}
          <div
            onClick={() => setActiveTab('delivery_tracking')}
            className="group bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-md hover:border-amber-400 transition-all cursor-pointer flex flex-col h-40"
          >
            <div className="flex-1 bg-gradient-to-br from-amber-50 to-orange-100 flex items-center justify-center p-3">
              <div className="w-12 h-12 rounded-full bg-amber-600 text-white flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                <Truck className="w-6 h-6" />
              </div>
            </div>
            <div className="p-2.5 bg-white border-t border-slate-100 text-center">
              <span className="text-xs font-bold text-slate-900 block truncate">
                Delivery Tracker
              </span>
              <span className="text-[10px] text-slate-500 font-medium">
                Cold-Chain GPS
              </span>
            </div>
          </div>

          {/* Story 5: Analytics & Reports */}
          <div
            onClick={() => setActiveTab('reports')}
            className="group bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs hover:shadow-md hover:border-blue-400 transition-all cursor-pointer flex flex-col h-40"
          >
            <div className="flex-1 bg-gradient-to-br from-sky-50 to-blue-100 flex items-center justify-center p-3">
              <div className="w-12 h-12 rounded-full bg-sky-600 text-white flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                <BarChart3 className="w-6 h-6" />
              </div>
            </div>
            <div className="p-2.5 bg-white border-t border-slate-100 text-center">
              <span className="text-xs font-bold text-slate-900 block truncate">
                Lab Reports
              </span>
              <span className="text-[10px] text-slate-500 font-medium">
                QC & Requisition
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* 3. Main Grid: Employee Feed (Left) & Right Utility Sidebar (Right) */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* Left Feed Section (8 Cols) */}
        <div className="lg:col-span-8 space-y-5">
          {/* Quick Composer Box */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4 space-y-3">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-full bg-teal-700 text-white font-bold flex items-center justify-center shrink-0 ring-2 ring-teal-100">
                {firstName.slice(0, 2).toUpperCase()}
              </div>
              <input
                type="text"
                value={noteInput}
                onChange={(e) => setNoteInput(e.target.value)}
                onFocus={() => setShowNoteForm(true)}
                placeholder={`Post shift notes or lab logs, ${firstName}...`}
                className="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-50 focus:bg-white text-xs border border-slate-200 rounded-full focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all placeholder:text-slate-400"
              />
              {showNoteForm && (
                <button
                  onClick={handleAddQuickNote}
                  className="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-full flex items-center gap-1.5 shadow-sm"
                >
                  <Send className="w-3 h-3" /> Post
                </button>
              )}
            </div>

            <div className="pt-2 border-t border-slate-100 flex items-center justify-around text-xs">
              <button
                onClick={() => setActiveTab('camera_attendance')}
                className="flex items-center gap-2 py-1.5 px-3 rounded-lg hover:bg-slate-50 text-slate-700 font-semibold transition-colors"
              >
                <Clock className="w-4 h-4 text-rose-500" />
                <span>Clock In</span>
              </button>
              <button
                onClick={() => setActiveTab('inventory')}
                className="flex items-center gap-2 py-1.5 px-3 rounded-lg hover:bg-slate-50 text-slate-700 font-semibold transition-colors"
              >
                <Boxes className="w-4 h-4 text-emerald-500" />
                <span>Inventory</span>
              </button>
              <button
                onClick={() => setActiveTab('delivery_tracking')}
                className="flex items-center gap-2 py-1.5 px-3 rounded-lg hover:bg-slate-50 text-slate-700 font-semibold transition-colors"
              >
                <Truck className="w-4 h-4 text-blue-500" />
                <span>Shipments</span>
              </button>
            </div>
          </div>

          {/* User's custom posted notes */}
          {quickNotes.length > 0 && (
            <div className="space-y-3">
              {quickNotes.map((note, idx) => (
                <div
                  key={idx}
                  className="bg-white rounded-xl border border-slate-200 shadow-sm p-4 animate-in fade-in"
                >
                  <div className="flex items-center justify-between mb-2">
                    <div className="flex items-center gap-2">
                      <div className="w-7 h-7 rounded-full bg-teal-700 text-white font-bold text-xs flex items-center justify-center">
                        {firstName.slice(0, 2).toUpperCase()}
                      </div>
                      <div>
                        <span className="text-xs font-bold text-slate-900">
                          {employeeName}
                        </span>
                        <span className="text-[10px] text-slate-400 block font-mono">
                          Just now • Staff Note
                        </span>
                      </div>
                    </div>
                  </div>
                  <p className="text-xs text-slate-800 leading-relaxed pl-9">
                    {note}
                  </p>
                </div>
              ))}
            </div>
          )}

          {/* Card 1: Official Welcome & Lab Operations Feed */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-4 flex items-center justify-between border-b border-slate-100">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-gradient-to-br from-teal-600 to-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                  MC
                </div>
                <div>
                  <div className="text-xs font-bold text-slate-900 flex items-center gap-1.5">
                    McPIL Pharmaceutical Laboratory
                    <ShieldCheck className="w-3.5 h-3.5 text-teal-600" />
                  </div>
                  <span className="text-[10px] text-slate-400">
                    System Broadcast • Today
                  </span>
                </div>
              </div>
              <span className="text-[11px] px-2.5 py-0.5 rounded-full bg-teal-50 text-teal-700 font-bold border border-teal-200/60">
                Official
              </span>
            </div>

            {/* Banner block inside card */}
            <div className="p-6 bg-gradient-to-r from-teal-700 to-emerald-800 text-white text-center space-y-1.5">
              <div className="text-lg sm:text-xl font-black">
                {greeting}, {firstName}!
              </div>
              <p className="text-xs text-teal-100/90 max-w-md mx-auto">
                Good Laboratory Practices (GLP) standards are active. Please ensure all chemical inventory adjustments and biometric attendances are recorded promptly.
              </p>
            </div>

            {/* Quick stats row */}
            <div className="grid grid-cols-3 border-y border-slate-100 bg-slate-50 divide-x divide-slate-100 text-center py-3">
              <div>
                <span className="text-lg font-black text-teal-700 block">
                  {purchaseOrders.length}
                </span>
                <span className="text-[10px] text-slate-500 font-bold uppercase">
                  Purchase Orders
                </span>
              </div>
              <div>
                <span className="text-lg font-black text-amber-600 block">
                  {deliveries.filter((d) => d.status === 'in_transit').length}
                </span>
                <span className="text-[10px] text-slate-500 font-bold uppercase">
                  Active Shipments
                </span>
              </div>
              <div>
                <span className="text-lg font-black text-slate-800 block">
                  {employees.length}
                </span>
                <span className="text-[10px] text-slate-500 font-bold uppercase">
                  Team Members
                </span>
              </div>
            </div>

            {/* Social reaction footer */}
            <div className="p-3 bg-white flex items-center justify-between text-xs text-slate-500 border-t border-slate-100">
              <button
                onClick={() => toggleLike('welcome')}
                className={`flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-colors font-bold ${
                  postLikes.welcome
                    ? 'text-teal-600 bg-teal-50'
                    : 'hover:bg-slate-100 text-slate-600'
                }`}
              >
                <ThumbsUp className="w-3.5 h-3.5" />
                <span>{postLikes.welcome ? 'Liked (13)' : 'Like (12)'}</span>
              </button>
              <button
                onClick={() => setActiveTab('inventory')}
                className="flex items-center gap-1 text-teal-600 font-bold hover:underline"
              >
                View Inventory <ChevronRight className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          {/* Card 2: HR Attendance Reminder Feed */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-4 flex items-center justify-between border-b border-slate-100">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-gradient-to-br from-amber-500 to-rose-500 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                  <BellRing className="w-4 h-4" />
                </div>
                <div>
                  <div className="text-xs font-bold text-slate-900">
                    HR & Compliance Department
                  </div>
                  <span className="text-[10px] text-slate-400 font-mono">
                    Shift Notification • {new Date().toLocaleDateString()}
                  </span>
                </div>
              </div>
              <span className="text-[10px] px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-bold border border-emerald-200">
                Attendance Duty
              </span>
            </div>

            <div className="p-5 bg-gradient-to-r from-emerald-600 to-teal-700 text-white space-y-2">
              <div className="text-base font-extrabold flex items-center gap-2">
                <Clock className="w-5 h-5 text-emerald-200" />
                Don't forget to record your attendance today!
              </div>
              <p className="text-xs text-emerald-100 leading-relaxed max-w-lg">
                Snap your verified biometric photo with automatic GPS geo-fencing and thermal body temperature logging at the camera terminal.
              </p>
            </div>

            <div className="p-4 bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div className="text-xs text-slate-600">
                <span className="font-bold text-slate-900">Your Status Today: </span>
                {myTodayAttendance ? (
                  <span className="text-emerald-700 font-bold inline-flex items-center gap-1 ml-1">
                    <CheckCircle2 className="w-3.5 h-3.5" /> Checked In ({myTodayAttendance.check_in || '8:00 AM'})
                  </span>
                ) : (
                  <span className="text-amber-700 font-bold inline-flex items-center gap-1 ml-1">
                    <Clock className="w-3.5 h-3.5" /> Ready to Check-In
                  </span>
                )}
              </div>

              <button
                onClick={() => setActiveTab('camera_attendance')}
                className="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-extrabold text-xs rounded-xl shadow-sm flex items-center justify-center gap-1.5 transition-all"
              >
                <Camera className="w-4 h-4" /> Open Camera Check-In
              </button>
            </div>
          </div>

          {/* Card 3: Recent Shipments & Logistics Feed */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="p-4 flex items-center justify-between border-b border-slate-100">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-sm">
                  <Truck className="w-5 h-5" />
                </div>
                <div>
                  <div className="text-xs font-bold text-slate-900">
                    Cold-Chain Logistics & Delivery Updates
                  </div>
                  <span className="text-[10px] text-slate-400">
                    Real-time carrier transit notifications
                  </span>
                </div>
              </div>
              <button
                onClick={() => setActiveTab('delivery_tracking')}
                className="text-xs font-bold text-teal-600 hover:underline"
              >
                All Shipments
              </button>
            </div>

            <div className="divide-y divide-slate-100">
              {recentDeliveries.map((del) => (
                <div
                  key={del.id}
                  onClick={() => setActiveTab('delivery_tracking')}
                  className="p-3.5 hover:bg-slate-50/80 transition-colors flex items-center justify-between cursor-pointer"
                >
                  <div className="flex items-start gap-3">
                    <div className="p-2 rounded-lg bg-teal-50 text-teal-700 font-mono text-xs font-bold shrink-0 mt-0.5">
                      <Truck className="w-4 h-4" />
                    </div>
                    <div>
                      <div className="text-xs font-bold text-slate-900">
                        {del.delivery_number} • {del.supplier_name}
                      </div>
                      <div className="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                        <span className="font-mono">{del.tracking_number}</span>
                        <span>&bull;</span>
                        <span>{del.carrier}</span>
                        {del.temperature_celsius !== undefined && (
                          <span className="text-emerald-700 font-mono font-bold">
                            {del.temperature_celsius}°C
                          </span>
                        )}
                      </div>
                    </div>
                  </div>

                  <span
                    className={`text-[10px] px-2 py-0.5 rounded-full font-bold uppercase ${
                      del.status === 'delivered'
                        ? 'bg-emerald-100 text-emerald-800'
                        : del.status === 'in_transit'
                        ? 'bg-blue-100 text-blue-800'
                        : 'bg-amber-100 text-amber-800'
                    }`}
                  >
                    {del.status.replace('_', ' ')}
                  </span>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Right Sidebar Section (4 Cols) */}
        <div className="lg:col-span-4 space-y-5">
          {/* Quick Access Utility Box */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4 space-y-3">
            <h3 className="text-xs font-bold text-slate-400 uppercase tracking-wider">
              Quick Shortcuts
            </h3>
            <div className="space-y-1.5 text-xs">
              <button
                onClick={() => setActiveTab('camera_attendance')}
                className="w-full flex items-center justify-between p-2.5 rounded-lg hover:bg-slate-50 text-slate-700 font-bold transition-colors group text-left"
              >
                <span className="flex items-center gap-2.5">
                  <Clock className="w-4 h-4 text-teal-600" />
                  Clock In / Out Station
                </span>
                <ChevronRight className="w-3.5 h-3.5 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
              </button>

              <button
                onClick={() => setActiveTab('attendance')}
                className="w-full flex items-center justify-between p-2.5 rounded-lg hover:bg-slate-50 text-slate-700 font-bold transition-colors group text-left"
              >
                <span className="flex items-center gap-2.5">
                  <Calendar className="w-4 h-4 text-purple-600" />
                  Attendance Timesheet Log
                </span>
                <ChevronRight className="w-3.5 h-3.5 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
              </button>

              <button
                onClick={() => setActiveTab('inventory')}
                className="w-full flex items-center justify-between p-2.5 rounded-lg hover:bg-slate-50 text-slate-700 font-bold transition-colors group text-left"
              >
                <span className="flex items-center gap-2.5">
                  <Boxes className="w-4 h-4 text-emerald-600" />
                  Chemical & Reagent Ledger
                </span>
                <ChevronRight className="w-3.5 h-3.5 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
              </button>

              <button
                onClick={() => setActiveTab('delivery_tracking')}
                className="w-full flex items-center justify-between p-2.5 rounded-lg hover:bg-slate-50 text-slate-700 font-bold transition-colors group text-left"
              >
                <span className="flex items-center gap-2.5">
                  <Truck className="w-4 h-4 text-blue-600" />
                  Delivery GPS Tracking
                </span>
                <ChevronRight className="w-3.5 h-3.5 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
              </button>

              <button
                onClick={() => setActiveTab('reports')}
                className="w-full flex items-center justify-between p-2.5 rounded-lg hover:bg-slate-50 text-slate-700 font-bold transition-colors group text-left"
              >
                <span className="flex items-center gap-2.5">
                  <BarChart3 className="w-4 h-4 text-amber-600" />
                  Laboratory Reports & QC
                </span>
                <ChevronRight className="w-3.5 h-3.5 text-slate-400 group-hover:translate-x-0.5 transition-transform" />
              </button>
            </div>
          </div>

          {/* Your Daily Overview KPI Widget */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4 space-y-3">
            <h3 className="text-xs font-bold text-slate-400 uppercase tracking-wider">
              Shift Pulse & Overview
            </h3>

            <div className="space-y-2.5 text-xs">
              <div className="flex items-center justify-between py-1.5 border-b border-slate-100">
                <span className="text-slate-500">Purchase Orders</span>
                <span className="font-extrabold text-slate-900 font-mono">
                  {purchaseOrders.length} records
                </span>
              </div>

              <div className="flex items-center justify-between py-1.5 border-b border-slate-100">
                <span className="text-slate-500">Pending Deliveries</span>
                <span
                  className={`font-extrabold font-mono ${
                    deliveries.filter((d) => d.status === 'pending').length > 0
                      ? 'text-amber-600'
                      : 'text-emerald-600'
                  }`}
                >
                  {deliveries.filter((d) => d.status === 'pending').length} shipments
                </span>
              </div>

              <div className="flex items-center justify-between py-1.5 border-b border-slate-100">
                <span className="text-slate-500">Low Chemical Stock</span>
                <span
                  className={`font-extrabold font-mono ${
                    lowStockCount > 0 ? 'text-rose-600' : 'text-emerald-600'
                  }`}
                >
                  {lowStockCount} items
                </span>
              </div>

              <div className="flex items-center justify-between py-1.5">
                <span className="text-slate-500">Active Laboratory Team</span>
                <span className="font-extrabold text-teal-700 font-mono">
                  {employees.length} staff members
                </span>
              </div>
            </div>
          </div>

          {/* Biometric Snapshot Preview Widget */}
          {myRecentCameraLog && (
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4 space-y-3">
              <div className="flex items-center justify-between">
                <h3 className="text-xs font-bold text-slate-400 uppercase tracking-wider">
                  Verified Biometric Log
                </h3>
                <span className="text-[10px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold">
                  Verified
                </span>
              </div>

              <div className="flex items-start gap-3 bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                <img
                  src={myRecentCameraLog.photo_path}
                  onError={(e) => {
                    (e.currentTarget as HTMLImageElement).src =
                      'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=200&auto=format&fit=crop&q=80';
                  }}
                  alt={myRecentCameraLog.employee_name}
                  className="w-14 h-14 rounded-lg object-cover ring-1 ring-teal-500 shrink-0"
                />
                <div className="min-w-0 text-xs space-y-0.5">
                  <div className="font-bold text-slate-900 truncate">
                    {myRecentCameraLog.employee_name}
                  </div>
                  <div className="text-[11px] text-slate-500 font-mono">
                    {myRecentCameraLog.capture_date} • {myRecentCameraLog.capture_time}
                  </div>
                  <div className="text-[10px] text-teal-700 font-semibold flex items-center gap-1 truncate" title={myRecentCameraLog.location_address}>
                    <MapPin className="w-3 h-3 text-teal-600 shrink-0" />
                    <span className="truncate">{myRecentCameraLog.location_address}</span>
                  </div>
                  <div className="text-[10px] text-emerald-700 font-bold flex items-center gap-1">
                    <Thermometer className="w-3 h-3 text-rose-500 shrink-0" />
                    {myRecentCameraLog.temperature}°C (Normal)
                  </div>
                </div>
              </div>

              <button
                onClick={() => setActiveTab('camera_attendance')}
                className="w-full py-2 text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 rounded-lg transition-colors text-center block"
              >
                Inspect Live Camera Station
              </button>
            </div>
          )}

          {/* Team Contacts & Direct Roles */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4 space-y-3">
            <h3 className="text-xs font-bold text-slate-400 uppercase tracking-wider">
              Department Contacts
            </h3>

            <div className="space-y-2 text-xs">
              <div className="flex items-center gap-2.5 p-2 rounded-lg hover:bg-slate-50 transition-colors">
                <div className="w-8 h-8 rounded-full bg-teal-100 text-teal-800 font-bold flex items-center justify-center text-xs">
                  HR
                </div>
                <div className="min-w-0 flex-1">
                  <div className="font-bold text-slate-900">HR Department</div>
                  <div className="text-[10px] text-slate-400">Personnel & Shift Inquiries</div>
                </div>
                <span className="w-2 h-2 rounded-full bg-emerald-500" />
              </div>

              <div className="flex items-center gap-2.5 p-2 rounded-lg hover:bg-slate-50 transition-colors">
                <div className="w-8 h-8 rounded-full bg-amber-100 text-amber-800 font-bold flex items-center justify-center text-xs">
                  ST
                </div>
                <div className="min-w-0 flex-1">
                  <div className="font-bold text-slate-900">Store Custodian</div>
                  <div className="text-[10px] text-slate-400">Warehouse & Bodega Stock</div>
                </div>
                <span className="w-2 h-2 rounded-full bg-emerald-500" />
              </div>

              <div className="flex items-center gap-2.5 p-2 rounded-lg hover:bg-slate-50 transition-colors">
                <div className="w-8 h-8 rounded-full bg-purple-100 text-purple-800 font-bold flex items-center justify-center text-xs">
                  AD
                </div>
                <div className="min-w-0 flex-1">
                  <div className="font-bold text-slate-900">Lab Administrator</div>
                  <div className="text-[10px] text-slate-400">Clearance & PO Approvals</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
