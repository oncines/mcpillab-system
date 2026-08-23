import React from 'react';
import { useApp, NavTab } from '../../context/AppContext';
import {
  LayoutDashboard,
  FileText,
  Receipt,
  Boxes,
  Users,
  Clock,
  Camera,
  Truck,
  History,
  BarChart3,
  BotMessageSquare,
  Building2,
  ShieldCheck,
  FlaskConical,
  Store,
  LogOut,
} from 'lucide-react';

interface NavItem {
  id: NavTab;
  label: string;
  icon: React.ElementType;
  badge?: number;
  badgeColor?: string;
  category: 'core' | 'lab' | 'logistics' | 'analytics';
  allowedRoles?: ('admin' | 'employee' | 'store')[];
}

export const Sidebar: React.FC<{ isOpen?: boolean; setIsOpen?: (val: boolean) => void }> = ({
  isOpen,
  setIsOpen,
}) => {
  const {
    activeTab,
    setActiveTab,
    purchaseOrders,
    inventory,
    deliveries,
    currentUser,
    logout,
  } = useApp();

  const userRole = (currentUser?.role || 'employee') as 'admin' | 'employee' | 'store';

  const pendingPOCount = purchaseOrders.filter((p) => p.status === 'Pending').length;
  const lowStockCount = inventory.filter((i) => i.total_stock <= i.min_stock_level).length;
  const activeDeliveriesCount = deliveries.filter((d) => d.status === 'in_transit' || d.status === 'pending').length;

  const navItems: NavItem[] = [
    {
      id: 'dashboard',
      label: 'Operations Dashboard',
      icon: LayoutDashboard,
      category: 'core',
      allowedRoles: ['admin', 'employee', 'store'],
    },
    {
      id: 'purchase_orders',
      label: userRole === 'store' ? 'Store Requisitions (PO)' : 'Purchase Orders',
      icon: FileText,
      badge: pendingPOCount > 0 ? pendingPOCount : undefined,
      badgeColor: 'bg-amber-500 text-white',
      category: 'core',
      allowedRoles: ['admin', 'store'],
    },
    {
      id: 'invoices',
      label: 'Purchase Invoices',
      icon: Receipt,
      category: 'core',
      allowedRoles: ['admin'],
    },
    {
      id: 'inventory',
      label: userRole === 'store' ? 'Bodega Stock Ledger' : 'Inventory & Reagents',
      icon: Boxes,
      badge: lowStockCount > 0 ? lowStockCount : undefined,
      badgeColor: 'bg-rose-500 text-white',
      category: 'lab',
      allowedRoles: ['admin', 'employee', 'store'],
    },
    {
      id: 'employees',
      label: 'Staff & Personnel',
      icon: Users,
      category: 'lab',
      allowedRoles: ['admin', 'employee'],
    },
    {
      id: 'attendance',
      label: 'Attendance Monitor',
      icon: Clock,
      category: 'lab',
      allowedRoles: ['admin', 'employee'],
    },
    {
      id: 'camera_attendance',
      label: 'Camera Station (Photo/GPS)',
      icon: Camera,
      badgeColor: 'bg-teal-500 text-white',
      category: 'lab',
      allowedRoles: ['admin', 'employee'],
    },
    {
      id: 'delivery_tracking',
      label: 'Live Delivery Tracker',
      icon: Truck,
      badge: activeDeliveriesCount > 0 ? activeDeliveriesCount : undefined,
      badgeColor: 'bg-emerald-600 text-white',
      category: 'logistics',
      allowedRoles: ['admin', 'store'],
    },
    {
      id: 'delivery_history',
      label: 'Delivery History & Logs',
      icon: History,
      category: 'logistics',
      allowedRoles: ['admin', 'store'],
    },
    {
      id: 'reports',
      label: 'Reports & Analytics',
      icon: BarChart3,
      category: 'analytics',
      allowedRoles: ['admin', 'employee', 'store'],
    },
  ];

  const filteredItems = navItems.filter(
    (item) => !item.allowedRoles || item.allowedRoles.includes(userRole)
  );

  const roleMeta = {
    admin: {
      name: 'Administrator',
      desc: 'Full Oversight & Clearance',
      icon: ShieldCheck,
      badge: 'bg-purple-100 text-purple-800 border-purple-200',
    },
    employee: {
      name: 'Laboratory Employee',
      desc: 'Reagents & Attendance',
      icon: FlaskConical,
      badge: 'bg-teal-100 text-teal-800 border-teal-200',
    },
    store: {
      name: 'Store Custodian',
      desc: 'Warehouse & Deliveries',
      icon: Store,
      badge: 'bg-amber-100 text-amber-800 border-amber-200',
    },
  }[userRole] || {
    name: 'Staff Member',
    desc: 'System Access',
    icon: FlaskConical,
    badge: 'bg-slate-100 text-slate-800 border-slate-200',
  };

  const RoleIcon = roleMeta.icon;

  return (
    <aside className="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0 no-print select-none">
      {/* Role Indicator Banner */}
      <div className="p-3.5 border-b border-slate-100 bg-slate-50/70">
        <div className="flex items-center gap-2.5">
          <div className="p-2 rounded-xl bg-teal-50 text-teal-700 border border-teal-200/50 shrink-0">
            <RoleIcon className="w-4 h-4" />
          </div>
          <div className="min-w-0">
            <div className="text-xs font-bold text-slate-800 truncate">
              {currentUser?.full_name || 'Laboratory Staff'}
            </div>
            <div className="flex items-center gap-1.5 mt-0.5">
              <span className={`text-[10px] px-1.5 py-0.2 rounded font-bold uppercase border ${roleMeta.badge}`}>
                {userRole}
              </span>
              <span className="text-[10px] text-slate-400 truncate">{roleMeta.desc}</span>
            </div>
          </div>
        </div>
      </div>

      {/* Nav List */}
      <div className="flex-1 py-3 px-3 space-y-5 overflow-y-auto">
        {/* Core Operations */}
        {filteredItems.filter((i) => i.category === 'core').length > 0 && (
          <div>
            <div className="px-3 mb-1.5 text-[10px] font-bold tracking-wider text-slate-400 uppercase">
              {userRole === 'store' ? 'Store Operations' : 'Procurement & Finance'}
            </div>
            <nav className="space-y-1">
              {filteredItems
                .filter((item) => item.category === 'core')
                .map((item) => {
                  const Icon = item.icon;
                  const isActive = activeTab === item.id;
                  return (
                    <button
                      key={item.id}
                      onClick={() => setActiveTab(item.id)}
                      className={`w-full flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-lg transition-all ${
                        isActive
                          ? 'bg-teal-600 text-white shadow-sm shadow-teal-600/20'
                          : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                      }`}
                    >
                      <div className="flex items-center gap-2.5">
                        <Icon className={`w-4 h-4 ${isActive ? 'text-white' : 'text-slate-400'}`} />
                        <span>{item.label}</span>
                      </div>
                      {item.badge !== undefined && (
                        <span className={`text-[10px] px-1.5 py-0.5 rounded-full font-bold ${item.badgeColor}`}>
                          {item.badge}
                        </span>
                      )}
                    </button>
                  );
                })}
            </nav>
          </div>
        )}

        {/* Laboratory & Staff */}
        {filteredItems.filter((i) => i.category === 'lab').length > 0 && (
          <div>
            <div className="px-3 mb-1.5 text-[10px] font-bold tracking-wider text-slate-400 uppercase">
              {userRole === 'store' ? 'Warehouse Stock' : 'Laboratory & Staff'}
            </div>
            <nav className="space-y-1">
              {filteredItems
                .filter((item) => item.category === 'lab')
                .map((item) => {
                  const Icon = item.icon;
                  const isActive = activeTab === item.id;
                  return (
                    <button
                      key={item.id}
                      onClick={() => setActiveTab(item.id)}
                      className={`w-full flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-lg transition-all ${
                        isActive
                          ? 'bg-teal-600 text-white shadow-sm shadow-teal-600/20'
                          : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                      }`}
                    >
                      <div className="flex items-center gap-2.5">
                        <Icon className={`w-4 h-4 ${isActive ? 'text-white' : 'text-slate-400'}`} />
                        <span>{item.label}</span>
                      </div>
                      {item.badge !== undefined && (
                        <span className={`text-[10px] px-1.5 py-0.5 rounded-full font-bold ${item.badgeColor}`}>
                          {item.badge}
                        </span>
                      )}
                    </button>
                  );
                })}
            </nav>
          </div>
        )}

        {/* Logistics */}
        {filteredItems.filter((i) => i.category === 'logistics').length > 0 && (
          <div>
            <div className="px-3 mb-1.5 text-[10px] font-bold tracking-wider text-slate-400 uppercase">
              Supply Chain & Logistics
            </div>
            <nav className="space-y-1">
              {filteredItems
                .filter((item) => item.category === 'logistics')
                .map((item) => {
                  const Icon = item.icon;
                  const isActive = activeTab === item.id;
                  return (
                    <button
                      key={item.id}
                      onClick={() => setActiveTab(item.id)}
                      className={`w-full flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-lg transition-all ${
                        isActive
                          ? 'bg-teal-600 text-white shadow-sm shadow-teal-600/20'
                          : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                      }`}
                    >
                      <div className="flex items-center gap-2.5">
                        <Icon className={`w-4 h-4 ${isActive ? 'text-white' : 'text-slate-400'}`} />
                        <span>{item.label}</span>
                      </div>
                      {item.badge !== undefined && (
                        <span className={`text-[10px] px-1.5 py-0.5 rounded-full font-bold ${item.badgeColor}`}>
                          {item.badge}
                        </span>
                      )}
                    </button>
                  );
                })}
            </nav>
          </div>
        )}

        {/* Analytics & Tools */}
        {filteredItems.filter((i) => i.category === 'analytics').length > 0 && (
          <div>
            <div className="px-3 mb-1.5 text-[10px] font-bold tracking-wider text-slate-400 uppercase">
              Intelligence & Tools
            </div>
            <nav className="space-y-1">
              {filteredItems
                .filter((item) => item.category === 'analytics')
                .map((item) => {
                  const Icon = item.icon;
                  const isActive = activeTab === item.id;
                  return (
                    <button
                      key={item.id}
                      onClick={() => setActiveTab(item.id)}
                      className={`w-full flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-lg transition-all ${
                        isActive
                          ? 'bg-teal-600 text-white shadow-sm shadow-teal-600/20'
                          : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                      }`}
                    >
                      <div className="flex items-center gap-2.5">
                        <Icon className={`w-4 h-4 ${isActive ? 'text-white' : 'text-slate-400'}`} />
                        <span>{item.label}</span>
                      </div>
                      {item.badge !== undefined && (
                        <span className={`text-[10px] px-1.5 py-0.5 rounded-full font-bold ${item.badgeColor}`}>
                          {item.badge}
                        </span>
                      )}
                    </button>
                  );
                })}
            </nav>
          </div>
        )}
      </div>

      {/* Footer system status & Sign out */}
      <div className="p-3 border-t border-slate-200 bg-slate-50 space-y-2">
        <button
          onClick={logout}
          className="w-full flex items-center justify-center gap-2 py-1.5 px-3 rounded-lg text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200/60 transition-colors cursor-pointer"
        >
          <LogOut className="w-3.5 h-3.5" />
          Sign Out
        </button>
        <div className="flex items-center justify-between text-[10px] text-slate-400 px-1">
          <span className="flex items-center gap-1.5">
            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            GLP Terminal Active
          </span>
          <span className="font-mono">v2.4-pharma</span>
        </div>
      </div>
    </aside>
  );
};

