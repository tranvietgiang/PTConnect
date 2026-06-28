import { Bell, LogOut, Menu, Search } from "lucide-react";
import Button from "../common/Button";
import { useAuth } from "../../store/useAuth";

function Header({ onMenuClick }) {
  const { logout, user } = useAuth();

  return (
    <header className="sticky top-0 z-20 border-b border-brand-border bg-brand-white/95 backdrop-blur">
      <div className="flex h-16 items-center gap-3 px-4 sm:px-6">
        <Button
          aria-label="Mở menu"
          className="size-13 px-0 lg:hidden"
          icon={Menu}
          iconClassName="!size-11"
          onClick={onMenuClick}
          variant="ghost"
        />
        <div className="hidden h-10 flex-1 items-center gap-2 rounded-md border border-brand-border bg-brand-bg px-3 text-sm text-brand-muted md:flex">
          <Search aria-hidden="true" className="size-5" />
          <span>Tìm học sinh, lớp học, báo cáo</span>
        </div>
        <div className="ml-auto flex items-center gap-2">
          <Button
            aria-label="Thông báo"
            className="size-13 px-0"
            icon={Bell}
            iconClassName="!size-11"
            variant="ghost"
          />
          <div className="hidden text-right sm:block">
            <p className="text-sm font-semibold text-brand-text">
              {user?.name}
            </p>
            <p className="text-xs capitalize text-brand-muted">{user?.role}</p>
          </div>
          <Button
            aria-label="Đăng xuất"
            className="size-12 px-0"
            icon={LogOut}
            iconClassName="!size-11"
            onClick={logout}
            variant="secondary"
          />
        </div>
      </div>
    </header>
  );
}

export default Header;
