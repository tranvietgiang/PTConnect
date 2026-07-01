import { useState } from "react";
import { LogIn } from "lucide-react";
import { Navigate, useLocation, useNavigate } from "react-router-dom";
import Button from "../../components/common/Button";
import Input from "../../components/common/Input";
import Loading from "../../components/common/Loading";
import { useAuth } from "../../store/useAuth";
import { useToast } from "../../store/useToast";
import {
  getDefaultRouteByRole,
  getSafeRedirectPath,
} from "../../utils/roleRedirect";

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function buildLoginPayload(form) {
  const identifier = form.identifier.trim();
  const payload = {
    password: form.password,
    remember_me: form.remember_me,
  };

  if (emailPattern.test(identifier)) {
    payload.email = identifier;
  } else {
    payload.username = identifier;
  }

  return payload;
}

function LoginPage() {
  const { checkingAuth, isAuthenticated, login, user } = useAuth();
  const toast = useToast();
  const location = useLocation();
  const navigate = useNavigate();
  const [form, setForm] = useState({
    identifier: "",
    password: "",
    remember_me: false,
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  if (checkingAuth) {
    return <Loading label="Đang kiểm tra đăng nhập" />;
  }

  if (isAuthenticated) {
    return <Navigate replace to={getDefaultRouteByRole(user?.role)} />;
  }

  const validateForm = () => {
    const nextErrors = {};
    const identifier = form.identifier.trim();

    if (!identifier) {
      nextErrors.identifier = "Vui lòng nhập email hoặc username.";
    }

    if (!form.password.trim()) {
      nextErrors.password = "Vui lòng nhập mật khẩu.";
    }

    setErrors(nextErrors);

    if (Object.keys(nextErrors).length > 0) {
      toast.error(
        "Thiếu thông tin đăng nhập",
        "Vui lòng nhập email/username và mật khẩu.",
      );
      return false;
    }

    return true;
  };

  const handleSubmit = async (event) => {
    event.preventDefault();

    if (!validateForm()) return;

    setLoading(true);

    try {
      const response = await login(buildLoginPayload(form));
      const role = response.data?.user?.role;

      toast.success(
        "Đăng nhập thành công",
        "Hệ thống đã ghi nhớ phiên đăng nhập theo lựa chọn của bạn.",
      );
      navigate(getSafeRedirectPath(role, location.state?.from?.pathname), {
        replace: true,
      });
    } catch (error) {
      toast.error(
        "Đăng nhập thất bại",
        error.message || "Vui lòng kiểm tra email/username và mật khẩu.",
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <form
      autoComplete="on"
      className="rounded-lg border border-brand-border bg-brand-white p-6 shadow-sm"
      onSubmit={handleSubmit}
    >
      <div className="space-y-4">
        <Input
          autoComplete="username"
          error={errors.identifier}
          id="identifier"
          label="Email hoặc username"
          name="username"
          onChange={(event) => {
            setForm({ ...form, identifier: event.target.value });
            setErrors({ ...errors, identifier: undefined });
          }}
          placeholder="email hoặc username"
          type="text"
          value={form.identifier}
        />
        <Input
          autoComplete="current-password"
          error={errors.password}
          id="password"
          label="Mật khẩu"
          name="password"
          onChange={(event) => {
            setForm({ ...form, password: event.target.value });
            setErrors({ ...errors, password: undefined });
          }}
          placeholder="Nhập mật khẩu"
          showPasswordToggle
          type="password"
          value={form.password}
        />
        <label className="flex items-start gap-2 text-sm text-brand-muted">
          <input
            checked={form.remember_me}
            className="mt-1 size-4 rounded border-brand-border text-brand-teal focus:ring-brand-teal"
            name="remember_me"
            onChange={(event) =>
              setForm({ ...form, remember_me: event.target.checked })
            }
            type="checkbox"
          />
          <span>Ghi nhớ đăng nhập trên thiết bị này</span>
        </label>
      </div>
      <Button
        className="mt-6 w-full"
        disabled={loading}
        icon={LogIn}
        type="submit"
      >
        {loading ? "Đang đăng nhập" : "Đăng nhập"}
      </Button>
    </form>
  );
}

export default LoginPage;
