<?php
/*

//API filtering example
https://yourfullstack.com/apps/f3nation_api/api.php?table=locations&limit=5&sort=desc&like_column=description&like_value=TX

//API simplistic example
https://yourfullstack.com/apps/f3nation_api/api.php?table=locations&limit=5

//orgs
https://yourfullstack.com/apps/f3nation_api/api.php?table=orgs&limit=10

//events
https://yourfullstack.com/apps/f3nation_api/api.php?table=events&limit=10

*/
ini_set('display_errors', 1);ini_set('display_startup_errors', 1);error_reporting(E_ALL);
header('Content-Type: application/json');


// Validate and sanitize table name
$valid_tables = ['locations', 'orgs', 'events'];  // Add allowed table names to this list




  
// Database connection details
$host = '35.239.19.124';  // Change to your database host
$dbname = 'f3_staging';  // Your PostgreSQL database name
$user = 'hatch';  // Your PostgreSQL username
$password = '6?Q/VYQa9gUQeZSx';  // Your PostgreSQL password

// Establish connection to PostgreSQL using PDO
// Establish connection to PostgreSQL using PDO
try {
    $dsn = "pgsql:host=$host;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// Get parameters from the URL (GET params)
$table = isset($_GET['table']) ? $_GET['table'] : '';  // Default empty table
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;  // Default to 10 results if limit is not provided
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'asc';  // Default to ascending order if sort is not provided
$like_column = isset($_GET['like_column']) ? $_GET['like_column'] : '';  // The column to apply LIKE on
$like_value = isset($_GET['like_value']) ? $_GET['like_value'] : '';  // The value to apply with LIKE

if (!in_array($table, $valid_tables)) {
    echo json_encode(['error' => 'Invalid table name']);
    exit;
}

// Validate sort parameter (either asc or desc)
if ($sort !== 'asc' && $sort !== 'desc') {
    echo json_encode(['error' => 'Invalid sort parameter. Use "asc" or "desc".']);
    exit;
}

// Base query
$query = "SELECT * FROM $table";

// If a LIKE filter is provided, add it to the query
if (!empty($like_column) && !empty($like_value)) {
    // Sanitize the LIKE value by using a wildcard search with proper escaping
    $like_value = "%" . $like_value . "%";
    $query .= " WHERE $like_column LIKE :like_value";  // Directly use the provided column name
}

// Add ORDER BY clause for sorting
$query .= " ORDER BY id $sort";  // Adjust 'id' to the column you'd like to sort by

// Apply limit
$query .= " LIMIT :limit";

// Prepare and execute the query
$stmt = $pdo->prepare($query);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

// If a LIKE filter is provided, bind the like_value parameter
if (!empty($like_value)) {
    $stmt->bindParam(':like_value', $like_value, PDO::PARAM_STR);
}

$stmt->execute();

// Fetch all rows
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Return the results as JSON
echo json_encode($rows);

?>