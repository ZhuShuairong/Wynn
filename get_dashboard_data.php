<?php
// Database configuration
$servername = "Localhost";
$username = "root"; // Replace with your database username
$password = "";      // Replace with your database password
$dbname = "wynn_fyp";

// Create a connection to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Query to fetch all rows from topics_file
$sql = "SELECT Title, Content FROM topics_file";
$result = $conn->query($sql);

$trendingTopics = [];
$totalArticles = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Aggregate word counts
        $words = explode(',', $row['Content']);
        foreach ($words as $word) {
            $word = trim($word); // Remove extra spaces
            if (!empty($word)) {
                $trendingTopics[$word] = ($trendingTopics[$word] ?? 0) + 1;
            }
        }

        // Count total articles
        $totalArticles++;
    }
}

// Sort words by frequency in descending order
arsort($trendingTopics);

// Get top 10 trending topics
$trendingTopics = array_slice($trendingTopics, 0, 10, true);

// Return data as JSON
echo json_encode([
    'trending_topics' => array_map(function ($topic, $count) {
        return ['Topic' => $topic, 'article_count' => $count];
    }, array_keys($trendingTopics), $trendingTopics),
    'total_articles' => $totalArticles
]);
?>