import {
  Bell,
  BookOpen,
  CalendarCheck,
  ClipboardList,
  GraduationCap,
  LayoutDashboard,
  Settings,
  ShieldCheck,
  Users,
  X,
} from "lucide-react";
import { NavLink } from "react-router-dom";
import { useAuth } from "../../store/useAuth";
import Button from "../common/Button";

const navItems = [
  {
    icon: LayoutDashboard,
    label: "Tổng quan",
    roles: ["school_admin", "system_admin"],
    to: "/tong-quan",
    end: true,
  },
  {
    icon: Users,
    label: "Học sinh",
    roles: ["school_admin", "system_admin", "teacher", "assistant"],
    to: "/hoc-sinh",
    end: true,
  },
  {
    icon: BookOpen,
    label: "Lớp học",
    roles: ["school_admin", "system_admin", "teacher", "assistant"],
    to: "/lop-hoc",
    end: true,
  },
  {
    icon: CalendarCheck,
    label: "Điểm danh",
    roles: ["school_admin", "system_admin", "teacher", "assistant"],
    to: "/diem-danh",
    end: true,
  },
  {
    icon: GraduationCap,
    label: "Điểm số",
    roles: ["school_admin", "system_admin", "teacher", "assistant"],
    to: "/diem-so",
    end: true,
  },
  {
    icon: ClipboardList,
    label: "Bài tập",
    roles: ["school_admin", "system_admin", "teacher", "assistant", "student"],
    to: "/bai-tap",
    end: true,
  },
  {
    icon: GraduationCap,
    label: "Điểm của tôi",
    roles: ["student"],
    to: "/diem-cua-toi",
    end: true,
  },
  {
    icon: Bell,
    label: "Thông báo",
    roles: ["school_admin", "system_admin", "teacher", "assistant"],
    to: "/thong-bao",
    end: true,
  },
  {
    icon: Settings,
    label: "Quản lý buổi học",
    roles: ["school_admin", "system_admin"],
    to: "/diem-danh/quan-ly-buoi",
    end: true,
  },
];

function Sidebar({ isOpen, onClose }) {
  const { user } = useAuth();
  const visibleItems = navItems.filter((item) =>
    item.roles.includes(user?.role),
  );

  return (
    <>
      <div
        className={`fixed inset-0 z-30 bg-brand-text/40 transition lg:hidden ${isOpen ? "block" : "hidden"}`}
        onClick={onClose}
      />
      <aside
        className={`fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-brand-border bg-brand-white transition-transform lg:static lg:translate-x-0 ${
          isOpen ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        <div className="flex h-16 items-center gap-3 border-b border-brand-border px-5">
          <div className="grid size-10 place-items-center rounded-md bg-brand-teal text-brand-white">
            <ShieldCheck aria-hidden="true" className="size-6" />
          </div>
          <div>
            <p className="text-base font-bold text-brand-text">PTConnect</p>
            <p className="text-xs text-brand-muted">Quản lý học sinh</p>
          </div>
          <Button
            aria-label="Đóng menu"
            className="ml-auto size-10 px-0 lg:hidden"
            icon={X}
            iconClassName="size-6"
            onClick={onClose}
            variant="ghost"
          />
        </div>
        <nav className="flex-1 space-y-1 p-4">
          {visibleItems.map((item) => {
            const Icon = item.icon;

            return (
              <NavLink
                className={({ isActive }) =>
                  `flex h-11 items-center gap-3 rounded-md px-3 text-sm font-medium transition ${
                    isActive
                      ? "bg-brand-teal-soft text-brand-teal-dark"
                      : "text-brand-muted hover:bg-brand-bg hover:text-brand-text"
                  }`
                }
                end={item.end}
                key={item.to}
                onClick={onClose}
                to={item.to}
              >
                <Icon aria-hidden="true" className="size-5" />
                {item.label}
              </NavLink>
            );
          })}
        </nav>
        <div className="border-t border-brand-border p-4 text-xs text-brand-muted">
          Hệ thống quản lý học sinh PTConnect.
        </div>
      </aside>
    </>
  );
}

export default Sidebar;
