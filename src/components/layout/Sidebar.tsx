import React from 'react';
import { useApp } from '../../context/AppContext';
import {
  LayoutDashboard,
  Box,
  Camera,
  Calendar,
  Truck,
  BarChart2,
  MessageSquare,
  Settings,
  LogOut,
  Users,
  FileText,
  Receipt,
  History,
} from 'lucide-react';

export const Sidebar: React.FC<{ isOpen?: boolean; setIsOpen?: (val: boolean) => void }> = ({
  isOpen,
  setIsOpen,
}) => {
  const {
    activeTab,
    setActiveTab,
    currentUser,
    logout,
    deliveries,
  } = useApp();

  const isEmployee = currentUser?.role === 'employee';
  const isStore = currentUser?.role === 'store';
  const unreadMessages = 2;

  // Custom styling that exactly mirrors employee_home.php sidebar
  return (
    <aside className="w-56 bg-[#0d1b3e] text-white flex flex-col shrink-0 min-h-screen z-40 select-none border-r border-white/5">
      {/* Brand Header */}
      <div className="flex items-center gap-2.5 p-4 border-b border-white/10 shrink-0">
        <div className="w-9 h-9 rounded-full border-2 border-white/30 overflow-hidden shrink-0 flex items-center justify-center bg-[#1a2a5e]">
          <img
            src="/logo.png"
            alt="McPIL"
            className="w-full h-full object-cover block"
            onError={(e) => {
              (e.currentTarget as HTMLElement).style.display = 'none';
              if (e.currentTarget.nextElementSibling) {
                (e.currentTarget.nextElementSibling as HTMLElement).style.display = 'flex';
              }
            }}
          />
          <span className="text-[10px] font-black text-white hidden">McP</span>
        </div>
        <div className="min-w-0">
          <div className="text-xs font-black tracking-wider text-white uppercase leading-tight">
            McPIL
          </div>
          <div className="text-[9px] text-white/40 tracking-wider uppercase truncate max-w-[120px] mt-0.5">
            Pharmaceutical Lab...
          </div>
        </div>
      </div>

      {/* Nav Menu */}
      <div className="flex-1 px-2.5 py-2 overflow-y-auto space-y-3">
        {/* MAIN SECTION */}
        <div>
          <div className="text-[9.5px] font-bold tracking-widest text-white/30 uppercase px-2.5 py-1.5">
            Main
          </div>
          <div className="space-y-0.5">
            <button
              onClick={() => {
                setActiveTab('dashboard');
                if (setIsOpen) setIsOpen(false);
              }}
              className={`w-full flex items-center gap-2.5 px-2.5 py-2 min-h-[38px] rounded-lg text-[13px] font-medium transition-colors text-left ${
                activeTab === 'dashboard'
                  ? 'bg-white/15 text-white font-semibold shadow-xs'
                  : 'text-white/70 hover:bg-white/5 hover:text-white'
              }`}
            >
              <LayoutDashboard className="w-[18px] h-[18px] shrink-0 text-center" />
              <span>Home</span>
            </button>

            <button
              onClick={() => {
                setActiveTab('inventory');
                if (setIsOpen) setIsOpen(false);
              }}
              className={`w-full flex items-center gap-2.5 px-2.5 py-2 min-h-[38px] rounded-lg text-[13px] font-medium transition-colors text-left ${
                activeTab === 'inventory'
                  ? 'bg-white/15 text-white font-semibold shadow-xs'
                  : 'text-white/70 hover:bg-white/5 hover:text-white'
              }`}
            >
              <Box className="w-[18px] h-[18px] shrink-0 text-center" />
              <span>Inventory</span>
            </button>

            {!isEmployee && (
              <>
                <button
                  onClick={() => {
                    setActiveTab('purchase_orders');
                    if (setIsOpen) setIsOpen(false);
                  }}
                  className={`w-full flex items-center gap-2.5 px-2.5 py-2 min-h-[38px] rounded-lg text-[13px] font-medium transition-colors text-left ${
                    activeTab === 'purchase_orders'
                      ? 'bg-white/15 text-white font-semibold shadow-xs'
                      : 'text-white/70 hover:bg-white/5 hover:text-white'
                  }`}
                >
                  <FileText className="w-[18px] h-[18px] shrink-0 text-center" />
                  <span>Purchase Orders</span>
                </button>
                {!isStore && (
                  <>
                    <button
                      onClick={() => {
                        setActiveTab('invoices');
                        if (setIsOpen) setIsOpen(false);
                      }}
                      className={`w-full flex items-center gap-2.5 px-2.5 py-2 min-h-[38px] rounded-lg text-[13px] font-medium transition-colors text-left ${
                        activeTab === 'invoices'
                          ? 'bg-white/15 text-white font-semibold shadow-xs'
                          : 'text-white/70 hover:bg-white/5 hover:text-white'
                      }`}
                    >
                      <Receipt className="w-[18px] h-[18px] shrink-0 text-center" />
                      <span>Invoices</span>
                    </button>
                    <button
                      onClick={() => {
                        setActiveTab('employees');
                        if (setIsOpen) setIsOpen(false);
                      }}
                      className={`w-full flex items-center gap-2.5 px-2.5 py-2 min-h-[38px] rounded-lg text-[13px] font-medium transition-colors text-left ${
                        activeTab === 'employees'
                          ? 'bg-white/15 text-white font-semibold shadow-xs'
                          : 'text-white/70 hover:bg-white/5 hover:text-white'
                      }`}
                    >
                      <Users className="w-[18px] h-[18px] shrink-0 text-center" />
                      <span>Staff</span>
                    </button>
                  </>
                )}
              </>
            )}
          </div>
        </div>

        {/* ATTENDANCE SECTION */}
        <div>
          <div className="text-[9.5px] font-bold tracking-widest text-white/30 uppercase px-2.5 py-1.5">
            Attendance
          </div>
          <div className="space-y-0.5">
            <button
              onClick={() => {
                setActiveTab('camera_attendance');
                if (setIsOpen) setIsOpen(false);
              }}
              className={`w-full flex items-center gap-2.5 px-2.5 py-2 min-h-[38px] rounded-lg text-[13px] font-medium transition-colors text-left ${
                activeTab === 'camera_attendance'
                  ? 'bg-white/15 text-white font-semibold shadow-xs'
                  : 'text-white/70 hover:bg-white/5 hover:text-white'
              }`}
            >
              <Camera className="w-[18px] h-[18px] shrink-0 text-center" />
              <span>Attendance</span>
            </button>

            <button
              onClick={() => {
                setActiveTab('attendance');
                if (setIsOpen) setIsOpen(false);
              }}
              className={`w-full flex items-center gap-2.5 px-2.5 py-2 min-h-[38px] rounded-lg text-[13px] font-medium transition-colors text-left ${
                activeTab === 'attendance'
                  ? 'bg-white/15 text-white font-semibold shadow-xs'
                  : 'text-white/70 hover:bg-white/5 hover:text-white'
              }`}
            >
              <Calendar className="w-[18px] h-[18px] shrink-0 text-center" />
              <span>Attendance History</span>
            </button>
          </div>
        </div>

        {/* LOGISTICS SECTION */}
        {!isEmployee && (
          <div>
            <div className="text-[9.5px] font-bold tracking-widest text-white/30 uppercase px-2.5 py-1.5">
              Logistics
            </div>
            <div className="space-y-0.5">
              <button
                onClick={() => {
                  setActiveTab('delivery_tracking');
                  if (setIsOpen) setIsOpen(false);
                }}
                className={`w-full flex items-center gap-2.5 px-2.5 py-2 min-h-[38px] rounded-lg text-[13px] font-medium transition-colors text-left ${
                  activeTab === 'delivery_tracking'
                    ? 'bg-white/15 text-white font-semibold shadow-xs'
                    : 'text-white/70 hover:bg-white/5 hover:text-white'
                }`}
              >
                <Truck className="w-[18px] h-[18px] shrink-0 text-center" />
                <span>Delivery Tracking</span>
              </button>

              <button
                onClick={() => {
                  setActiveTab('delivery_history');
                  if (setIsOpen) setIsOpen(false);
                }}
                className={`w-full flex items-center gap-2.5 px-2.5 py-2 min-h-[38px] rounded-lg text-[13px] font-medium transition-colors text-left ${
                  activeTab === 'delivery_history'
                    ? 'bg-white/15 text-white font-semibold shadow-xs'
                    : 'text-white/70 hover:bg-white/5 hover:text-white'
                }`}
              >
                <History className="w-[18px] h-[18px] shrink-0 text-center" />
                <span>Delivery History</span>
              </button>
            </div>
          </div>
        )}

        {/* TOOLS SECTION */}
        <div>
          <div className="text-[9.5px] font-bold tracking-widest text-white/30 uppercase px-2.5 py-1.5">
            Tools
          </div>
          <div className="space-y-0.5">
            <button
              onClick={() => {
                setActiveTab('reports');
                if (setIsOpen) setIsOpen(false);
              }}
              className={`w-full flex items-center gap-2.5 px-2.5 py-2 min-h-[38px] rounded-lg text-[13px] font-medium transition-colors text-left ${
                activeTab === 'reports'
                  ? 'bg-white/15 text-white font-semibold shadow-xs'
                  : 'text-white/70 hover:bg-white/5 hover:text-white'
              }`}
            >
              <BarChart2 className="w-[18px] h-[18px] shrink-0 text-center" />
              <span>Reports</span>
            </button>

            <button
              onClick={() => {
                alert('Team Messages & Chat Interface opened.');
              }}
              className="w-full flex items-center justify-between px-2.5 py-2 min-h-[38px] rounded-lg text-[13px] font-medium transition-colors text-left text-white/70 hover:bg-white/5 hover:text-white"
            >
              <div className="flex items-center gap-2.5">
                <MessageSquare className="w-[18px] h-[18px] shrink-0 text-center" />
                <span>Messages</span>
              </div>
              {unreadMessages > 0 && (
                <span className="bg-[#e5534b] text-white text-[9px] font-bold min-w-[16px] h-4 rounded-full flex items-center justify-center px-1">
                  {unreadMessages}
                </span>
              )}
            </button>
          </div>
        </div>
      </div>

      {/* ACCOUNT / FOOTER SECTION */}
      <div className="p-2.5 border-t border-white/10 shrink-0">
        <div className="text-[9.5px] font-bold tracking-widest text-white/30 uppercase px-2.5 pb-1">
          Account
        </div>
        <div className="space-y-0.5">
          <button
            onClick={() => {
              alert('Account Settings & Preferences');
            }}
            className="w-full flex items-center gap-2.5 px-2.5 py-2 min-h-[38px] rounded-lg text-[13px] font-medium text-white/70 hover:bg-white/5 hover:text-white transition-colors text-left"
          >
            <Settings className="w-[18px] h-[18px] shrink-0 text-center" />
            <span>Settings</span>
          </button>

          <button
            onClick={logout}
            className="w-full flex items-center gap-2.5 px-2.5 py-2 min-h-[38px] rounded-lg text-[13px] font-medium text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 transition-colors text-left"
          >
            <LogOut className="w-[18px] h-[18px] shrink-0 text-center text-rose-400" />
            <span>Logout</span>
          </button>
        </div>
      </div>
    </aside>
  );
};
