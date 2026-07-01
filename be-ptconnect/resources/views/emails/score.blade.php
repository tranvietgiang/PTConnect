<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo điểm bài tập</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:Arial,Helvetica,sans-serif">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5;padding:20px 0">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:560px;background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.1)">

                    <tr>
                        <td style="background-color:#0d9488;padding:20px 24px;text-align:center">
                            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:bold">PTConnect</h1>
                            <p style="margin:4px 0 0;color:#ccfbf1;font-size:13px">Hệ thống quản lý học tập</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px">
                            <h2 style="margin:0 0 12px;color:#1e293b;font-size:17px;font-weight:bold">Thông báo điểm bài tập</h2>
                            <p style="margin:0 0 16px;color:#475569;font-size:14px;line-height:1.6">Bài tập của học sinh đã được chấm điểm.</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8fafc;border-radius:6px;padding:12px 16px;margin-bottom:16px">
                                <tr>
                                    <td style="padding:4px 0;font-size:13px;color:#64748b">Học sinh</td>
                                    <td style="padding:4px 0;font-size:13px;color:#1e293b;font-weight:600">{{ $studentName }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;font-size:13px;color:#64748b">Lớp</td>
                                    <td style="padding:4px 0;font-size:13px;color:#1e293b;font-weight:600">{{ $className }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;font-size:13px;color:#64748b">Bài tập</td>
                                    <td style="padding:4px 0;font-size:13px;color:#1e293b;font-weight:600">{{ $assignmentTitle }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;font-size:13px;color:#64748b">Điểm</td>
                                    <td style="padding:4px 0;font-size:13px">
                                        <span style="display:inline-block;padding:2px 10px;border-radius:4px;font-weight:600;font-size:12px;background-color:{{ $score !== null && $score >= 5 ? '#16a34a' : '#dc2626' }};color:#ffffff">{{ $score !== null ? $score : 'Chưa có điểm' }}</span>
                                    </td>
                                </tr>
                                @if ($comment)
                                <tr>
                                    <td style="padding:4px 0;font-size:13px;color:#64748b">Nhận xét</td>
                                    <td style="padding:4px 0;font-size:13px;color:#1e293b;font-weight:600">{{ $comment }}</td>
                                </tr>
                                @endif
                            </table>

                            <p style="margin:0;color:#64748b;font-size:13px;line-height:1.5">
                                Đây là email tự động từ hệ thống PTConnect. Vui lòng không trả lời email này.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#f8fafc;padding:16px 24px;text-align:center;border-top:1px solid #e2e8f0">
                            <p style="margin:0;color:#94a3b8;font-size:12px">PTConnect &copy; {{ date('Y') }} &mdash; Hệ thống quản lý học tập</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
