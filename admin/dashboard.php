<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$page_title = "Dashboard";
include 'includes/admin_header.php';

// Stats Query
$stats_sql = "SELECT 
    COUNT(*) as total_applications,
    SUM(CASE WHEN application_status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN application_status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN application_status = 'Rejected' THEN 1 ELSE 0 END) as rejected
    FROM student_profiles";
$stats_result = $conn->query($stats_sql) or die("Query Error: " . $conn->error);
$stats = $stats_result->fetch_assoc();

// Campus Stats
$campus_stats = [];
$campus_sql = "SELECT applied_campus, COUNT(*) as total, SUM(CASE WHEN application_status = 'Approved' THEN 1 ELSE 0 END) as approved FROM student_profiles GROUP BY applied_campus";
$campus_result = $conn->query($campus_sql) or die("Campus-wise query failed: " . $conn->error);
while ($row = $campus_result->fetch_assoc()) $campus_stats[] = $row;

// Recent Applications
$recent_applications = [];
$recent_sql = "SELECT sp.user_id, sp.full_name, sp.applied_campus, sp.application_status, sp.submitted_at, u.b_form FROM student_profiles sp JOIN registered_users u ON sp.user_id = u.id ORDER BY sp.submitted_at DESC LIMIT 5";
$recent_result = $conn->query($recent_sql) or die("Recent applications query failed: " . $conn->error);
while ($row = $recent_result->fetch_assoc()) $recent_applications[] = $row;
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Admin Dashboard</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Overview & Insights</li>
    </ol>

    <div class="row g-4 mb-4">
        <?php
        $card_data = [
            ['Total Applications', $stats['total_applications'], 'primary', 'applications.php'],
            ['Pending', $stats['pending'], 'warning', 'applications.php?status=Pending'],
            ['Approved', $stats['approved'], 'success', 'applications.php?status=Approved'],
            ['Rejected', $stats['rejected'], 'danger', 'applications.php?status=Rejected']
        ];
        foreach ($card_data as [$title, $count, $color, $link]): ?>
            <div class="col-md-3">
                <div class="card bg-<?= $color ?> text-white h-100">
                    <div class="card-body">
                        <h5><?= $title ?></h5>
                        <h2><?= $count ?></h2>
                    </div>
                    <div class="card-footer">
                        <a href="<?= $link ?>" class="text-white">View <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    

    <div class="row mb-4">
        <!-- Campus Stats (Placeholder for future chart integration) -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">Campus-wise Stats</div>
                <div class="card-body">
                    <table class="table table-sm table-striped">
                        <thead><tr><th>Campus</th><th>Total</th><th>Approved</th></tr></thead>
                        <tbody>
                        <?php foreach ($campus_stats as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['applied_campus']) ?></td>
                                <td><?= $c['total'] ?></td>
                                <td><?= $c['approved'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Application Trends Chart Placeholder -->
        <div class="col-md-6">
    <div class="card h-100">
        <div class="card-header">Status Chart</div>
        <div class="card-body p-3"> <!-- Added p-3 for padding -->
            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>
    </div>

    <div class="card mb-4 shadow">
    <div class="card-header">
        <h5 class="mb-0">Top Applied Campuses</h5>
    </div>
    <div class="card-body">
        <ul class="list-group">
            <?php
            usort($campus_stats, fn($a, $b) => $b['total'] <=> $a['total']);
            foreach (array_slice($campus_stats, 0, 5) as $campus): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= htmlspecialchars($campus['applied_campus']) ?>
                    <span class="badge bg-primary rounded-pill"><?= $campus['total'] ?> Applications</span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>





    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-table me-1"></i> Recent Applications</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th><th>Name</th><th>B-form</th><th>Campus</th><th>Status</th><th>Date</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent_applications as $app): ?>
                        <tr>
                            <td><?= $app['user_id'] ?></td>
                            <td><?= htmlspecialchars($app['full_name']) ?></td>
                            <td><?= $app['b_form'] ?></td>
                            <td><?= $app['applied_campus'] ?></td>
                            <td><span class="badge bg-<?php echo $app['application_status'] == 'Approved' ? 'success' : ($app['application_status'] == 'Rejected' ? 'danger' : 'warning'); ?>"><?= $app['application_status'] ?></span></td>
                            <td><?= date('d M Y', strtotime($app['submitted_at'])) ?></td>
                            <td><a href="view_application.php?id=<?= $app['user_id'] ?>" class="btn btn-sm btn-primary">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    /* Chart Container Styles */
    .chart-container {
        position: relative;
        height: 100%;
        min-height: 200px; /* Reduced from 250px */
        max-height: 300px;
        margin: 0 auto;
        width: 100%;
    }
    
    #statusChart {
        width: 100% !important;
        height: 100% !important;
        display: block;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .chart-container {
            min-height: 180px; /* Smaller on mobile */
            max-height: 250px;
        }
    }
</style>

<!-- Chart JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('statusChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Approved', 'Rejected'],
                    datasets: [{
                        data: [<?= $stats['pending'] ?>, <?= $stats['approved'] ?>, <?= $stats['rejected'] ?>],
                        backgroundColor: ['#ffc107', '#28a745', '#dc3545'],
                        borderColor: ['#fff', '#fff', '#fff'],
                        borderWidth: 1 // Reduced from 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Changed to false for better control
                    plugins: {
                        legend: { 
                            position: 'bottom',
                            labels: {
                                padding: 15, // Reduced from 20
                                boxWidth: 12, // Reduced from 15
                                font: {
                                    size: 12 // Reduced from 13
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0,0,0,0.8)',
                            titleFont: {
                                size: 13 // Reduced from 14
                            },
                            bodyFont: {
                                size: 12 // Reduced from 13
                            }
                        }
                    },
                    cutout: '60%', // Reduced from 65%
                    animation: {
                        animateScale: true,
                        animateRotate: true
                    }
                }
            });
        }
    });
</script>


<?php include 'includes/admin_footer.php'; ?>
