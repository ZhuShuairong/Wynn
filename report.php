<?php
// report.php - Displays a specific topic report from the database

// --- Database Configuration ---
// !! IMPORTANT: Use values from your config or secure method !!
$db_user = 'root';
$db_pass = '';
$db_name = 'wynn_fyp';
$db_socket = '/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock';


// --- Get Topic from URL ---
$topic = isset($_GET['topic']) ? trim(urldecode($_GET['topic'])) : '';
$page_title_topic = $topic ? htmlspecialchars($topic, ENT_QUOTES, 'UTF-8') : 'Report'; // For display

// --- Initialize Variables ---
$report_content_html = "<p class='info-message'>No topic specified or report found for this topic.</p>"; // Default message
$generated_time_display = "N/A";
$report_found = false;

// --- Establish Database Connection ---
$conn = mysqli_connect(($db_socket ? null : $db_host), $db_user, $db_pass, $db_name, ($db_socket ? null : 3306), $db_socket);

// Check connection
if (!$conn) {
    error_log("Database Connection Error in report.php: " . mysqli_connect_error());
    $report_content_html = "<p class='error-message'>Error: Unable to connect to the database.</p>";
} else {
    mysqli_set_charset($conn, "utf8mb4");

    if (!empty($topic)) {
        // --- Query for the latest report for the SPECIFIC topic ---
        $sql = "SELECT Report_Content, Generated_Time
                FROM report_file
                WHERE Topic = ?
                ORDER BY Generated_Time DESC, Report_ID DESC
                LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $topic);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $raw_content = $row['Report_Content'];
                $generated_time_raw = $row['Generated_Time'];
                $report_found = true;

                // --- NEW: Prepare content with <p> and <h3> tags ---
                if ($raw_content) {
                    // 1. Escape HTML special chars from the raw content FIRST for security
                    $escaped_content = htmlspecialchars($raw_content, ENT_QUOTES, 'UTF-8');

                    // 2. Replace **subtitle** with <h3>subtitle</h3>
                    //    Use a placeholder temporarily if needed, or do it carefully
                    $content_with_headings = preg_replace('/\*\*(.*?)\*\*/s', '<h3>$1</h3>', $escaped_content);

                    // 3. Split into lines/blocks (handle Windows/Unix newlines)
                    $lines = preg_split('/(\r\n|\r|\n)/', $content_with_headings);

                    // 4. Process lines into paragraphs and headings
                    $html_output = "";
                    $current_paragraph = "";
                    foreach ($lines as $line) {
                        $trimmed_line = trim($line);
                        if (empty($trimmed_line)) {
                            // Empty line signifies a potential paragraph break
                            if (!empty($current_paragraph)) {
                                $html_output .= "<p>" . trim($current_paragraph) . "</p>\n"; // Close previous paragraph
                                $current_paragraph = "";
                            }
                        } elseif (substr($trimmed_line, 0, 4) === '<h3>' && substr($trimmed_line, -5) === '</h3>') {
                            // It's a heading line
                            if (!empty($current_paragraph)) {
                                $html_output .= "<p>" . trim($current_paragraph) . "</p>\n"; // Close paragraph before heading
                                $current_paragraph = "";
                            }
                            $html_output .= $trimmed_line . "\n"; // Add the heading directly
                        } else {
                            // Non-empty, non-heading line: add to current paragraph buffer
                            $current_paragraph .= $trimmed_line . " "; // Add space between joined lines
                        }
                    }
                    // Add any remaining text as the last paragraph
                    if (!empty($current_paragraph)) {
                         $html_output .= "<p>" . trim($current_paragraph) . "</p>\n";
                    }

                    $report_content_html = $html_output; // Assign the generated HTML

                } else {
                    $report_content_html = "<p class='info-message'>Report content is empty.</p>";
                }
                // --- End NEW content processing ---


                // --- Format Time ---
                if ($generated_time_raw) {
                    try { $date = new DateTime($generated_time_raw); $generated_time_display = $date->format('Y-m-d H:i:s'); }
                    catch (Exception $e) { $generated_time_display = "Invalid Date"; }
                } else { $generated_time_display = "Not Available"; }

                mysqli_free_result($result);
            } else {
                 $report_content_html = "<p class='info-message'>No report found for the topic: '" . htmlspecialchars($topic, ENT_QUOTES, 'UTF-8') . "'.</p>";
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("SQL Prepare Error in report.php: " . mysqli_error($conn));
            $report_content_html = "<p class='error-message'>Error: Could not retrieve the report.</p>";
        }
    } else {
        $report_content_html = "<p class='info-message'>Please select a topic to view its report.</p>";
    }
    mysqli_close($conn);
}

