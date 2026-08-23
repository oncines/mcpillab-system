import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import {
  Bell,
  Search,
  CheckCircle2,
  AlertTriangle,
  Package,
  Truck,
  Users,
  ChevronDown,
  RefreshCw,
  Sparkles,
  ExternalLink,
  LogOut,
  ShieldCheck,
  FlaskConical,
  Store,
} from 'lucide-react';

export const Navbar: React.FC<{ onToggleSidebar?: () => void }> = () => {
  const {
    currentUser,
    setCurrentUser,
    users,
    notifications,
    markNotificationRead,
    markAllNotificationsRead,
    setActiveTab,
    searchQuery,
    setSearchQuery,
    resetAllData,
    logout,
  } = useApp();

  const [showNotifMenu, setShowNotifMenu] = useState(false);
  const [showUserMenu, setShowUserMenu] = useState(false);

  const unreadCount = notifications.filter((n) => !n.read).length;

  const handleNotificationClick = (notif: typeof notifications[0]) => {
    markNotificationRead(notif.id);
    if (notif.link) {
      setActiveTab(notif.link as any);
    }
    setShowNotifMenu(false);
  };

  const userRole = currentUser?.role || 'employee';
  const roleColors: Record<string, { bg: string; text: string; border: string }> = {
    admin: { bg: 'bg-purple-100', text: 'text-purple-800', border: 'border-purple-200' },
    employee: { bg: 'bg-teal-100', text: 'text-teal-800', border: 'border-teal-200' },
    store: { bg: 'bg-amber-100', text: 'text-amber-800', border: 'border-amber-200' },
    manager: { bg: 'bg-blue-100', text: 'text-blue-800', border: 'border-blue-200' },
  };

  const currentRoleStyle = roleColors[userRole] || roleColors.employee;

  return (
    <header className="bg-white border-b border-slate-200 sticky top-0 z-40 px-4 lg:px-6 py-3 no-print">
      <div className="flex items-center justify-between gap-4">
        {/* Brand */}
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-600 to-emerald-700 flex items-center justify-center text-white font-black text-xl shadow-md shadow-teal-700/20">
            MC
          </div>
          <div>
            <div className="flex items-center gap-2">
              <span className="font-extrabold text-slate-900 tracking-tight text-lg">MCPIL</span>
              <span className="hidden sm:inline-block px-2 py-0.5 text-xs font-semibold uppercase tracking-wider bg-teal-50 text-teal-700 rounded-full border border-teal-200/60">
                Pharma Lab Ops
              </span>
            </div>
            <p className="text-xs text-slate-500 font-medium hidden md:block">
              Pharmaceutical Laboratory Management & QC System
            </p>
          </div>
        </div>

        {/* Search Bar */}
        <div className="flex-1 max-w-md hidden md:block">
          <div className="relative">
            <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              placeholder="Search POs, chemicals, suppliers, staff..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full bg-slate-100/80 hover:bg-slate-100 focus:bg-white text-sm text-slate-800 pl-9 pr-4 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all placeholder:text-slate-400"
            />
            {searchQuery && (
              <button
                onClick={() => setSearchQuery('')}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400 hover:text-slate-600"
              >
                Clear
              </button>
            )}
          </div>
        </div>

        {/* Right action items */}
        <div className="flex items-center gap-2 lg:gap-3">
          {/* Reset Demo Data Button */}
          <button
            onClick={() => {
              if (window.confirm('Reset all operational data back to clean sample defaults?')) {
                resetAllData();
              }
            }}
            title="Reset system sample records"
            className="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition-colors"
          >
            <RefreshCw className="w-4 h-4" />
          </button>

          {/* Notification dropdown */}
          <div className="relative">
            <button
              onClick={() => {
                setShowNotifMenu(!showNotifMenu);
                setShowUserMenu(false);
              }}
              className="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg relative transition-colors"
              title="System Notifications"
            >
              <Bell className="w-5 h-5" />
              {unreadCount > 0 && (
                <span className="absolute top-1 right-1 w-4 h-4 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center ring-2 ring-white animate-pulse">
                  {unreadCount}
                </span>
              )}
            </button>

            {showNotifMenu && (
              <div className="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-xl border border-slate-200 p-3 z-50 animate-in fade-in slide-in-from-top-2 duration-150">
                <div className="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
                  <div className="flex items-center gap-2">
                    <span className="font-bold text-sm text-slate-900">Notifications</span>
                    {unreadCount > 0 && (
                      <span className="text-xs px-2 py-0.5 bg-rose-100 text-rose-700 font-semibold rounded-full">
                        {unreadCount} new
                      </span>
                    )}
                  </div>
                  {unreadCount > 0 && (
                    <button
                      onClick={markAllNotificationsRead}
                      className="text-xs font-semibold text-teal-600 hover:text-teal-700 hover:underline"
                    >
                      Mark all as read
                    </button>
                  )}
                </div>

                <div className="max-h-72 overflow-y-auto space-y-2 divide-y divide-slate-50">
                  {notifications.length === 0 ? (
                    <p className="text-center text-xs text-slate-400 py-6">No notifications</p>
                  ) : (
                    notifications.map((n) => (
                      <div
                        key={n.id}
                        onClick={() => handleNotificationClick(n)}
                        className={`pt-2 first:pt-0 p-2 rounded-lg cursor-pointer transition-colors ${
                          n.read ? 'hover:bg-slate-50 opacity-75' : 'bg-teal-50/50 hover:bg-teal-50 border border-teal-100/60'
                        }`}
                      >
                        <div className="flex items-start gap-2.5">
                          <div className="mt-0.5">
                            {n.type === 'po' && <Package className="w-4 h-4 text-indigo-500" />}
                            {n.type === 'delivery' && <Truck className="w-4 h-4 text-emerald-500" />}
                            {n.type === 'inventory' && <AlertTriangle className="w-4 h-4 text-amber-500" />}
                            {n.type === 'attendance' && <Users className="w-4 h-4 text-teal-500" />}
                            {n.type === 'system' && <CheckCircle2 className="w-4 h-4 text-slate-500" />}
                          </div>
                          <div className="flex-1 min-w-0">
                            <div className="flex items-center justify-between">
                              <p className="text-xs font-bold text-slate-900 truncate">{n.title}</p>
                              <span className="text-[10px] text-slate-400 font-mono">{n.timestamp.slice(11)}</span>
                            </div>
                            <p className="text-xs text-slate-600 mt-0.5 line-clamp-2">{n.message}</p>
                          </div>
                        </div>
                      </div>
                    ))
                  )}
                </div>
              </div>
            )}
          </div>

          {/* User Switcher / Profile dropdown */}
          <div className="relative">
            <button
              onClick={() => {
                setShowUserMenu(!showUserMenu);
                setShowNotifMenu(false);
              }}
              className="flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-lg border border-slate-200 hover:border-slate-300 hover:bg-slate-50 transition-all text-left"
            >
              <img
                src={currentUser?.avatar || 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80'}
                alt={currentUser?.full_name || 'User'}
                className="w-7 h-7 rounded-full object-cover ring-1 ring-slate-200"
              />
              <div className="hidden sm:block">
                <div className="text-xs font-bold text-slate-900 leading-tight flex items-center gap-1.5">
                  {currentUser?.full_name?.split(' ')[0] || 'User'}
                  <span
                    className={`text-[9px] px-1.5 py-0.2 rounded font-semibold uppercase ${
                      userRole === 'admin'
                        ? 'bg-purple-100 text-purple-700'
                        : userRole === 'store'
                        ? 'bg-amber-100 text-amber-700'
                        : 'bg-teal-100 text-teal-700'
                    }`}
                  >
                    {userRole}
                  </span>
                </div>
              </div>
              <ChevronDown className="w-3.5 h-3.5 text-slate-400" />
            </button>

            {showUserMenu && (
              <div className="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-slate-200 p-2 z-50 animate-in fade-in slide-in-from-top-2 duration-150">
                <div className="px-3 py-2.5 bg-slate-50 rounded-lg border border-slate-100 mb-2">
                  <div className="flex items-center justify-between">
                    <p className="text-[10px] uppercase tracking-wider font-bold text-slate-400">Current Session</p>
                    <span
                      className={`text-[10px] px-2 py-0.5 rounded-full font-bold uppercase ${
                        userRole === 'admin'
                          ? 'bg-purple-100 text-purple-700'
                          : userRole === 'store'
                          ? 'bg-amber-100 text-amber-700'
                          : 'bg-teal-100 text-teal-700'
                      }`}
                    >
                      {userRole} clearance
                    </span>
                  </div>
                  <p className="text-sm font-bold text-slate-900 truncate mt-0.5">{currentUser?.full_name}</p>
                  <p className="text-xs text-slate-500 font-mono">{currentUser?.email}</p>
                  {currentUser?.department && (
                    <p className="text-[11px] text-teal-700 font-medium mt-1">Dept: {currentUser.department}</p>
                  )}
                  {currentUser?.store_name && (
                    <p className="text-[11px] text-amber-700 font-medium mt-1">Hub: {currentUser.store_name}</p>
                  )}
                </div>

                <div className="py-1">
                  <p className="px-3 py-1 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                    Switch Active Role
                  </p>
                  <div className="space-y-1 max-h-48 overflow-y-auto">
                    {users.map((u) => (
                      <button
                        key={u.id}
                        onClick={() => {
                          setCurrentUser(u);
                          setShowUserMenu(false);
                        }}
                        className={`w-full flex items-center justify-between px-3 py-2 text-xs rounded-lg transition-colors text-left ${
                          currentUser?.id === u.id
                            ? 'bg-teal-50 text-teal-900 font-bold border border-teal-200'
                            : 'hover:bg-slate-100 text-slate-700 font-medium'
                        }`}
                      >
                        <div className="flex items-center gap-2">
                          <img src={u.avatar} alt={u.full_name} className="w-6 h-6 rounded-full object-cover" />
                          <div>
                            <div className="truncate max-w-[170px]">{u.full_name}</div>
                            <div className="text-[10px] text-slate-400 capitalize">
                              {u.role} &bull; {u.username}
                            </div>
                          </div>
                        </div>
                        {currentUser?.id === u.id && <CheckCircle2 className="w-4 h-4 text-teal-600 shrink-0" />}
                      </button>
                    ))}
                  </div>
                </div>

                <div className="pt-2 mt-2 border-t border-slate-100">
                  <button
                    onClick={() => {
                      setShowUserMenu(false);
                      logout();
                    }}
                    className="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors cursor-pointer"
                  >
                    <LogOut className="w-3.5 h-3.5" />
                    Sign Out / Switch User
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </header>
  );
};
