<?php
session_start();
header('Content-Type: application/json');

// --- Database credentials ---
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "wynn_fyp";

// Hardcoded values for days and top N topics
$days_param             = 7; // Default number of days for trending topics
$top_n_topics           = 25; // Number of top topics for sidebar and pie chart
$top_word_cloud         = 30; // Number of words for word cloud

// Define custom day ranges for specific charts (hardcoded)
$topic_distribution_days = 28; // Topic Distribution Over Time
$heatmap_days            = 28; // Calendar Heat Map (Topic Activity)
$scatter_plot_days       = 25; // Topic Hotness Distribution

// --- Connect to the database ---
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'DB connect failed: ' . $conn->connect_error]));
}
$conn->set_charset("utf8mb4");

// --- Helper function to fetch data for a chart ---
function fetchChartData($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

// --- 1) Total Articles Count ---
$totalArticlesQuery = "SELECT COUNT(*) AS cnt FROM topics_file";
$totalArticlesData = fetchChartData($conn, $totalArticlesQuery);
$totalArticles = (int)($totalArticlesData[0]['cnt'] ?? 0);

// --- 2) Trending Topics ---
$date_cutoff = date('Y-m-d H:i:s', strtotime("-{$days_param} days"));
$trendingTopicsQuery = "
    SELECT Topic_ID, Content, DATE(Created_Time) AS article_date
    FROM topics_file
    WHERE Created_Time >= ?
";
$trendingTopicsData = fetchChartData($conn, $trendingTopicsQuery, [$date_cutoff]);

$topic_article_ids = [];
$daily_topic_article_ids = [];
$daily_article_counts = [];
foreach ($trendingTopicsData as $row) {
    $id = $row['Topic_ID'];
    $d = $row['article_date'];
    $keys = explode(',', $row['Content'] ?? '');

    // Track total articles per day
    if (!isset($daily_article_counts[$d])) {
        $daily_article_counts[$d] = 0;
    }
    $daily_article_counts[$d]++;

    foreach ($keys as $kw) {
        $t = trim($kw);
        if ($t === '') continue;
        $topic_article_ids[$t][$id] = true;
        $daily_topic_article_ids[$d][$t][$id] = true;
    }
}

// --- 3) Sidebar Counts ---
$all_topic_counts = [];
foreach ($topic_article_ids as $t => $ids) {
    $all_topic_counts[$t] = count($ids);
}
arsort($all_topic_counts);
$top_topics = array_slice(array_keys($all_topic_counts), 0, $top_n_topics);

$sidebar_topics_output = [];
foreach ($top_topics as $t) {
    $weekly_count = 0;
    foreach (array_keys($daily_topic_article_ids) as $d) {
        if (isset($daily_topic_article_ids[$d][$t])) {
            $weekly_count += count($daily_topic_article_ids[$d][$t]);
        }
    }
    $sidebar_topics_output[] = ['Topic' => $t, 'article_count' => $weekly_count];
}
usort($sidebar_topics_output, function($a, $b) {
    return $b['article_count'] <=> $a['article_count'];
});

// --- 4) Trend Data ---
$days_labels = [];
for ($i = 0; $i < $days_param; $i++) {
    $days_labels[] = date('Y-m-d', strtotime("-{$i} days"));
}
$days_labels = array_reverse($days_labels);

$topic_trends_output = [];
foreach ($top_topics as $t) {
    $topic_trends_output[$t] = array_fill(0, count($days_labels), 0);
}
foreach ($days_labels as $i => $d) {
    if (isset($daily_topic_article_ids[$d])) {
        foreach ($top_topics as $t) {
            $topic_trends_output[$t][$i] = count($daily_topic_article_ids[$d][$t] ?? []);
        }
    }
}

// --- 5) Daily Top Topic ---
$daily_top_topic_output = [];
foreach ($days_labels as $d) {
    $counts = [];
    foreach ($daily_topic_article_ids[$d] ?? [] as $t => $ids) {
        $counts[$t] = count($ids);
    }
    arsort($counts);
    $top_topic = array_key_first($counts);
    $daily_top_topic_output[] = [
        'day' => date('D', strtotime($d)),
        'topic' => $top_topic,
        'count' => $counts[$top_topic] ?? 0
    ];
}

// --- 6) Clustered Bar Chart ---
$daily_top_three_topics = [];
foreach ($days_labels as $d) {
    $counts = [];
    foreach ($daily_topic_article_ids[$d] ?? [] as $t => $ids) {
        $counts[$t] = count($ids);
    }
    arsort($counts);
    $top3 = array_slice($counts, 0, 3, true);
    $cluster = [];
    foreach ($top3 as $t => $count) {
        $cluster[] = ['topic' => $t, 'count' => $count];
    }
    $daily_top_three_topics[] = [
        'day' => date('D', strtotime($d)),
        'date' => $d,
        'topics' => $cluster
    ];
}

// --- 7) Pie Chart & Word Cloud ---
$pie_chart_data = array_slice($all_topic_counts, 0, $top_n_topics, true);
$word_cloud_data = array_slice($all_topic_counts, 0, $top_word_cloud, true);

// --- 8) Heatmap ---
$heatmap_data = [];
$heatmap_dates = [];
for ($i = 0; $i < $heatmap_days; $i++) {
    $heatmap_dates[] = date('Y-m-d', strtotime("-{$i} days"));
}
$heatmap_dates = array_reverse($heatmap_dates);

foreach ($heatmap_dates as $d) {
    $heatmap_data[$d] = $daily_article_counts[$d] ?? 0;
}

// --- 9) Monthly Trends (Topic Distribution Over Time) ---
$monthly_dates = [];
for ($i = 0; $i < $topic_distribution_days; $i++) {
    $monthly_dates[] = date('Y-m-d', strtotime("-{$i} days"));
}
$monthly_dates = array_reverse($monthly_dates);

$monthly_topic_trends = [];
foreach ($top_topics as $t) {
    $monthly_topic_trends[$t] = array_fill(0, count($monthly_dates), 0);
}
foreach ($monthly_dates as $i => $d) {
    if (isset($daily_topic_article_ids[$d])) {
        foreach ($top_topics as $t) {
            $monthly_topic_trends[$t][$i] = count($daily_topic_article_ids[$d][$t] ?? []);
        }
    }
}

// --- 10) Scatter Plot ---
$scatter_data = [];
$scatter_dates = [];
for ($i = 0; $i < $scatter_plot_days; $i++) {
    $scatter_dates[] = date('Y-m-d', strtotime("-{$i} days"));
}
$scatter_dates = array_reverse($scatter_dates);

foreach ($top_topics as $t) {
    $weekly_count = 0;
    foreach ($scatter_dates as $d) {
        if (isset($daily_topic_article_ids[$d][$t])) {
            $weekly_count += count($daily_topic_article_ids[$d][$t]);
        }
    }
    $scatter_data[] = ['topic' => $t, 'count' => $weekly_count];
}

// --- 11) User Preferences ---
$user_preferences = null;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $preferencesQuery = "
        SELECT visible_chart_types
        FROM user_dashboard_preferences
        WHERE User_ID = ?
    ";
    $prefData = fetchChartData($conn, $preferencesQuery, [$uid]);
    if (!empty($prefData)) {
        $user_preferences = ['visible_chart_types' => $prefData[0]['visible_chart_types']];
    }
}

// --- Output JSON ---
$output = [
    'trending_topics'  => $sidebar_topics_output,
    'total_articles'   => $totalArticles,
    'last_update_time' => date('Y-m-d H:i:s'),
    'chart_data'       => [
        'days_labels'     => $days_labels,
        'topic_trends'    => $topic_trends_output,
        'daily_top_topic' => $daily_top_topic_output,
        'pie_chart'       => $pie_chart_data,
        'word_cloud'      => $word_cloud_data,
        'clustered_bar'   => $daily_top_three_topics,
        'heatmap'         => $heatmap_data,
        'monthly_trends'  => $monthly_topic_trends,
        'monthly_dates'   => $monthly_dates,
        'scatter_data'    => $scatter_data
    ],
    'user_preferences' => $user_preferences
];

echo json_encode($output, JSON_PRETTY_PRINT);
$conn->close();
?>