// Update download link
$download_link = "#";
if (!empty($topic)) {
    $download_link = "download_report.php?topic=" . urlencode($topic);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FinSight – Report: <?php echo $page_title_topic; ?></title>
  <style>
    /* --- Basic resets and layout --- */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { font-size: 100%; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background: #f8f9fa; color: #212529; display: flex; flex-direction: column;
      min-height: 100vh; line-height: 1.6;
    }
    header, main, footer { padding: 1rem 2rem; width: 100%; }

    /* --- Header Styling --- */
    header { background-color: #007bff; color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding-top: 1.5rem; padding-bottom: 1.5rem; }
    header h1 { margin-bottom: 0.5rem; font-weight: 600; font-size: 1.75rem; text-align: center; }
    .report-info { text-align: center; margin-bottom: 1rem; font-size: 0.9rem; color: rgba(255, 255, 255, 0.8); }
    .report-info strong { color: #fff; }
    .top-buttons { display: flex; flex-wrap: wrap; justify-content: center; gap: 0.8rem; margin-top: 1rem; }

    /* --- Button Styling --- */
    .btn { display: inline-block; background-color: #0056b3; color: #ffffff; text-decoration: none; padding: 0.6rem 1.2rem; border-radius: 0.25rem; font-weight: 500; font-size: 0.95rem; transition: background-color 0.2s ease-in-out, transform 0.1s ease, box-shadow 0.2s ease; border: none; cursor: pointer; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .btn:hover { background-color: #00418a; transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.15); }
    .btn:active { transform: translateY(0px); box-shadow: 0 1px 2px rgba(0,0,0,0.1); }

    /* --- Main Content Area --- */
    main {
        flex: 1; background: #ffffff; margin: 1.5rem auto; padding: 2rem 2.5rem;
        border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        max-width: 1140px; /* Keep increased width */
        width: 90%;
    }

    /* --- Report Content Styling (Using p and h3) --- */
    .report-content { color: #343a40; font-size: 1rem; }

    /* Style for paragraphs (<p>) */
    .report-content p {
        /* <<< INCREASED SPACE BETWEEN PARAGRAPHS */
        margin-bottom: 4em; /* Increased - Adjust as needed */
        text-align: justify; /* Justify text for report look */
        line-height: 1.75; /* Adjust line height within paragraph */
        overflow-wrap: break-word;
        word-wrap: break-word;
        hyphens: auto;
    }
    .report-content p:last-child {
        margin-bottom: 0; /* No space after the very last paragraph */
    }

    /* Style for subtitles (<h3>) */
    .report-content h3 { /* Changed from strong to h3 */
        font-weight: 700;
        /* display: block; REMOVED - h3 is block by default */
        margin-top: 1.8em; /* Space above subtitle */
        /* <<< DECREASED SPACE BELOW SUBTITLE */
        margin-bottom: 0.5em; /* Adjust as needed */
        font-size: 1.3em; /* Slightly larger subtitle */
        color: #0056b3;
        border-bottom: 1px solid #eee;
        padding-bottom: 0.2em;
        line-height: 1.4;
    }
    /* Remove extra top margin if heading is the very first element */
    .report-content > *:first-child {
         margin-top: 0 !important;
    }
    /* OR specifically target first h3 */
    /* .report-content h3:first-child { margin-top: 0; } */

    /* --- Error/Info Message Styling (Keep as is) --- */
    .error-message, .info-message { border: 1px solid transparent; padding: 1rem 1.25rem; margin-bottom: 1rem; border-radius: 0.25rem; font-weight: normal; }
    .error-message { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
    .info-message { color: #0c5460; background-color: #d1ecf1; border-color: #bee5eb; }

    /* --- Footer Styling (Keep as is) --- */
    footer { text-align: center; background-color: #e9ecef; padding: 1.5rem 0; color: #6c757d; font-size: 0.9rem; border-top: 1px solid #dee2e6; margin-top: auto; }

    /* --- Responsive Adjustments (Keep as is) --- */
    @media (min-width: 768px) {
      header { display: flex; justify-content: space-between; align-items: center; }
      header h1, .report-info { text-align: left; margin-bottom: 0; }
      .top-buttons { justify-content: flex-end; margin-top: 0; margin-bottom: 0; }
    }
    @media (max-width: 576px) {
      header, main { padding: 1rem 1rem; }
      main { margin: 1rem auto; width: 95%; padding: 1.5rem 1.5rem; max-width: 100%; }
      header h1 { font-size: 1.5rem; text-align: center; margin-bottom: 0.5rem; }
      .report-info { font-size: 0.85rem; margin-bottom: 0.8rem; text-align: center; }
      .top-buttons { justify-content: center; }
      .btn { padding: 0.5rem 1rem; font-size: 0.9rem; }
      .report-content { font-size: 0.95rem; }
      .report-content h3 { font-size: 1.1em; } /* Adjusted heading size for mobile */
    }
  </style>
</head>
<body>

  <!-- Header -->
  <header>
      <div>
          <h1>FinSight Report: <?php echo $page_title_topic; ?></h1>
          <?php if ($report_found): ?>
              <div class="report-info">
                  Generated on: <strong><?php echo $generated_time_display; ?></strong>
              </div>
          <?php endif; ?>
      </div>
      <div class="top-buttons">
          <a href="dashboard.html" class="btn">Back to Dashboard</a>
          <a href="<?php echo $download_link; ?>" class="btn" <?php if (!$report_found) echo 'style="display:none;"';?> >Download Report</a>
          <a href="logout.php" class="btn">Logout</a>
      </div>
  </header>

  <!-- Main Content -->
  <main>
    <!-- The PHP block now echoes content wrapped in <p> and <h3> tags -->
    <div class="report-content">
      <?php echo $report_content_html; ?>
    </div>
  </main>

  <!-- Footer -->
  <footer>
    <p>© <?php echo date("Y"); ?> FinSight. All rights reserved.</p>
  </footer>

</body>
</html>