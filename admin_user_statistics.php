<?php
$pageTitle = "User Statistics";
require_once 'admin_header.php';
require_once 'admin_db_connect.php';


// --- 1. User Growth: Monthly Registrations ---
$monthly_growth = [];
$sql_growth = "SELECT DATE_FORMAT(Add_Time, '%Y-%m') as month, COUNT(*) as users_added
               FROM user_file
               GROUP BY month
               ORDER BY month";
$result_growth = $conn->query($sql_growth);
if ($result_growth) {
    while ($row = $result_growth->fetch_assoc()) {
        $monthly_growth[] = $row;
    }
}

// --- 2. Interest Category Distribution ---
$interest_stats = [];
$sql_interests = "SELECT Interest_Category, COUNT(*) as count
                  FROM user_interest_file
                  WHERE Interest_Category IS NOT NULL AND Interest_Category != ''
                  GROUP BY Interest_Category
                  ORDER BY count DESC";
$result_interests = $conn->query($sql_interests);
if ($result_interests) {
    while ($row = $result_interests->fetch_assoc()) {
        $interest_stats[] = $row;
    }
}

$conn->close();
?>

<h1>User Statistics</h1>

<div class="message notice"><?php echo $stats_message; ?></div>

<!-- 1. User Growth Over Time -->
<h2>User Growth Over Time</h2>
<canvas id="growthChart" style="max-width: 700px;"></canvas>

<!-- 2. Monthly Additions Table -->
<h3 style="margin-top: 2rem;" >Users Added Per Month</h3>
<table>
    <thead>
        <tr>
            <th>Month</th>
            <th>Users Added</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($monthly_growth as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['month']); ?></td>
                <td><?php echo htmlspecialchars($row['users_added']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- 3. User Interests -->
<h2 style="margin-top: 5rem;">User Interest Distribution</h2>
<?php if (!empty($interest_stats)): ?>
    <table>
        <thead>
            <tr>
                <th>Interest Category</th>
                <th>Number of Users</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($interest_stats as $stat): ?>
                <tr>
                    <td><?php echo htmlspecialchars($stat['Interest_Category']); ?></td>
                    <td><?php echo htmlspecialchars($stat['count']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No user interest data found or unable to fetch statistics.</p>
<?php endif; ?>

<!-- Chart.js for Growth Chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const growthCtx = document.getElementById('growthChart').getContext('2d');
    const growthChart = new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthly_growth, 'month')); ?>,
            datasets: [{
                label: 'Cumulative Users',
                data: (function() {
                    const monthly = <?php echo json_encode(array_column($monthly_growth, 'users_added')); ?>;
                    let cumulative = [], total = 0;
                    for (let i = 0; i < monthly.length; i++) {
                        total += parseInt(monthly[i]);
                        cumulative.push(total);
                    }
                    return cumulative;
                })(),
                borderColor: '#0A74DA',
                backgroundColor: 'rgba(10,116,218,0.2)',
                fill: true,
                tension: 0.2
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
</script>

<?php require_once 'admin_footer.php'; ?>
