import { Link } from "react-router-dom";

const NotFoundPage = () => {
  return (
    <div className="min-h-screen bg-[#f5f7fa] flex items-center justify-center p-4 relative overflow-hidden">
      {/* Animated background circles */}
      <div className="absolute inset-0 overflow-hidden">
        <div className="absolute -top-40 -right-40 w-80 h-80 bg-[#147d8b] rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-pulse"></div>
        <div className="absolute -bottom-40 -left-40 w-80 h-80 bg-[#e5262a] rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-pulse delay-1000"></div>
        <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-[#e8f3f5] rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse delay-2000"></div>
      </div>

      {/* Main card */}
      <div className="relative bg-white/90 backdrop-blur-sm rounded-2xl shadow-2xl p-8 md:p-12 max-w-2xl w-full border border-[#e5e7eb] hover:shadow-3xl transition-shadow duration-300">
        {/* Decorative top bar */}
        <div className="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-[#147d8b] via-[#0f6370] to-[#147d8b] rounded-t-2xl"></div>

        <div className="text-center">
          {/* Illustration */}
          <div className="flex justify-center mb-6">
            <div className="relative">
              {/* Main icon circle */}
              <div className="w-32 h-32 md:w-40 md:h-40 rounded-full bg-gradient-to-br from-[#147d8b] to-[#0f6370] flex items-center justify-center shadow-lg animate-bounce-slow">
                <svg
                  className="w-16 h-16 md:w-20 md:h-20 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>
              {/* Decorative rings */}
              <div className="absolute -inset-4 rounded-full border-2 border-[#147d8b]/30 animate-ping-slow"></div>
              <div className="absolute -inset-8 rounded-full border-2 border-[#147d8b]/20 animate-ping-slower"></div>
            </div>
          </div>

          {/* 404 Title */}
          <h1 className="text-8xl md:text-9xl font-bold bg-gradient-to-r from-[#147d8b] via-[#0f6370] to-[#147d8b] bg-clip-text text-transparent mb-2 animate-gradient">
            404
          </h1>

          {/* Main heading */}
          <h2 className="text-2xl md:text-3xl font-semibold text-[#1f2937] mb-3">
            Không Tìm Thấy Trang
          </h2>

          {/* Divider */}
          <div className="w-20 h-1 bg-gradient-to-r from-[#147d8b] to-[#0f6370] mx-auto mb-4 rounded-full"></div>

          {/* Message */}
          <p className="text-[#6b7280] mb-4 text-base md:text-lg">
            Trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển.
          </p>

          {/* PTConnect friendly message */}
          <div className="bg-[#e8f3f5] rounded-lg p-4 mb-6 border border-[#147d8b]/20">
            <div className="flex items-center justify-center gap-2">
              <svg
                className="w-5 h-5 text-[#147d8b] flex-shrink-0"
                fill="currentColor"
                viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  fillRule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                  clipRule="evenodd"
                />
              </svg>
              <span className="text-[#1f2937] text-sm md:text-base">
                Đừng lo lắng! Bạn có thể quay lại bảng điều khiển PTConnect hoặc
                đăng nhập để tiếp tục công việc.
              </span>
            </div>
          </div>

          {/* Buttons */}
          <div className="flex flex-col sm:flex-row gap-3 justify-center">
            <Link
              to="/"
              className="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-[#147d8b] to-[#0f6370] text-white font-medium rounded-lg hover:from-[#0f6370] hover:to-[#0a4d58] transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105"
            >
              <svg
                className="w-5 h-5 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                />
              </svg>
              Về Trang Chủ
            </Link>
            <Link
              to="/login"
              className="inline-flex items-center justify-center px-6 py-3 bg-white text-[#147d8b] font-medium rounded-lg border-2 border-[#147d8b]/30 hover:border-[#147d8b] hover:bg-[#e8f3f5] transition-all duration-200 shadow-sm hover:shadow-md transform hover:scale-105"
            >
              <svg
                className="w-5 h-5 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"
                />
              </svg>
              Đăng Nhập
            </Link>
          </div>

          {/* Footer note */}
          <p className="mt-6 text-xs text-[#6b7280]">PTConnect &bull; xxxx</p>
        </div>
      </div>
    </div>
  );
};

export default NotFoundPage;
