<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: ../html/admin_login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/adminstyle.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        /* Small dashboard-specific overrides */
        .topbar { width: 95%; max-width: 1100px; margin: 18px auto 6px auto; display:flex; align-items:center; justify-content:space-between; gap:12px; background:#fff; padding:10px 14px; border-radius:10px; box-shadow: 0 6px 14px rgba(0,0,0,0.06); }
        .dashboard-wrap { display: block; gap: 24px; width: 95%; max-width: 1100px; margin: 6px auto 28px auto; }
        .main { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 6px 14px rgba(0,0,0,0.06); }
        .brand { font-weight: 700; color: #262261; font-size: 18px; }
        .topnav { display:flex; gap:10px; align-items:center; }
        .topnav a { display:flex; gap:8px; align-items:center; padding:8px 12px; color:#444; text-decoration:none; border-radius:6px; }
        .topnav a:hover { background:#f0f4ff; color:#262261; }
        .cards { display:flex; gap:12px; margin-bottom:16px; }
        .card { flex:1; padding:12px; border-radius:8px; background:linear-gradient(180deg,#ffffff,#f6f8ff); box-shadow:0 4px 10px rgba(20,30,60,0.04); }
        .card h3 { margin-bottom:6px; font-size:14px; color:#555; }
        .card p { font-size:20px; font-weight:700; color:#262261; }
        table { width:100%; border-collapse:collapse; margin-top:12px; }
        th, td { text-align:left; padding:8px; border-bottom:1px solid #eee; }
        .filter-row { display:flex; gap:12px; align-items:center; margin-bottom:12px; }
        .logout { display:inline-block; padding:8px 14px; background:#e74c3c; color:#fff; border-radius:8px; text-decoration:none; }
    </style>
</head>
<body>

<header>Library Attendance System - Admin Dashboard</header>

<div class="dashboard-wrap">
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:12px">
            <!--<div class="brand">Library Monitoring</div>-->
            <div style="font-size:13px;color:#666">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></div>
        </div>
        <nav class="topnav">
            <a href="admin_dashboard.php"><i class="bi bi-speedometer2" aria-hidden="true"></i><span>Overview</span></a>
            <a href="../html/signup.html"><i class="bi bi-person-plus" aria-hidden="true"></i><span>Register User</span></a>
            <a href="logout.php" style="color:#e74c3c;"><i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Logout</span></a>
        </nav>
    </div>

    <div class="dashboard-wrap">
    <section class="main">
        <div class="cards">
            <div class="card">
                <h3>Total Registered Users</h3>
                <p id="totalUsers">—</p>
            </div>
            <div class="card">
                <h3>Total Attendance Logs</h3>
                <p id="totalLogs">—</p>
            </div>
            <div class="card">
                <h3>Most Active Course</h3>
                <p id="topCourse">—</p>
            </div>
        </div>

        <div class="filter-row">
            <label for="dateRange">Date range:</label>
            <select id="dateRange">
                <option value="all">All time</option>
                <option value="week">Last 7 days</option>
                <option value="month">Last 30 days</option>
                <option value="year">Last 365 days</option>
                <option value="custom">Custom range</option>
            </select>

            <div id="customRange" style="display:none; align-items:center;">
                <label style="font-size:13px">From</label>
                <input type="date" id="startDate">
                <label style="font-size:13px">To</label>
                <input type="date" id="endDate">
                <button id="applyDateFilter" style="padding:6px 10px;border-radius:6px;background:#2e6bf6;color:#fff;border:none;cursor:pointer;">Apply</button>
            </div>

            <label for="courseFilter">Show courses:</label>
            <select id="courseFilter">
                <option value="all">All Courses</option>
            </select>
            <a href="logout.php" class="logout">Logout</a>
        </div>

        <h2 style="margin-top:6px">Logins per Course / Strand</h2>
        <canvas id="courseChart" height="260"></canvas>

        <h2 style="margin-top:20px">Top Active Users</h2>
        <div style="overflow:auto; max-height:260px;">
            <table id="topUsersTable">
                <thead><tr><th>Name</th><th>Course</th><th>Student ID</th><th>Logins</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </section>
</div>

<script>
let currentData = null;
let chart = null;

function escapeHtml(s){ return (s||'').toString().replace(/[&<>"']/g, function(m){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];}); }

async function fetchAndRender(range='all', start=null, end=null) {
    try {
        let qs = '';
        if (range && range !== 'all') qs += `range=${encodeURIComponent(range)}`;
        if (start) qs += (qs? '&' : '') + `start=${encodeURIComponent(start)}`;
        if (end) qs += (qs? '&' : '') + `end=${encodeURIComponent(end)}`;

        const res = await fetch('get_course_data.php' + (qs?('?'+qs):''));
        const data = await res.json();
        currentData = data;

        // Fill cards
        document.getElementById('totalUsers').textContent = data.total_users ?? '0';
        document.getElementById('totalLogs').textContent = data.total_logs ?? '0';

        // Populate course filter (only once)
        const courseFilter = document.getElementById('courseFilter');
        if (courseFilter.options.length <= 1) {
            data.courses.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c;
                opt.textContent = c;
                courseFilter.appendChild(opt);
            });
        }

        // Determine top course
        if (data.totals && data.courses && data.courses.length) {
            const maxIdx = data.totals.reduce((bestIdx, val, idx, arr) => val > (arr[bestIdx]||0) ? idx : bestIdx, 0);
            document.getElementById('topCourse').textContent = data.courses[maxIdx] + ' (' + data.totals[maxIdx] + ')';
        } else {
            document.getElementById('topCourse').textContent = '—';
        }

        // Build or update chart
        const ctx = document.getElementById('courseChart').getContext('2d');
        const palette = data.courses.map((_, i) => `hsl(${Math.floor((360/data.courses.length)*i)},70%,50%)`);

        if (!chart) {
            chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.courses,
                    datasets: [{
                        label: 'Logins',
                        data: data.totals,
                        backgroundColor: palette,
                        borderColor: palette,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        } else {
            chart.data.labels = data.courses;
            chart.data.datasets[0].data = data.totals;
            chart.data.datasets[0].backgroundColor = palette;
            chart.update();
        }

        // Populate top users table
        const tbody = document.querySelector('#topUsersTable tbody');
        tbody.innerHTML = '';
        (data.top_users || []).forEach(u => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${escapeHtml(u.name)}</td><td>${escapeHtml(u.course)}</td><td>${escapeHtml(u.student_id)}</td><td>${u.logins}</td>`;
            tbody.appendChild(tr);
        });

    } catch (err) {
        console.error('Error fetching chart data:', err);
    }
}

function setupFilters() {
    const dateRange = document.getElementById('dateRange');
    const customRange = document.getElementById('customRange');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const applyBtn = document.getElementById('applyDateFilter');
    const courseFilter = document.getElementById('courseFilter');

    dateRange.addEventListener('change', () => {
        if (dateRange.value === 'custom') {
            customRange.style.display = 'flex';
        } else {
            customRange.style.display = 'none';
            // immediate reload for non-custom ranges
            fetchAndRender(dateRange.value, null, null);
        }
    });

    applyBtn.addEventListener('click', () => {
        const s = startDate.value;
        const e = endDate.value;
        if (!s || !e) {
            alert('Please select both start and end dates.');
            return;
        }
        fetchAndRender('custom', s, e);
    });

    courseFilter.addEventListener('change', () => {
        const val = courseFilter.value;
        if (!currentData) return;
        const palette = currentData.courses.map((_, i) => `hsl(${Math.floor((360/currentData.courses.length)*i)},70%,50%)`);
        if (val === 'all') {
            chart.data.labels = currentData.courses;
            chart.data.datasets[0].data = currentData.totals;
            chart.data.datasets[0].backgroundColor = palette;
        } else {
            const idx = currentData.courses.indexOf(val);
            if (idx >= 0) {
                chart.data.labels = [currentData.courses[idx]];
                chart.data.datasets[0].data = [currentData.totals[idx]];
                chart.data.datasets[0].backgroundColor = [palette[idx]];
            }
        }
        chart.update();
    });
}

// Initialize with default (all time)
setupFilters();
fetchAndRender('all');
</script>

</body>
</html>
