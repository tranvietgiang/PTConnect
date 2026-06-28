import { Outlet } from "react-router-dom";

function AuthLayout() {
  return (
    <main className="grid min-h-screen place-items-center bg-brand-bg px-4 py-10">
      <div className="w-full max-w-md">
        <div className="mb-6 text-center">
          <div className="mx-auto mb-3 grid size-12 place-items-center rounded-md bg-brand-white p-1 shadow-sm ring-1 ring-brand-border">
            <img
              alt="PTConnect"
              className="size-10 object-contain"
              src="/logo-ptconnect/ptconnect-favicon-512.png"
            />
          </div>
          <h1 className="text-2xl font-bold text-brand-text">PTConnect</h1>
          <p className="mt-1 text-sm text-brand-muted">
            kết nối phụ huynh với giáo viên
          </p>
        </div>
        <Outlet />
      </div>
    </main>
  );
}

export default AuthLayout;
