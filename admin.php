<?php
// Include the database connection
include('db_connect.php');

// Define variables and initialize with empty values
$column_name = $column_type = "";
$column_name_err = $column_type_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate column name
    if (empty(trim($_POST["column_name"]))) {
        $column_name_err = "Please enter a column name.";
    } else {
        $column_name = trim($_POST["column_name"]);
    }
    
    // Validate column type
    if (empty(trim($_POST["column_type"]))) {
        $column_type_err = "Please select a column type.";
    } else {
        $column_type = trim($_POST["column_type"]);
    }

    // If no errors, proceed with adding the column to the table and logging the action
    if (empty($column_name_err) && empty($column_type_err)) {
        // Add the column to the cleaning_table
        $sql = "ALTER TABLE cleaning_table ADD `$column_name` $column_type";
        
        if ($conn->query($sql) === TRUE) {
            // Log the action (Make sure to update the log entry to be more meaningful)
            $log_sql = "INSERT INTO cleaning_table_log (action, room, name, status, nursing_area, modified_date, pc_name, performed_at, operation) 
                        VALUES ('INSERT', 'N/A', 'Admin', 'Added new column', 'N/A', NOW(), 'Admin', NOW(), 'add_column')";
            $conn->query($log_sql);
            
            echo "Column '$column_name' added successfully!";
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Column</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f0f0f0; }
        .container { max-width: 600px; margin: auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h2 { text-align: center; }
        .form-group { margin-bottom: 15px; }
        .form-group label { font-size: 16px; }
        .form-group input, .form-group select { width: 100%; padding: 8px; margin-top: 5px; }
        .form-group button { background-color: #4CAF50; color: white; padding: 10px 20px; border: none; cursor: pointer; width: 100%; }
        .form-group button:hover { background-color: #45a049; }
        .error { color: red; }
    </style>
</head>
<body>

<div class="container">
    <h2>Add a New Column</h2>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
            <label for="column_name">Column Name</label>
            <input type="text" name="column_name" id="column_name" value="<?php echo $column_name; ?>">
            <span class="error"><?php echo $column_name_err; ?></span>
        </div>
        
        <div class="form-group">
            <label for="column_type">Column Type</label>
            <select name="column_type" id="column_type">
                <option value="">Select column type</option>
                <option value="VARCHAR(100)" <?php if($column_type == 'VARCHAR(100)') echo 'selected'; ?>>VARCHAR(100)</option>
                <option value="INT" <?php if($column_type == 'INT') echo 'selected'; ?>>INT</option>
                <option value="DATE" <?php if($column_type == 'DATE') echo 'selected'; ?>>DATE</option>
                <option value="TEXT" <?php if($column_type == 'TEXT') echo 'selected'; ?>>TEXT</option>
                <option value="TIMESTAMP" <?php if($column_type == 'TIMESTAMP') echo 'selected'; ?>>TIMESTAMP</option>
            </select>
            <span class="error"><?php echo $column_type_err; ?></span>
        </div>

        <div class="form-group">
            <button type="submit">Add Column</button>
        </div>
    </form>
</div>

</body>
</html>
