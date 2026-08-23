import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { Employee } from '../../types';
import {
  Users,
  Plus,
  Search,
  Phone,
  Mail,
  Building2,
  Calendar,
  DollarSign,
  ShieldCheck,
  Edit2,
  Trash2,
  Eye,
  CheckCircle2,
} from 'lucide-react';

export const EmployeeView: React.FC = () => {
  const {
    employees,
    attendanceRecords,
    addEmployee,
    updateEmployee,
    deleteEmployee,
    searchQuery,
  } = useApp();

  const [departmentFilter, setDepartmentFilter] = useState<string>('all');
  const [localSearch, setLocalSearch] = useState('');
  const [showAddModal, setShowAddModal] = useState(false);
  const [selectedEmployee, setSelectedEmployee] = useState<Employee | null>(null);
  const [editingEmployee, setEditingEmployee] = useState<Employee | null>(null);

  // Add/Edit Form state
  const nextEmpId = `EMP00${employees.length + 1}`;
  const [empId, setEmpId] = useState(nextEmpId);
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('+1-555-');
  const [department, setDepartment] = useState('Laboratory');
  const [position, setPosition] = useState('Lab Technician');
  const [hireDate, setHireDate] = useState(new Date().toISOString().split('T')[0]);
  const [salary, setSalary] = useState<number>(45000);
  const [status, setStatus] = useState<'active' | 'inactive'>('active');
  const [address, setAddress] = useState('');
  const [emergencyContact, setEmergencyContact] = useState('');

  const openAddModal = () => {
    setEditingEmployee(null);
    setEmpId(`EMP00${employees.length + 1}`);
    setFirstName('');
    setLastName('');
    setEmail('');
    setPhone('+1-555-');
    setDepartment('Laboratory');
    setPosition('Lab Technician');
    setHireDate(new Date().toISOString().split('T')[0]);
    setSalary(45000);
    setStatus('active');
    setAddress('');
    setEmergencyContact('');
    setShowAddModal(true);
  };

  const openEditModal = (emp: Employee) => {
    setEditingEmployee(emp);
    setEmpId(emp.employee_id);
    setFirstName(emp.first_name);
    setLastName(emp.last_name);
    setEmail(emp.email);
    setPhone(emp.phone);
    setDepartment(emp.department);
    setPosition(emp.position);
    setHireDate(emp.hire_date);
    setSalary(emp.salary);
    setStatus(emp.status);
    setAddress(emp.address || '');
    setEmergencyContact(emp.emergency_contact || '');
    setShowAddModal(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!firstName.trim() || !lastName.trim()) return;

    if (editingEmployee) {
      updateEmployee(editingEmployee.id, {
        employee_id: empId,
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim(),
        phone: phone.trim(),
        department,
        position,
        hire_date: hireDate,
        salary,
        status,
        address,
        emergency_contact: emergencyContact,
      });
    } else {
      addEmployee({
        employee_id: empId,
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim() || `${firstName.toLowerCase()}.${lastName.toLowerCase()}@mcpillab.com`,
        phone: phone.trim(),
        department,
        position,
        hire_date: hireDate,
        salary,
        status,
        address,
        emergency_contact: emergencyContact,
      });
    }

    setShowAddModal(false);
  };

  const effectiveSearch = (searchQuery || localSearch).toLowerCase();
  const filteredEmployees = employees.filter((emp) => {
    const matchesDept = departmentFilter === 'all' || emp.department.toLowerCase() === departmentFilter.toLowerCase();
    const fullName = `${emp.first_name} ${emp.last_name}`.toLowerCase();
    const matchesSearch =
      fullName.includes(effectiveSearch) ||
      emp.employee_id.toLowerCase().includes(effectiveSearch) ||
      emp.position.toLowerCase().includes(effectiveSearch) ||
      emp.email.toLowerCase().includes(effectiveSearch);
    return matchesDept && matchesSearch;
  });

  const departments = Array.from(new Set(employees.map((e) => e.department)));

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
        <div>
          <div className="flex items-center gap-2">
            <h1 className="text-xl font-bold text-slate-900">Personnel & Staff Profiles</h1>
            <span className="text-xs px-2.5 py-0.5 rounded-full bg-teal-50 text-teal-700 font-bold border border-teal-200">
              {employees.length} Active Staff
            </span>
          </div>
          <p className="text-xs text-slate-500 mt-1">
            Maintain laboratory technician records, roles, salary schedules, and emergency contacts.
          </p>
        </div>

        <button
          onClick={openAddModal}
          className="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-sm shadow-teal-700/20 transition-colors shrink-0"
        >
          <Plus className="w-4 h-4" />
          Add Employee Profile
        </button>
      </div>

      {/* Filter and Search */}
      <div className="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div className="flex flex-wrap items-center gap-1.5">
          <button
            onClick={() => setDepartmentFilter('all')}
            className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-colors ${
              departmentFilter === 'all'
                ? 'bg-slate-900 text-white shadow-sm'
                : 'bg-slate-100 hover:bg-slate-200 text-slate-600'
            }`}
          >
            All Departments
          </button>
          {departments.map((dept) => (
            <button
              key={dept}
              onClick={() => setDepartmentFilter(dept)}
              className={`px-3 py-1.5 rounded-lg text-xs font-bold transition-colors ${
                departmentFilter === dept
                  ? 'bg-slate-900 text-white shadow-sm'
                  : 'bg-slate-100 hover:bg-slate-200 text-slate-600'
              }`}
            >
              {dept}
            </button>
          ))}
        </div>

        <div className="relative w-full sm:w-64">
          <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            placeholder="Search name, ID, position..."
            value={localSearch}
            onChange={(e) => setLocalSearch(e.target.value)}
            className="w-full pl-9 pr-4 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"
          />
        </div>
      </div>

      {/* Staff Grid Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        {filteredEmployees.map((emp) => {
          const empAttendance = attendanceRecords.filter((a) => a.employee_id === emp.id);
          return (
            <div
              key={emp.id}
              className="bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-all p-5 flex flex-col justify-between"
            >
              <div>
                <div className="flex items-start justify-between gap-3">
                  <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded-xl bg-teal-50 text-teal-800 font-black text-lg flex items-center justify-center border border-teal-200">
                      {emp.first_name[0]}
                      {emp.last_name[0]}
                    </div>
                    <div>
                      <div className="flex items-center gap-1.5">
                        <h3 className="font-bold text-slate-900 text-sm">
                          {emp.first_name} {emp.last_name}
                        </h3>
                        <span className="font-mono text-[10px] px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-semibold">
                          {emp.employee_id}
                        </span>
                      </div>
                      <p className="text-xs text-teal-700 font-semibold">{emp.position}</p>
                      <p className="text-[11px] text-slate-500">{emp.department}</p>
                    </div>
                  </div>

                  <span
                    className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                      emp.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'
                    }`}
                  >
                    {emp.status}
                  </span>
                </div>

                <div className="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
                  <div className="flex items-center gap-2">
                    <Mail className="w-3.5 h-3.5 text-slate-400" />
                    <span className="truncate">{emp.email}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Phone className="w-3.5 h-3.5 text-slate-400" />
                    <span>{emp.phone}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Calendar className="w-3.5 h-3.5 text-slate-400" />
                    <span>Hired: {emp.hire_date}</span>
                  </div>
                  <div className="flex items-center gap-2 font-mono font-bold text-slate-800">
                    <DollarSign className="w-3.5 h-3.5 text-teal-600" />
                    <span>₱{emp.salary.toLocaleString(undefined, { minimumFractionDigits: 2 })} / mo</span>
                  </div>
                </div>
              </div>

              <div className="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between">
                <button
                  onClick={() => setSelectedEmployee(emp)}
                  className="text-xs font-bold text-teal-600 hover:text-teal-700 flex items-center gap-1"
                >
                  <Eye className="w-3.5 h-3.5" /> Full Profile
                </button>

                <div className="flex items-center gap-1">
                  <button
                    onClick={() => openEditModal(emp)}
                    className="p-1.5 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-lg"
                    title="Edit Profile"
                  >
                    <Edit2 className="w-3.5 h-3.5" />
                  </button>
                  <button
                    onClick={() => {
                      if (window.confirm(`Are you sure you want to remove employee ${emp.first_name} ${emp.last_name}?`)) {
                        deleteEmployee(emp.id);
                      }
                    }}
                    className="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg"
                    title="Delete Employee"
                  >
                    <Trash2 className="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>
          );
        })}
      </div>

      {/* MODAL: Full Employee Detail */}
      {selectedEmployee && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
            <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
              <div>
                <h2 className="font-bold text-slate-900 text-base">Employee Official Record</h2>
                <p className="text-xs text-slate-500 font-mono">{selectedEmployee.employee_id}</p>
              </div>
              <button
                onClick={() => setSelectedEmployee(null)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100"
              >
                ✕
              </button>
            </div>

            <div className="space-y-4 text-xs">
              <div className="flex items-center gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                <div className="w-14 h-14 rounded-2xl bg-teal-600 text-white font-extrabold text-xl flex items-center justify-center shadow">
                  {selectedEmployee.first_name[0]}
                  {selectedEmployee.last_name[0]}
                </div>
                <div>
                  <h3 className="font-extrabold text-slate-900 text-base">
                    {selectedEmployee.first_name} {selectedEmployee.last_name}
                  </h3>
                  <p className="text-teal-700 font-bold">{selectedEmployee.position}</p>
                  <p className="text-slate-500">{selectedEmployee.department} Division</p>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3 bg-white p-3 border border-slate-200 rounded-xl">
                <div>
                  <span className="text-[10px] text-slate-400 uppercase font-bold">Email Address</span>
                  <div className="font-medium text-slate-800">{selectedEmployee.email}</div>
                </div>
                <div>
                  <span className="text-[10px] text-slate-400 uppercase font-bold">Contact Number</span>
                  <div className="font-medium text-slate-800">{selectedEmployee.phone}</div>
                </div>
                <div>
                  <span className="text-[10px] text-slate-400 uppercase font-bold">Hire Date</span>
                  <div className="font-medium text-slate-800">{selectedEmployee.hire_date}</div>
                </div>
                <div>
                  <span className="text-[10px] text-slate-400 uppercase font-bold">Monthly Compensation</span>
                  <div className="font-bold text-slate-900 font-mono">
                    ₱{selectedEmployee.salary.toLocaleString(undefined, { minimumFractionDigits: 2 })}
                  </div>
                </div>
                {selectedEmployee.address && (
                  <div className="col-span-2">
                    <span className="text-[10px] text-slate-400 uppercase font-bold">Residential Address</span>
                    <div className="font-medium text-slate-800">{selectedEmployee.address}</div>
                  </div>
                )}
                {selectedEmployee.emergency_contact && (
                  <div className="col-span-2">
                    <span className="text-[10px] text-slate-400 uppercase font-bold">Emergency Contact</span>
                    <div className="font-medium text-slate-800">{selectedEmployee.emergency_contact}</div>
                  </div>
                )}
              </div>

              <div className="flex justify-end pt-3 border-t border-slate-200">
                <button
                  onClick={() => setSelectedEmployee(null)}
                  className="px-4 py-2 bg-slate-800 text-white font-bold text-xs rounded-xl"
                >
                  Close Record
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* MODAL: Add / Edit Form */}
      {showAddModal && (
        <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 animate-in fade-in duration-150">
          <div className="bg-white rounded-2xl max-w-xl w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl border border-slate-200">
            <div className="flex items-center justify-between pb-4 mb-4 border-b border-slate-200">
              <div className="flex items-center gap-2">
                <Users className="w-5 h-5 text-teal-600" />
                <h2 className="font-bold text-slate-900 text-base">
                  {editingEmployee ? 'Edit Employee Profile' : 'Register New Laboratory Staff'}
                </h2>
              </div>
              <button
                onClick={() => setShowAddModal(false)}
                className="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4 text-xs">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-bold text-slate-700 mb-1">Employee ID</label>
                  <input
                    type="text"
                    value={empId}
                    onChange={(e) => setEmpId(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono font-bold text-teal-700"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Employment Status</label>
                  <select
                    value={status}
                    onChange={(e) => setStatus(e.target.value as any)}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  >
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">First Name</label>
                  <input
                    type="text"
                    value={firstName}
                    onChange={(e) => setFirstName(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Last Name</label>
                  <input
                    type="text"
                    value={lastName}
                    onChange={(e) => setLastName(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Email Address</label>
                  <input
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="alice@mcpillab.com"
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Phone Number</label>
                  <input
                    type="text"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Department</label>
                  <select
                    value={department}
                    onChange={(e) => setDepartment(e.target.value)}
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  >
                    <option value="Laboratory">Laboratory</option>
                    <option value="Quality Control">Quality Control</option>
                    <option value="Purchasing">Purchasing</option>
                    <option value="Warehouse & Logistics">Warehouse & Logistics</option>
                    <option value="Research & Dev">Research & Dev</option>
                  </select>
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Job Position</label>
                  <input
                    type="text"
                    value={position}
                    onChange={(e) => setPosition(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Hire Date</label>
                  <input
                    type="date"
                    value={hireDate}
                    onChange={(e) => setHireDate(e.target.value)}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-700 mb-1">Monthly Salary (₱)</label>
                  <input
                    type="number"
                    value={salary}
                    onChange={(e) => setSalary(Number(e.target.value))}
                    required
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg font-mono font-bold"
                  />
                </div>

                <div className="col-span-2">
                  <label className="block font-bold text-slate-700 mb-1">Residential Address</label>
                  <input
                    type="text"
                    value={address}
                    onChange={(e) => setAddress(e.target.value)}
                    placeholder="Street, City, Postal Code"
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>

                <div className="col-span-2">
                  <label className="block font-bold text-slate-700 mb-1">Emergency Contact</label>
                  <input
                    type="text"
                    value={emergencyContact}
                    onChange={(e) => setEmergencyContact(e.target.value)}
                    placeholder="Name & Contact Number"
                    className="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg"
                  />
                </div>
              </div>

              <div className="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <button
                  type="button"
                  onClick={() => setShowAddModal(false)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow"
                >
                  {editingEmployee ? 'Update Profile' : 'Save Employee'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
