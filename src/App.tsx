import React, { useState } from 'react';
import { AppProvider, useApp } from './context/AppContext';
import { Sidebar } from './components/layout/Sidebar';
import { Navbar } from './components/layout/Navbar';
import { AuthPage } from './components/auth/AuthPage';
import { DashboardView } from './components/dashboard/DashboardView';
import { PurchaseOrderView } from './components/purchase/PurchaseOrderView';
import { InvoiceView } from './components/invoices/InvoiceView';
import { InventoryView } from './components/inventory/InventoryView';
import { EmployeeView } from './components/employees/EmployeeView';
import { AttendanceView } from './components/attendance/AttendanceView';
import { CameraAttendanceView } from './components/attendance/CameraAttendanceView';
import { DeliveryView } from './components/delivery/DeliveryView';
import { SupplierView } from './components/suppliers/SupplierView';
import { ReportsView } from './components/reports/ReportsView';

const MainLayout: React.FC = () => {
  const { activeTab } = useApp();
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);

  const renderActiveView = () => {
    switch (activeTab) {
      case 'dashboard':
        return <DashboardView />;
      case 'purchase_orders':
        return <PurchaseOrderView />;
      case 'invoices':
        return <InvoiceView />;
      case 'inventory':
        return <InventoryView />;
      case 'employees':
        return <EmployeeView />;
      case 'attendance':
        return <AttendanceView />;
      case 'camera_attendance':
        return <CameraAttendanceView />;
      case 'delivery_tracking':
      case 'delivery_history':
      case 'deliveries' as any:
        return <DeliveryView />;
      case 'suppliers' as any:
        return <SupplierView />;
      case 'reports':
        return <ReportsView />;
      default:
        return <DashboardView />;
    }
  };

  return (
    <div className="flex h-screen w-screen overflow-hidden bg-slate-50 font-sans">
      {/* Sidebar Navigation */}
      <Sidebar isOpen={isSidebarOpen} setIsOpen={setIsSidebarOpen} />

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        {/* Top Navbar */}
        <Navbar onToggleSidebar={() => setIsSidebarOpen(!isSidebarOpen)} />

        {/* Dynamic Page Viewport */}
        <main className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
          {renderActiveView()}
        </main>
      </div>
    </div>
  );
};

const RootApp: React.FC = () => {
  const { isAuthenticated } = useApp();

  if (!isAuthenticated) {
    return <AuthPage />;
  }

  return <MainLayout />;
};

export const App: React.FC = () => {
  return (
    <AppProvider>
      <RootApp />
    </AppProvider>
  );
};

export default App;

