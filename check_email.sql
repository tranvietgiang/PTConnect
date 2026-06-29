SELECT u.email, u.role, s.full_name, s.student_code, c.name AS lop, c.grade_level AS khoi
FROM users u
JOIN parents p ON p.user_id = u.id
JOIN students s ON s.id = p.student_id
JOIN classrooms c ON c.id = s.classroom_id
WHERE u.email = 'wedgiang@gmail.com';
