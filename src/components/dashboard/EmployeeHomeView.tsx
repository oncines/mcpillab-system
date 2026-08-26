import React, { useState } from 'react';
import { useApp } from '../../context/AppContext';
import {
  Clock,
  Boxes,
  MessageSquare,
  BarChart3,
  Truck,
  FlaskConical,
  Bell,
  ThumbsUp,
  Share2,
  ExternalLink,
  Plus,
  MoreHorizontal,
  Globe,
  Camera,
  History,
  Edit,
} from 'lucide-react';

export const EmployeeHomeView: React.FC = () => {
  const {
    currentUser,
    purchaseOrders,
    deliveries,
    employees,
    attendanceRecords,
    setActiveTab,
  } = useApp();

  const [postLikes, setPostLikes] = useState<{ [key: string]: boolean }>({
    welcome: false,
    hr: false,
    delivery: false,
    inventory: false,
  });

  const [postComments, setPostComments] = useState<{ [key: string]: string[] }>({
    welcome: [],
    hr: [],
    delivery: [],
    inventory: [],
  });

  const [openCommentPost, setOpenCommentPost] = useState<string | null>(null);
  const [commentInput, setCommentInput] = useState('');

  // Calculations & user info
  const fullName = currentUser?.full_name || 'Laboratory Staff';
  const firstName = fullName.split(' ')[0];
  const initials = fullName
    .split(' ')
    .map((n) => n[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();

  const currentHour = new Date().getHours();
  const greeting =
    currentHour < 12
      ? 'Morning'
      : currentHour < 18
      ? 'Afternoon'
      : 'Evening';

  const stats = {
    total_purchase_orders: purchaseOrders.length,
    pending_deliveries: deliveries.filter((d) => d.status === 'pending').length,
    total_employees: employees.length,
  };

  const unreadMessages = 2;
  const recentDeliveries = deliveries.slice(0, 3);

  const toggleLike = (key: string) => {
    setPostLikes((prev) => ({ ...prev, [key]: !prev[key] }));
  };

  const handleAddComment = (key: string) => {
    if (!commentInput.trim()) return;
    setPostComments((prev) => ({
      ...prev,
      [key]: [...(prev[key] || []), commentInput.trim()],
    }));
    setCommentInput('');
  };

  const handleShare = (postTitle: string) => {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(`${postTitle} - Shared from McPIL Employee Portal`);
      alert('Post link copied to clipboard!');
    } else {
      alert(`Shared: ${postTitle}`);
    }
  };

  return (
    <div className="w-full">
      {/* Topbar matching employee_home.php */}
      <div className="bg-white border border-slate-200 rounded-xl px-4 py-3 mb-5 flex items-center justify-between shadow-xs">
        <div className="flex items-center gap-3">
          <div>
            <h1 className="text-base sm:text-lg font-bold text-slate-900 leading-tight">
              Employee Home
            </h1>
            <p className="text-xs text-slate-500 font-medium">
              Welcome back, {firstName} &middot; Daily workspace
            </p>
          </div>
        </div>
        <div className="flex items-center gap-3">
          <button
            onClick={() => setActiveTab('reports')}
            className="w-9 h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center relative transition-colors"
            title="Messages"
          >
            <MessageSquare className="w-4 h-4" />
            {unreadMessages > 0 && (
              <span className="absolute -top-1 -right-1 w-4 h-4 bg-rose-500 text-white font-bold text-[10px] rounded-full flex items-center justify-center ring-2 ring-white">
                {unreadMessages}
              </span>
            )}
          </button>
          <div
            className="w-9 h-9 rounded-full bg-[#1877f2] text-white font-bold text-xs flex items-center justify-center shadow-xs cursor-pointer"
            title={fullName}
          >
            {initials}
          </div>
        </div>
      </div>

      {/* Main Grid: Feed (Left) & Right Sidebar (Right) */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-5">
        {/* Main Feed Column */}
        <div className="lg:col-span-8 space-y-4">
          {/* Stories Row */}
          <div className="flex gap-2.5 overflow-x-auto pb-2 scrollbar-none">
            {/* Story 1: Create Story */}
            <div
              onClick={() => {
                const note = prompt('Create a quick note/story update:');
                if (note) alert(`Story updated: "${note}"`);
              }}
              className="w-28 shrink-0 h-48 rounded-xl overflow-hidden bg-white border border-slate-200 shadow-xs cursor-pointer flex flex-col justify-between group hover:border-blue-400 transition-all"
            >
              <div className="h-[65%] bg-slate-100 flex items-center justify-center">
                <div className="w-9 h-9 rounded-full bg-[#1877f2] text-white flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform">
                  <Plus className="w-5 h-5" />
                </div>
              </div>
              <div className="h-[35%] bg-white p-2.5 flex items-center">
                <span className="font-semibold text-xs text-slate-800 leading-tight">
                  Create Story
                </span>
              </div>
            </div>

            {/* Story 2: Clock In Now */}
            <div
              onClick={() => setActiveTab('camera_attendance')}
              className="w-28 shrink-0 h-48 rounded-xl overflow-hidden bg-white border border-slate-200 shadow-xs cursor-pointer flex flex-col justify-between relative group hover:border-blue-400 transition-all"
            >
              <div className="h-[65%] bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center">
                <Clock className="w-10 h-10 text-[#1877f2] group-hover:scale-105 transition-transform" />
              </div>
              <div className="absolute top-2.5 left-2.5 w-8 h-8 rounded-full bg-gradient-to-br from-[#1877f2] to-blue-700 text-white flex items-center justify-center border-2 border-[#1877f2] shadow-xs">
                <Clock className="w-4 h-4 text-white" />
              </div>
              <div className="h-[35%] bg-white p-2.5 flex items-center">
                <span className="font-semibold text-xs text-slate-800 leading-tight">
                  Clock In Now
                </span>
              </div>
            </div>

            {/* Story 3: Inventory */}
            <div
              onClick={() => setActiveTab('inventory')}
              className="w-28 shrink-0 h-48 rounded-xl overflow-hidden bg-white border border-slate-200 shadow-xs cursor-pointer flex flex-col justify-between relative group hover:border-emerald-400 transition-all"
            >
              <div className="h-[65%] bg-gradient-to-br from-emerald-50 to-green-100 flex items-center justify-center">
                <Boxes className="w-10 h-10 text-[#27ae60] group-hover:scale-105 transition-transform" />
              </div>
              <div className="absolute top-2.5 left-2.5 w-8 h-8 rounded-full bg-gradient-to-br from-[#27ae60] to-emerald-600 text-white flex items-center justify-center border-2 border-emerald-500 shadow-xs">
                <Boxes className="w-4 h-4 text-white" />
              </div>
              <div className="h-[35%] bg-white p-2.5 flex items-center">
                <span className="font-semibold text-xs text-slate-800 leading-tight">
                  Inventory
                </span>
              </div>
            </div>

            {/* Story 4: Team Messages */}
            <div
              onClick={() => {
                alert('Team message inbox opened.');
              }}
              className="w-28 shrink-0 h-48 rounded-xl overflow-hidden bg-white border border-slate-200 shadow-xs cursor-pointer flex flex-col justify-between relative group hover:border-orange-400 transition-all"
            >
              <div className="h-[65%] bg-gradient-to-br from-orange-50 to-amber-100 flex items-center justify-center">
                <MessageSquare className="w-10 h-10 text-[#e67e22] group-hover:scale-105 transition-transform" />
              </div>
              <div className="absolute top-2.5 left-2.5 w-8 h-8 rounded-full bg-gradient-to-br from-[#e67e22] to-amber-600 text-white flex items-center justify-center border-2 border-amber-500 shadow-xs">
                <MessageSquare className="w-4 h-4 text-white" />
              </div>
              <div className="h-[35%] bg-white p-2.5 flex items-center">
                <span className="font-semibold text-xs text-slate-800 leading-tight">
                  Team Messages
                </span>
              </div>
            </div>

            {/* Story 5: Reports */}
            <div
              onClick={() => setActiveTab('reports')}
              className="w-28 shrink-0 h-48 rounded-xl overflow-hidden bg-white border border-slate-200 shadow-xs cursor-pointer flex flex-col justify-between relative group hover:border-purple-400 transition-all"
            >
              <div className="h-[65%] bg-gradient-to-br from-purple-50 to-indigo-100 flex items-center justify-center">
                <BarChart3 className="w-10 h-10 text-[#8e44ad] group-hover:scale-105 transition-transform" />
              </div>
              <div className="absolute top-2.5 left-2.5 w-8 h-8 rounded-full bg-gradient-to-br from-[#8e44ad] to-purple-600 text-white flex items-center justify-center border-2 border-purple-500 shadow-xs">
                <BarChart3 className="w-4 h-4 text-white" />
              </div>
              <div className="h-[35%] bg-white p-2.5 flex items-center">
                <span className="font-semibold text-xs text-slate-800 leading-tight">
                  Reports
                </span>
              </div>
            </div>
          </div>

          {/* Composer Card */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-xs p-3.5 space-y-3">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-full bg-[#1877f2] text-white font-bold text-xs flex items-center justify-center shrink-0">
                {initials}
              </div>
              <input
                type="text"
                readOnly
                onClick={() => {
                  const input = prompt(`What's on your mind, ${firstName}?`);
                  if (input) alert(`Status update shared with laboratory team: "${input}"`);
                }}
                placeholder={`What's on your mind, ${firstName}?`}
                className="flex-1 h-10 px-4 rounded-full bg-slate-100 hover:bg-slate-200/80 text-sm text-slate-700 font-medium cursor-pointer border-none outline-none transition-colors"
              />
            </div>

            <div className="h-px bg-slate-100" />

            <div className="grid grid-cols-3 gap-1">
              <button
                type="button"
                onClick={() => setActiveTab('camera_attendance')}
                className="flex items-center justify-center gap-2 py-2 px-2 rounded-lg hover:bg-slate-50 text-slate-600 font-semibold text-xs sm:text-sm transition-colors"
              >
                <Clock className="w-4 h-4 text-[#e53935]" />
                <span>Clock In</span>
              </button>
              <button
                type="button"
                onClick={() => setActiveTab('inventory')}
                className="flex items-center justify-center gap-2 py-2 px-2 rounded-lg hover:bg-slate-50 text-slate-600 font-semibold text-xs sm:text-sm transition-colors"
              >
                <Boxes className="w-4 h-4 text-[#27ae60]" />
                <span>Inventory</span>
              </button>
              <button
                type="button"
                onClick={() => {
                  alert('Team Messages channel opened.');
                }}
                className="flex items-center justify-center gap-2 py-2 px-2 rounded-lg hover:bg-slate-50 text-slate-600 font-semibold text-xs sm:text-sm transition-colors"
              >
                <MessageSquare className="w-4 h-4 text-[#fb8c00]" />
                <span>Messages</span>
              </button>
            </div>
          </div>

          {/* Post 1: Official Welcome System Post */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div className="p-3.5 flex items-center justify-between border-b border-slate-100">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-gradient-to-br from-[#1877f2] to-[#4267b2] text-white flex items-center justify-center shrink-0">
                  <FlaskConical className="w-5 h-5" />
                </div>
                <div>
                  <h2 className="text-sm font-bold text-slate-900 leading-tight">
                    McPIL Pharmaceutical Laboratory
                  </h2>
                  <div className="text-xs text-slate-400 flex items-center gap-1">
                    <Globe className="w-3 h-3" />
                    <span>Just now</span>
                  </div>
                </div>
              </div>
              <button className="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-50">
                <MoreHorizontal className="w-4 h-4" />
              </button>
            </div>

            <div className="bg-gradient-to-br from-[#1877f2] to-[#4267b2] text-white p-6 sm:p-8 text-center flex flex-col items-center justify-center min-h-[160px]">
              <div className="text-xl sm:text-2xl font-bold leading-tight">
                Good {greeting}, {firstName}!
              </div>
              <div className="text-sm font-normal opacity-90 mt-1">
                Welcome to McPIL Pharmaceutical Laboratory
              </div>
            </div>

            <div className="grid grid-cols-3 gap-px bg-slate-200 border-y border-slate-200">
              <div className="bg-white p-3.5 sm:p-4 text-center">
                <span className="text-2xl sm:text-3xl font-bold text-[#1877f2] block">
                  {stats.total_purchase_orders}
                </span>
                <span className="text-xs text-slate-500 font-medium mt-0.5 block">
                  Purchase Orders
                </span>
              </div>
              <div className="bg-white p-3.5 sm:p-4 text-center">
                <span className="text-2xl sm:text-3xl font-bold text-[#1877f2] block">
                  {stats.pending_deliveries}
                </span>
                <span className="text-xs text-slate-500 font-medium mt-0.5 block">
                  Pending Deliveries
                </span>
              </div>
              <div className="bg-white p-3.5 sm:p-4 text-center">
                <span className="text-2xl sm:text-3xl font-bold text-[#1877f2] block">
                  {stats.total_employees}
                </span>
                <span className="text-xs text-slate-500 font-medium mt-0.5 block">
                  Team Members
                </span>
              </div>
            </div>

            <div className="px-4 py-2 flex items-center justify-between text-xs text-slate-500 border-b border-slate-100">
              <div className="flex items-center gap-1.5">
                <span className="px-1.5 py-0.5 rounded-full bg-blue-50 text-[#1877f2] font-semibold text-[11px]">
                  Like
                </span>
                <span>{postLikes.welcome ? 15 : 14} reactions</span>
              </div>
              <span>{3 + postComments.welcome.length} comments</span>
            </div>

            <div className="p-1 flex">
              <button
                type="button"
                onClick={() => toggleLike('welcome')}
                className={`flex-1 flex items-center justify-center gap-2 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-colors ${
                  postLikes.welcome
                    ? 'text-[#1877f2] bg-blue-50'
                    : 'text-slate-600 hover:bg-slate-50'
                }`}
              >
                <ThumbsUp className="w-4 h-4" />
                <span>{postLikes.welcome ? 'Liked' : 'Like'}</span>
              </button>
              <button
                type="button"
                onClick={() =>
                  setOpenCommentPost(openCommentPost === 'welcome' ? null : 'welcome')
                }
                className="flex-1 flex items-center justify-center gap-2 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-50 rounded-lg transition-colors"
              >
                <MessageSquare className="w-4 h-4" />
                <span>Comment</span>
              </button>
              <button
                type="button"
                onClick={() => handleShare('McPIL Lab Welcome Broadcast')}
                className="flex-1 flex items-center justify-center gap-2 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-50 rounded-lg transition-colors"
              >
                <Share2 className="w-4 h-4" />
                <span>Share</span>
              </button>
            </div>

            {/* Comment Drawer if opened */}
            {openCommentPost === 'welcome' && (
              <div className="p-3.5 bg-slate-50 border-t border-slate-100 space-y-2">
                {postComments.welcome.map((c, i) => (
                  <div key={i} className="text-xs bg-white p-2 rounded-lg border border-slate-200">
                    <span className="font-bold text-slate-800">{fullName}: </span>
                    <span className="text-slate-600">{c}</span>
                  </div>
                ))}
                <div className="flex gap-2">
                  <input
                    type="text"
                    value={commentInput}
                    onChange={(e) => setCommentInput(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleAddComment('welcome')}
                    placeholder="Write a comment..."
                    className="flex-1 text-xs px-3 py-1.5 rounded-lg border border-slate-200 bg-white outline-none focus:border-blue-500"
                  />
                  <button
                    onClick={() => handleAddComment('welcome')}
                    className="px-3 py-1.5 bg-[#1877f2] text-white text-xs font-bold rounded-lg"
                  >
                    Post
                  </button>
                </div>
              </div>
            )}
          </div>

          {/* Post 2: HR Department Attendance Post */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div className="p-3.5 flex items-center justify-between border-b border-slate-100">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-gradient-to-br from-[#e67e22] to-[#f39c12] text-white flex items-center justify-center shrink-0">
                  <Bell className="w-5 h-5" />
                </div>
                <div>
                  <h2 className="text-sm font-bold text-slate-900 leading-tight">
                    HR Department
                  </h2>
                  <div className="text-xs text-slate-400 flex items-center gap-1">
                    <Globe className="w-3 h-3" />
                    <span>Today at {new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                  </div>
                </div>
              </div>
              <button className="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-50">
                <MoreHorizontal className="w-4 h-4" />
              </button>
            </div>

            <div className="bg-gradient-to-br from-[#27ae60] to-[#2ecc71] text-white p-6 sm:p-8 text-center flex flex-col items-center justify-center min-h-[160px]">
              <div className="text-xl sm:text-2xl font-bold leading-tight">
                Don't forget to clock in today!
              </div>
              <div className="text-sm font-normal opacity-90 mt-1">
                Tap the button below to record your attendance
              </div>
            </div>

            <div className="p-3.5 sm:p-4">
              <button
                onClick={() => setActiveTab('camera_attendance')}
                className="inline-flex items-center gap-2 bg-[#1877f2] hover:bg-blue-600 text-white rounded-lg px-5 py-2.5 font-bold text-sm shadow-xs transition-colors"
              >
                <Camera className="w-4 h-4" />
                <span>Open Attendance Camera</span>
              </button>
            </div>

            <div className="px-4 py-2 flex items-center justify-between text-xs text-slate-500 border-y border-slate-100">
              <div className="flex items-center gap-1.5">
                <span className="px-1.5 py-0.5 rounded-full bg-emerald-50 text-[#27ae60] font-semibold text-[11px]">
                  Done
                </span>
                <span>{postLikes.hr ? 9 : 8} reactions</span>
              </div>
            </div>

            <div className="p-1 flex">
              <button
                type="button"
                onClick={() => toggleLike('hr')}
                className={`flex-1 flex items-center justify-center gap-2 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-colors ${
                  postLikes.hr
                    ? 'text-[#1877f2] bg-blue-50'
                    : 'text-slate-600 hover:bg-slate-50'
                }`}
              >
                <ThumbsUp className="w-4 h-4" />
                <span>{postLikes.hr ? 'Liked' : 'Like'}</span>
              </button>
              <button
                type="button"
                onClick={() =>
                  setOpenCommentPost(openCommentPost === 'hr' ? null : 'hr')
                }
                className="flex-1 flex items-center justify-center gap-2 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-50 rounded-lg transition-colors"
              >
                <MessageSquare className="w-4 h-4" />
                <span>Comment</span>
              </button>
              <button
                type="button"
                onClick={() => handleShare('HR Attendance Reminder')}
                className="flex-1 flex items-center justify-center gap-2 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-50 rounded-lg transition-colors"
              >
                <Share2 className="w-4 h-4" />
                <span>Share</span>
              </button>
            </div>

            {openCommentPost === 'hr' && (
              <div className="p-3.5 bg-slate-50 border-t border-slate-100 space-y-2">
                {postComments.hr.map((c, i) => (
                  <div key={i} className="text-xs bg-white p-2 rounded-lg border border-slate-200">
                    <span className="font-bold text-slate-800">{fullName}: </span>
                    <span className="text-slate-600">{c}</span>
                  </div>
                ))}
                <div className="flex gap-2">
                  <input
                    type="text"
                    value={commentInput}
                    onChange={(e) => setCommentInput(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleAddComment('hr')}
                    placeholder="Write a comment..."
                    className="flex-1 text-xs px-3 py-1.5 rounded-lg border border-slate-200 bg-white outline-none focus:border-blue-500"
                  />
                  <button
                    onClick={() => handleAddComment('hr')}
                    className="px-3 py-1.5 bg-[#1877f2] text-white text-xs font-bold rounded-lg"
                  >
                    Post
                  </button>
                </div>
              </div>
            )}
          </div>

          {/* Post 3: Delivery Updates Post */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div className="p-3.5 flex items-center justify-between border-b border-slate-100">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-gradient-to-br from-[#27ae60] to-[#2ecc71] text-white flex items-center justify-center shrink-0">
                  <Truck className="w-5 h-5" />
                </div>
                <div>
                  <h2 className="text-sm font-bold text-slate-900 leading-tight">
                    Delivery Updates
                  </h2>
                  <div className="text-xs text-slate-400 flex items-center gap-1">
                    <Globe className="w-3 h-3" />
                    <span>Recent activity</span>
                  </div>
                </div>
              </div>
              <button className="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-50">
                <MoreHorizontal className="w-4 h-4" />
              </button>
            </div>

            <div className="p-4 pb-2">
              <p className="text-sm text-slate-800 leading-relaxed">
                <strong>Latest delivery updates</strong> - here's what's happening with your recent shipments:
              </p>
            </div>

            <div className="divide-y divide-slate-100">
              {recentDeliveries.length > 0 ? (
                recentDeliveries.map((del) => (
                  <div
                    key={del.id}
                    onClick={() => setActiveTab('delivery_tracking')}
                    className="p-3.5 px-4 flex items-center justify-between hover:bg-slate-50 transition-colors cursor-pointer"
                  >
                    <div className="flex items-center gap-3">
                      <div className="w-9 h-9 rounded-lg bg-blue-50 text-[#1877f2] flex items-center justify-center shrink-0">
                        <Truck className="w-4 h-4" />
                      </div>
                      <div>
                        <div className="text-sm font-bold text-slate-900">
                          {del.delivery_number}
                        </div>
                        <div className="text-xs text-slate-500">
                          {del.supplier_name} &middot; {del.delivery_date}
                        </div>
                      </div>
                    </div>

                    <span
                      className={`text-xs font-semibold px-2.5 py-1 rounded-full uppercase text-[11px] ${
                        del.status === 'delivered'
                          ? 'bg-[#e6f4ea] text-[#1e8e3e]'
                          : del.status === 'in_transit'
                          ? 'bg-blue-50 text-[#1877f2]'
                          : 'bg-[#fff3e0] text-[#e65100]'
                      }`}
                    >
                      {del.status.replace('_', ' ')}
                    </span>
                  </div>
                ))
              ) : (
                <div className="p-4 text-xs text-slate-500">No recent deliveries.</div>
              )}
            </div>

            <div className="p-3 px-4 border-t border-slate-100">
              <button
                onClick={() => setActiveTab('delivery_tracking')}
                className="text-xs sm:text-sm font-semibold text-[#1877f2] hover:underline flex items-center gap-1.5"
              >
                <ExternalLink className="w-3.5 h-3.5" />
                <span>View all deliveries</span>
              </button>
            </div>

            <div className="p-1 flex border-t border-slate-100">
              <button
                type="button"
                onClick={() => toggleLike('delivery')}
                className={`flex-1 flex items-center justify-center gap-2 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-colors ${
                  postLikes.delivery
                    ? 'text-[#1877f2] bg-blue-50'
                    : 'text-slate-600 hover:bg-slate-50'
                }`}
              >
                <ThumbsUp className="w-4 h-4" />
                <span>{postLikes.delivery ? 'Liked' : 'Like'}</span>
              </button>
              <button
                type="button"
                onClick={() =>
                  setOpenCommentPost(openCommentPost === 'delivery' ? null : 'delivery')
                }
                className="flex-1 flex items-center justify-center gap-2 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-50 rounded-lg transition-colors"
              >
                <MessageSquare className="w-4 h-4" />
                <span>Comment</span>
              </button>
              <button
                type="button"
                onClick={() => handleShare('McPIL Lab Delivery Updates')}
                className="flex-1 flex items-center justify-center gap-2 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-50 rounded-lg transition-colors"
              >
                <Share2 className="w-4 h-4" />
                <span>Share</span>
              </button>
            </div>
          </div>

          {/* Post 4: Lab Inventory Post */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div className="p-3.5 flex items-center justify-between border-b border-slate-100">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-gradient-to-br from-[#8e44ad] to-[#9b59b6] text-white flex items-center justify-center shrink-0">
                  <FlaskConical className="w-5 h-5" />
                </div>
                <div>
                  <h2 className="text-sm font-bold text-slate-900 leading-tight">
                    Lab Inventory
                  </h2>
                  <div className="text-xs text-slate-400 flex items-center gap-1">
                    <Globe className="w-3 h-3" />
                    <span>Earlier today</span>
                  </div>
                </div>
              </div>
              <button className="text-slate-400 hover:text-slate-600 p-1.5 rounded-full hover:bg-slate-50">
                <MoreHorizontal className="w-4 h-4" />
              </button>
            </div>

            <div className="p-4">
              <p className="text-sm text-slate-800 leading-relaxed">
                The lab inventory has been updated. Check the latest stock levels to ensure all materials are well-stocked for your work today.
              </p>
            </div>

            <div className="px-4 pb-4">
              <button
                onClick={() => setActiveTab('inventory')}
                className="inline-flex items-center gap-2 bg-slate-50 hover:bg-slate-100 text-slate-800 border border-slate-200 rounded-lg px-5 py-2.5 font-semibold text-sm shadow-xs transition-colors"
              >
                <Boxes className="w-4 h-4 text-[#8e44ad]" />
                <span>View Inventory</span>
              </button>
            </div>

            <div className="px-4 py-2 flex items-center justify-between text-xs text-slate-500 border-t border-slate-100">
              <div className="flex items-center gap-1.5">
                <span className="px-1.5 py-0.5 rounded-full bg-purple-50 text-[#8e44ad] font-semibold text-[11px]">
                  Updated
                </span>
                <span>{postLikes.inventory ? 7 : 6} reactions</span>
              </div>
            </div>

            <div className="p-1 flex border-t border-slate-100">
              <button
                type="button"
                onClick={() => toggleLike('inventory')}
                className={`flex-1 flex items-center justify-center gap-2 py-2 text-xs sm:text-sm font-semibold rounded-lg transition-colors ${
                  postLikes.inventory
                    ? 'text-[#1877f2] bg-blue-50'
                    : 'text-slate-600 hover:bg-slate-50'
                }`}
              >
                <ThumbsUp className="w-4 h-4" />
                <span>{postLikes.inventory ? 'Liked' : 'Like'}</span>
              </button>
              <button
                type="button"
                onClick={() =>
                  setOpenCommentPost(openCommentPost === 'inventory' ? null : 'inventory')
                }
                className="flex-1 flex items-center justify-center gap-2 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-50 rounded-lg transition-colors"
              >
                <MessageSquare className="w-4 h-4" />
                <span>Comment</span>
              </button>
              <button
                type="button"
                onClick={() => handleShare('Lab Inventory Status')}
                className="flex-1 flex items-center justify-center gap-2 py-2 text-xs sm:text-sm font-semibold text-slate-600 hover:bg-slate-50 rounded-lg transition-colors"
              >
                <Share2 className="w-4 h-4" />
                <span>Share</span>
              </button>
            </div>
          </div>
        </div>

        {/* Right Sidebar Column */}
        <div className="lg:col-span-4 space-y-4">
          {/* Quick Access Widget */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-xs p-4">
            <h3 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
              Quick Access
            </h3>
            <div className="divide-y divide-slate-100 text-sm">
              <button
                onClick={() => setActiveTab('camera_attendance')}
                className="w-full flex items-center gap-3 py-2.5 text-slate-700 hover:text-[#1877f2] font-medium transition-colors text-left"
              >
                <Clock className="w-4 h-4 text-[#1877f2] shrink-0" />
                <span>Clock In / Out</span>
              </button>
              <button
                onClick={() => setActiveTab('attendance')}
                className="w-full flex items-center gap-3 py-2.5 text-slate-700 hover:text-[#1877f2] font-medium transition-colors text-left"
              >
                <History className="w-4 h-4 text-[#1877f2] shrink-0" />
                <span>Attendance History</span>
              </button>
              <button
                onClick={() => setActiveTab('inventory')}
                className="w-full flex items-center gap-3 py-2.5 text-slate-700 hover:text-[#1877f2] font-medium transition-colors text-left"
              >
                <Boxes className="w-4 h-4 text-[#1877f2] shrink-0" />
                <span>Inventory</span>
              </button>
              <button
                onClick={() => setActiveTab('reports')}
                className="w-full flex items-center gap-3 py-2.5 text-slate-700 hover:text-[#1877f2] font-medium transition-colors text-left"
              >
                <BarChart3 className="w-4 h-4 text-[#1877f2] shrink-0" />
                <span>Reports</span>
              </button>
              <button
                onClick={() => {
                  alert('Team Messages opened.');
                }}
                className="w-full flex items-center gap-3 py-2.5 text-slate-700 hover:text-[#1877f2] font-medium transition-colors text-left"
              >
                <MessageSquare className="w-4 h-4 text-[#1877f2] shrink-0" />
                <span>Team Messages</span>
              </button>
            </div>
          </div>

          {/* Your Dashboard Widget */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-xs p-4">
            <h3 className="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">
              Your Dashboard
            </h3>
            <div className="divide-y divide-slate-100 text-sm">
              <div className="flex items-center justify-between py-2">
                <span className="text-slate-500">Purchase Orders</span>
                <span className="font-semibold text-slate-900">
                  {stats.total_purchase_orders}
                </span>
              </div>
              <div className="flex items-center justify-between py-2">
                <span className="text-slate-500">Pending Deliveries</span>
                <span
                  className={`font-semibold ${
                    stats.pending_deliveries > 0 ? 'text-rose-600' : 'text-emerald-600'
                  }`}
                >
                  {stats.pending_deliveries}
                </span>
              </div>
              <div className="flex items-center justify-between py-2">
                <span className="text-slate-500">Unread Messages</span>
                <span
                  className={`font-semibold ${
                    unreadMessages > 0 ? 'text-rose-600' : 'text-emerald-600'
                  }`}
                >
                  {unreadMessages > 0 ? unreadMessages : 'None'}
                </span>
              </div>
              <div className="flex items-center justify-between py-2">
                <span className="text-slate-500">Team Size</span>
                <span className="font-semibold text-slate-900">
                  {stats.total_employees} members
                </span>
              </div>
            </div>
          </div>

          {/* Contacts Widget */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-xs p-4">
            <div className="flex items-center justify-between mb-3">
              <h3 className="text-xs font-bold text-slate-500 uppercase tracking-wider">
                Contacts
              </h3>
              <button
                type="button"
                onClick={() => {
                  const to = prompt('Recipient name:');
                  const msg = prompt('Message:');
                  if (to && msg) alert(`Message sent to ${to}: "${msg}"`);
                }}
                className="text-slate-400 hover:text-slate-600 p-1 rounded-md hover:bg-slate-50"
                title="New message"
              >
                <Edit className="w-4 h-4" />
              </button>
            </div>

            <div className="space-y-1">
              <div
                onClick={() => alert('Direct message to HR Department opened.')}
                className="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 cursor-pointer transition-colors"
              >
                <div className="relative">
                  <div className="w-9 h-9 rounded-full bg-gradient-to-br from-[#1877f2] to-blue-600 text-white font-bold text-xs flex items-center justify-center">
                    HR
                  </div>
                  <div className="w-2.5 h-2.5 rounded-full bg-emerald-500 absolute bottom-0 right-0 ring-2 ring-white" />
                </div>
                <span className="text-sm font-semibold text-slate-800">
                  HR Department
                </span>
              </div>

              <div
                onClick={() => alert('Direct message to Store Manager opened.')}
                className="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 cursor-pointer transition-colors"
              >
                <div className="relative">
                  <div className="w-9 h-9 rounded-full bg-gradient-to-br from-[#27ae60] to-emerald-600 text-white font-bold text-xs flex items-center justify-center">
                    ST
                  </div>
                  <div className="w-2.5 h-2.5 rounded-full bg-emerald-500 absolute bottom-0 right-0 ring-2 ring-white" />
                </div>
                <span className="text-sm font-semibold text-slate-800">
                  Store Manager
                </span>
              </div>

              <div
                onClick={() => alert('Direct message to Admin opened.')}
                className="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50 cursor-pointer transition-colors"
              >
                <div className="relative">
                  <div className="w-9 h-9 rounded-full bg-gradient-to-br from-[#e67e22] to-amber-600 text-white font-bold text-xs flex items-center justify-center">
                    AD
                  </div>
                </div>
                <span className="text-sm font-semibold text-slate-800">
                  Admin
                </span>
              </div>
            </div>
          </div>

          {/* Footer links */}
          <div className="text-xs text-slate-400 px-2 flex flex-wrap items-center gap-x-3 gap-y-1">
            <a href="#" className="hover:underline">Privacy</a>
            <span>&middot;</span>
            <a href="#" className="hover:underline">Terms</a>
            <span>&middot;</span>
            <a href="#" className="hover:underline">Help</a>
            <span>&middot;</span>
            <span>McPIL &copy; {new Date().getFullYear()}</span>
          </div>
        </div>
      </div>
    </div>
  );
};
