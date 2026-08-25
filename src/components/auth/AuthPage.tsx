import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import { UserRole } from '../../types';
import {
  ShieldCheck,
  FlaskConical,
  Store,
  Lock,
  User as UserIcon,
  Mail,
  Phone,
  Eye,
  EyeOff,
  ArrowRight,
  Sparkles,
  CheckCircle2,
  Building2,
  AlertCircle,
  KeyRound,
  BadgePercent,
  Check,
} from 'lucide-react';

interface AuthPageProps {
  initialMode?: 'login' | 'register';
  onSuccess?: () => void;
}

export const AuthPage: React.FC<AuthPageProps> = ({ initialMode = 'login', onSuccess }) => {
  const { login, register, users } = useApp();

  const [mode, setMode] = useState<'login' | 'register'>(initialMode);
  const [showPassword, setShowPassword] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  // Login Form State
  const [loginRole, setLoginRole] = useState<'admin' | 'employee' | 'store'>('admin');
  const [loginIdentifier, setLoginIdentifier] = useState('admin');
  const [loginPassword, setLoginPassword] = useState('admin123');
  const [rememberMe, setRememberMe] = useState(true);

  // Quick switch role in login
  const handleSelectLoginRole = (role: 'admin' | 'employee' | 'store') => {
    setLoginRole(role);
    if (role === 'admin') {
      setLoginIdentifier('admin');
      setLoginPassword('admin123');
    } else if (role === 'employee') {
      setLoginIdentifier('alice.tech');
      setLoginPassword('emp123');
    } else if (role === 'store') {
      setLoginIdentifier('store_central');
      setLoginPassword('store123');
    }
  };

  // Register Form State
  const [regFullName, setRegFullName] = useState('');
  const [regEmail, setRegEmail] = useState('');
  const [regUsername, setRegUsername] = useState('');
  const [regPassword, setRegPassword] = useState('');
  const [regConfirmPassword, setRegConfirmPassword] = useState('');
  const [regRole, setRegRole] = useState<'admin' | 'employee' | 'store'>('employee');
  const [regDepartment, setRegDepartment] = useState('Analytical Chemistry');
  const [regStoreName, setRegStoreName] = useState('Central Warehouse Hub B');
  const [regPhone, setRegPhone] = useState('+1-555-');
  const [regEmployeeId, setRegEmployeeId] = useState(`EMP-00${users.length + 1}`);
  const [regAdminCode, setRegAdminCode] = useState('MCPIL-ADM-2026');
  const [agreeTerms, setAgreeTerms] = useState(true);

  const handleLoginSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);
    setSuccessMessage(null);
    setLoading(true);

    setTimeout(() => {
      const res = login(loginIdentifier, loginPassword, loginRole);
      setLoading(false);
      if (!res.success) {
        setErrorMessage(res.message);
      } else {
        setSuccessMessage(`Welcome, ${res.user?.full_name}! Logging in as ${res.user?.role.toUpperCase()}...`);
        if (onSuccess) onSuccess();
      }
    }, 350);
  };

  const handleRegisterSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage(null);
    setSuccessMessage(null);

    if (!regFullName.trim()) {
      setErrorMessage('Please enter your full name.');
      return;
    }
    if (!regEmail.trim() || !regEmail.includes('@')) {
      setErrorMessage('Please provide a valid email address.');
      return;
    }
    if (regPassword.length < 4) {
      setErrorMessage('Password must be at least 4 characters.');
      return;
    }
    if (regPassword !== regConfirmPassword) {
      setErrorMessage('Passwords do not match. Please re-enter your password.');
      return;
    }

    setLoading(true);

    setTimeout(() => {
      // Auto derive a clean username from email or full name
      const baseUsername = regEmail.trim().split('@')[0].toLowerCase().replace(/[^a-z0-9_]/g, '') || regFullName.trim().toLowerCase().replace(/\s+/g, '.');
      
      const res = register({
        full_name: regFullName.trim(),
        email: regEmail.trim(),
        username: baseUsername,
        password: regPassword,
        role: regRole,
        department: regRole === 'employee' ? regDepartment : regRole === 'store' ? 'Warehouse & Bodega' : 'Executive Administration',
        store_name: regRole === 'store' ? regStoreName : undefined,
        phone: regPhone.trim() || '+1-555-0199',
        employee_id: regRole === 'employee' ? `EMP-00${users.length + 1}` : undefined,
      });

      setLoading(false);
      if (!res.success) {
        setErrorMessage(res.message);
      } else {
        setSuccessMessage(`Account created successfully as ${regRole.toUpperCase()}! Logging in...`);
        if (onSuccess) onSuccess();
      }
    }, 350);
  };

  return (
    <div className="min-h-screen w-full bg-gradient-to-br from-slate-900 via-slate-800 to-teal-950 flex flex-col justify-center items-center p-4 sm:p-6 lg:p-8 font-sans text-slate-100 relative overflow-x-hidden">
      {/* Background ambient lighting */}
      <div className="absolute top-1/4 left-1/2 -translate-x-1/2 w-96 sm:w-[600px] h-96 bg-teal-500/10 blur-3xl rounded-full pointer-events-none" />
      <div className="absolute bottom-10 right-10 w-72 h-72 bg-emerald-500/10 blur-3xl rounded-full pointer-events-none" />

      {/* Main Container */}
      <div className="w-full max-w-xl z-10 space-y-5">
        {/* Header Branding */}
        <div className="text-center space-y-2">
          <div className="inline-flex items-center justify-center gap-3">
            <div className="w-12 h-12 rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-600 flex items-center justify-center text-white font-black text-2xl shadow-xl shadow-teal-500/30 border border-teal-400/30">
              MC
            </div>
            <div className="text-left">
              <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-2">
                MCPIL <span className="text-teal-400 font-light">Laboratory Portal</span>
              </h1>
              <p className="text-xs text-slate-400 font-medium">
                Role-Based Access: Admin • Employee • Store
              </p>
            </div>
          </div>
        </div>

        {/* Auth Form Card */}
        <div className="bg-white text-slate-900 rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
          {/* Tabs: Login vs Register */}
          <div className="grid grid-cols-2 border-b border-slate-200 text-center font-bold text-sm bg-slate-50">
            <button
              type="button"
              onClick={() => {
                setMode('login');
                setErrorMessage(null);
                setSuccessMessage(null);
              }}
              className={`py-3.5 transition-colors flex items-center justify-center gap-2 cursor-pointer ${
                mode === 'login'
                  ? 'bg-white text-teal-800 border-b-2 border-teal-600 shadow-xs'
                  : 'text-slate-500 hover:text-slate-800'
              }`}
            >
              <KeyRound className="w-4 h-4" />
              Login
            </button>
            <button
              type="button"
              onClick={() => {
                setMode('register');
                setErrorMessage(null);
                setSuccessMessage(null);
              }}
              className={`py-3.5 transition-colors flex items-center justify-center gap-2 cursor-pointer ${
                mode === 'register'
                  ? 'bg-white text-teal-800 border-b-2 border-teal-600 shadow-xs'
                  : 'text-slate-500 hover:text-slate-800'
              }`}
            >
              <UserIcon className="w-4 h-4" />
              Register
            </button>
          </div>

          <div className="p-6 sm:p-7">
            {/* Feedback Alerts */}
            {errorMessage && (
              <div className="mb-4 p-3.5 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs flex items-center gap-2.5 animate-in fade-in duration-150">
                <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />
                <span className="font-semibold">{errorMessage}</span>
              </div>
            )}

            {successMessage && (
              <div className="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs flex items-center gap-2.5 animate-in fade-in duration-150">
                <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
                <span className="font-semibold">{successMessage}</span>
              </div>
            )}

            {/* ===================== LOGIN FORM ===================== */}
            {mode === 'login' ? (
              <form onSubmit={handleLoginSubmit} className="space-y-4">
                {/* 3-Role Selection Cards */}
                <div>
                  <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <UserIcon className="w-3.5 h-3.5 text-teal-600" />
                    Select Role
                  </label>
                  <div className="grid grid-cols-3 gap-2.5">
                    {/* Admin selector */}
                    <button
                      type="button"
                      onClick={() => handleSelectLoginRole('admin')}
                      className={`p-3.5 rounded-xl border-2 transition-all cursor-pointer text-center flex flex-col items-center justify-center gap-1.5 ${
                        loginRole === 'admin'
                          ? 'border-purple-600 bg-purple-50/80 text-purple-950 ring-2 ring-purple-600/20 shadow-xs'
                          : 'border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-slate-300 text-slate-700'
                      }`}
                    >
                      <div className={`w-8 h-8 rounded-lg flex items-center justify-center transition-colors ${
                        loginRole === 'admin' ? 'bg-purple-600 text-white shadow-xs' : 'bg-purple-100 text-purple-700'
                      }`}>
                        <ShieldCheck className="w-4 h-4" />
                      </div>
                      <div className="font-bold text-xs">Admin</div>
                    </button>

                    {/* Employee selector */}
                    <button
                      type="button"
                      onClick={() => handleSelectLoginRole('employee')}
                      className={`p-3.5 rounded-xl border-2 transition-all cursor-pointer text-center flex flex-col items-center justify-center gap-1.5 ${
                        loginRole === 'employee'
                          ? 'border-teal-600 bg-teal-50/80 text-teal-950 ring-2 ring-teal-600/20 shadow-xs'
                          : 'border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-slate-300 text-slate-700'
                      }`}
                    >
                      <div className={`w-8 h-8 rounded-lg flex items-center justify-center transition-colors ${
                        loginRole === 'employee' ? 'bg-teal-600 text-white shadow-xs' : 'bg-teal-100 text-teal-700'
                      }`}>
                        <FlaskConical className="w-4 h-4" />
                      </div>
                      <div className="font-bold text-xs">Employee</div>
                    </button>

                    {/* Store selector */}
                    <button
                      type="button"
                      onClick={() => handleSelectLoginRole('store')}
                      className={`p-3.5 rounded-xl border-2 transition-all cursor-pointer text-center flex flex-col items-center justify-center gap-1.5 ${
                        loginRole === 'store'
                          ? 'border-amber-600 bg-amber-50/80 text-amber-950 ring-2 ring-amber-600/20 shadow-xs'
                          : 'border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-slate-300 text-slate-700'
                      }`}
                    >
                      <div className={`w-8 h-8 rounded-lg flex items-center justify-center transition-colors ${
                        loginRole === 'store' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-100 text-amber-700'
                      }`}>
                        <Store className="w-4 h-4" />
                      </div>
                      <div className="font-bold text-xs">Store</div>
                    </button>
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Username or Email
                  </label>
                  <div className="relative">
                    <UserIcon className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                      type="text"
                      value={loginIdentifier}
                      onChange={(e) => setLoginIdentifier(e.target.value)}
                      required
                      placeholder={
                        loginRole === 'admin'
                          ? 'Enter admin email or username (e.g. admin)'
                          : loginRole === 'employee'
                          ? 'Enter employee email or username (e.g. alice.tech)'
                          : 'Enter store / bodega email or username (e.g. store_central)'
                      }
                      className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition-all"
                    />
                  </div>
                </div>

                <div>
                  <div className="flex items-center justify-between mb-1.5">
                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                      Password
                    </label>
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="text-xs text-teal-600 hover:text-teal-700 font-semibold flex items-center gap-1 cursor-pointer"
                    >
                      {showPassword ? <EyeOff className="w-3.5 h-3.5" /> : <Eye className="w-3.5 h-3.5" />}
                      {showPassword ? 'Hide' : 'Show'}
                    </button>
                  </div>
                  <div className="relative">
                    <Lock className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                      type={showPassword ? 'text' : 'password'}
                      value={loginPassword}
                      onChange={(e) => setLoginPassword(e.target.value)}
                      required
                      placeholder="Enter password"
                      className="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition-all"
                    />
                  </div>
                </div>

                <div className="flex items-center justify-between pt-1">
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={rememberMe}
                      onChange={(e) => setRememberMe(e.target.checked)}
                      className="rounded border-slate-300 text-teal-600 focus:ring-teal-500 w-4 h-4"
                    />
                    <span className="text-xs text-slate-600 font-medium">Remember terminal</span>
                  </label>

                  <button
                    type="button"
                    onClick={() => {
                      alert('Default Credentials:\n• Admin: admin / admin123\n• Employee: alice.tech / emp123\n• Store: store_central / store123');
                    }}
                    className="text-xs font-bold text-teal-600 hover:text-teal-700 hover:underline cursor-pointer"
                  >
                    Credentials Hint
                  </button>
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full py-3 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-md shadow-teal-700/20 transition-all flex items-center justify-center gap-2 mt-2 cursor-pointer disabled:opacity-50"
                >
                  {loading ? 'Authenticating...' : 'Sign In to Laboratory'}
                  <ArrowRight className="w-4 h-4" />
                </button>

                <div className="pt-3 mt-3 border-t border-slate-100 text-center text-xs text-slate-500">
                  New laboratory staff or custodian?{' '}
                  <button
                    type="button"
                    onClick={() => setMode('register')}
                    className="text-teal-700 font-bold hover:underline cursor-pointer"
                  >
                    Register new account →
                  </button>
                </div>
              </form>
            ) : (
              /* ===================== REGISTER FORM ===================== */
              <form onSubmit={handleRegisterSubmit} className="space-y-4">
                {/* Role Selection Cards matching screenshot & login tab */}
                <div>
                  <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <UserIcon className="w-3.5 h-3.5 text-teal-600" />
                    Select Role
                  </label>
                  <div className="grid grid-cols-3 gap-2.5">
                    {/* Admin selector */}
                    <button
                      type="button"
                      onClick={() => setRegRole('admin')}
                      className={`p-3.5 rounded-xl border-2 transition-all cursor-pointer text-center flex flex-col items-center justify-center gap-1.5 ${
                        regRole === 'admin'
                          ? 'border-purple-600 bg-purple-50/80 text-purple-950 ring-2 ring-purple-600/20 shadow-xs'
                          : 'border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-slate-300 text-slate-700'
                      }`}
                    >
                      <div className={`w-8 h-8 rounded-lg flex items-center justify-center transition-colors ${
                        regRole === 'admin' ? 'bg-purple-600 text-white shadow-xs' : 'bg-purple-100 text-purple-700'
                      }`}>
                        <ShieldCheck className="w-4 h-4" />
                      </div>
                      <div className="font-bold text-xs">Admin</div>
                    </button>

                    {/* Employee selector */}
                    <button
                      type="button"
                      onClick={() => setRegRole('employee')}
                      className={`p-3.5 rounded-xl border-2 transition-all cursor-pointer text-center flex flex-col items-center justify-center gap-1.5 ${
                        regRole === 'employee'
                          ? 'border-teal-600 bg-teal-50/80 text-teal-950 ring-2 ring-teal-600/20 shadow-xs'
                          : 'border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-slate-300 text-slate-700'
                      }`}
                    >
                      <div className={`w-8 h-8 rounded-lg flex items-center justify-center transition-colors ${
                        regRole === 'employee' ? 'bg-teal-600 text-white shadow-xs' : 'bg-teal-100 text-teal-700'
                      }`}>
                        <FlaskConical className="w-4 h-4" />
                      </div>
                      <div className="font-bold text-xs">Employee</div>
                    </button>

                    {/* Store selector */}
                    <button
                      type="button"
                      onClick={() => setRegRole('store')}
                      className={`p-3.5 rounded-xl border-2 transition-all cursor-pointer text-center flex flex-col items-center justify-center gap-1.5 ${
                        regRole === 'store'
                          ? 'border-amber-600 bg-amber-50/80 text-amber-950 ring-2 ring-amber-600/20 shadow-xs'
                          : 'border-slate-200 bg-slate-50 hover:bg-slate-100 hover:border-slate-300 text-slate-700'
                      }`}
                    >
                      <div className={`w-8 h-8 rounded-lg flex items-center justify-center transition-colors ${
                        regRole === 'store' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-100 text-amber-700'
                      }`}>
                        <Store className="w-4 h-4" />
                      </div>
                      <div className="font-bold text-xs">Store</div>
                    </button>
                  </div>
                </div>

                {/* Full Name */}
                <div>
                  <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                    <UserIcon className="w-3.5 h-3.5 text-slate-400" />
                    Full Name
                  </label>
                  <input
                    type="text"
                    value={regFullName}
                    onChange={(e) => setRegFullName(e.target.value)}
                    required
                    placeholder="Enter your full name"
                    className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition-all"
                  />
                </div>

                {/* Email */}
                <div>
                  <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                    <Mail className="w-3.5 h-3.5 text-slate-400" />
                    Email
                  </label>
                  <input
                    type="email"
                    value={regEmail}
                    onChange={(e) => setRegEmail(e.target.value)}
                    required
                    placeholder={
                      regRole === 'admin'
                        ? 'Enter your admin email address'
                        : regRole === 'employee'
                        ? 'Enter your employee email address'
                        : 'Enter your store email address'
                    }
                    className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition-all"
                  />
                </div>

                {/* Password & Confirm Password Row */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                      <Lock className="w-3.5 h-3.5 text-slate-400" />
                      Password
                    </label>
                    <input
                      type="password"
                      value={regPassword}
                      onChange={(e) => setRegPassword(e.target.value)}
                      required
                      placeholder="Enter password"
                      className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition-all"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                      <Lock className="w-3.5 h-3.5 text-slate-400" />
                      Confirm Password
                    </label>
                    <input
                      type="password"
                      value={regConfirmPassword}
                      onChange={(e) => setRegConfirmPassword(e.target.value)}
                      required
                      placeholder="Confirm password"
                      className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-600 transition-all"
                    />
                  </div>
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full py-3 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-md shadow-teal-700/20 transition-all flex items-center justify-center gap-2 mt-2 cursor-pointer disabled:opacity-50"
                >
                  {loading ? 'Creating Account...' : 'Register'}
                  <ArrowRight className="w-4 h-4" />
                </button>

                <div className="pt-3 mt-3 border-t border-slate-100 text-center text-xs text-slate-500">
                  Already have an account?{' '}
                  <button
                    type="button"
                    onClick={() => setMode('login')}
                    className="text-teal-700 font-bold hover:underline cursor-pointer"
                  >
                    Login here →
                  </button>
                </div>
              </form>
            )}
          </div>
        </div>

        {/* Footer Security Badges */}
        <div className="flex flex-wrap items-center justify-center gap-6 text-[11px] text-slate-400 font-medium">
          <span className="flex items-center gap-1.5">
            <ShieldCheck className="w-4 h-4 text-emerald-400" />
            GLP & GMP Certified Compliance
          </span>
          <span className="flex items-center gap-1.5">
            <Lock className="w-4 h-4 text-teal-400" />
            256-Bit Role Based Access Security
          </span>
          <span className="flex items-center gap-1.5">
            <FlaskConical className="w-4 h-4 text-cyan-400" />
            Pharmaceutical QC & Inventory
          </span>
        </div>
      </div>
    </div>
  );
};
