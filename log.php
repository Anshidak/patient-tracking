<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once("db_connect.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add Patient
    if (isset($_POST['room']) && isset($_POST['name']) && isset($_POST['status']) && isset($_POST['nursing_area']) && isset($_POST['doctor_name'])) {
        $room = htmlspecialchars($_POST['room']);
        $name = htmlspecialchars($_POST['name']);
        $status = htmlspecialchars($_POST['status']);
        $nursing_area = htmlspecialchars($_POST['nursing_area']);
        $doctor_name = htmlspecialchars($_POST['doctor_name']); // Doctor Name

        $stmt = $conn->prepare("INSERT INTO cleaning_table (Room, Name, Status, Nursing_area, Doctor_Name, Modified_Date, PC_Name) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("ssssss", $room, $name, $status, $nursing_area, $doctor_name, gethostname());
        $stmt->execute();
        $stmt->close();

        // Log the insertion
        $log_action = 'INSERT';
        logAction($conn, $log_action, $room, $name, $status, $nursing_area, $doctor_name);
    }

    // Edit Status
    if (isset($_POST['edit_id']) && isset($_POST['edit_status'])) {
        $id = $_POST['edit_id'];
        $status = htmlspecialchars($_POST['edit_status']);

        $stmt = $conn->prepare("UPDATE cleaning_table SET Status = ?, Modified_Date = NOW(), PC_Name = ? WHERE id = ?");
        $stmt->bind_param("ssi", $status, gethostname(), $id);
        $stmt->execute();
        $stmt->close();

        // Log the update
        $stmt_fetch = $conn->prepare("SELECT Name, Nursing_area, Room, Doctor_Name FROM cleaning_table WHERE id = ?");
        $stmt_fetch->bind_param("i", $id);
        $stmt_fetch->execute();
        $stmt_fetch->bind_result($name, $nursing_area, $room, $doctor_name);
        $stmt_fetch->fetch();
        $stmt_fetch->close();

        $log_action = 'UPDATE';
        logAction($conn, $log_action, $room, $name, $status, $nursing_area, $doctor_name);
    }

    // Delete
    if (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];

        // Fetch Room and Name for logging
        $stmt_fetch = $conn->prepare("SELECT Room, Name, Doctor_Name FROM cleaning_table WHERE id = ?");
        $stmt_fetch->bind_param("i", $delete_id);
        $stmt_fetch->execute();
        $stmt_fetch->bind_result($room, $name, $doctor_name);
        $stmt_fetch->fetch();
        $stmt_fetch->close();

        // Delete the entry
        $stmt = $conn->prepare("DELETE FROM cleaning_table WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();

        // Log the deletion
        $log_action = 'DELETE';
        logAction($conn, $log_action, $room, $name, '', '', $doctor_name);
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Function to log actions
function logAction($conn, $action, $room, $name, $status, $nursing_area, $doctor_name) {
    $stmt_log = $conn->prepare("INSERT INTO cleaning_table_log (action, room, name, status, nursing_area, doctor_name, modified_date, pc_name, operation) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
    $pc_name = gethostname();
    $operation = strtolower($action); // e.g. insert, update, delete
    $stmt_log->bind_param("ssssssss", $action, $room, $name, $status, $nursing_area, $doctor_name, $pc_name, $operation);
    $stmt_log->execute();
    $stmt_log->close();
}

// Handle filtering and searching
$filterStatus = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$searchRoom = isset($_GET['search_room']) ? $_GET['search_room'] : '';
$dateFilter = isset($_GET['date_filter']) ? $_GET['date_filter'] : date('Y-m-d'); // Default to today's date
$filterNursingArea = isset($_GET['filter_nursing_area']) ? $_GET['filter_nursing_area'] : ''; // New filter for nursing area
$filterDoctor = isset($_GET['filter_doctor']) ? $_GET['filter_doctor'] : ''; // Filter for Doctor

// Prepare SQL query with filtering
$sql_query = "SELECT id, Room, Name, Status, Nursing_area, Doctor_Name, Modified_Date, PC_Name FROM cleaning_table WHERE 1=1";

if ($filterStatus) {
    $sql_query .= " AND Status = ?";
}
if ($searchRoom) {
    $sql_query .= " AND Room LIKE ?";
}
if ($filterNursingArea) {
    $sql_query .= " AND Nursing_area = ?";
}
if ($filterDoctor) {
    $sql_query .= " AND Doctor_Name LIKE ?";
}
$sql_query .= " AND DATE(Modified_Date) = ?";
$sql_query .= " ORDER BY Room";

$params = [];
$types = '';

if ($filterStatus) {
    $params[] = $filterStatus;
    $types .= 's';
}
if ($searchRoom) {
    $params[] = "%$searchRoom%";
    $types .= 's';
}
if ($filterNursingArea) {
    $params[] = $filterNursingArea;
    $types .= 's';
}
if ($filterDoctor) {
    $params[] = "%$filterDoctor%";
    $types .= 's';
}
$params[] = $dateFilter; // Date filter
$types .= 's';

// Prepare statement
$stmt = $conn->prepare($sql_query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultset = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Cleaning Status</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container home">
        <center><h2><u>ROOM CLEANING STATUS</u></h2></center>
        <br>
        <hr>

        <!-- Filter Form -->
        <form method="GET" id="filter-form" class="mt-4">
            <div class="form-row align-items-end">
                <div class="form-group col-md-4">
                    <label for="filter_status">Filter by Status</label>
                    <select name="filter_status" class="form-control" id="filter_status">
                        <option value="">All</option>
                        <option value="Discharge Recommended" <?php if ($filterStatus == 'Discharge Recommended') echo 'selected'; ?>>Discharge Recommended</option>
                        <option value="Sent for Billing" <?php if ($filterStatus == 'Sent for Billing') echo 'selected'; ?>>Sent for Billing</option>
                        <option value="Final Billing" <?php if ($filterStatus == 'Final Billing') echo 'selected'; ?>>Final Billing</option>
                        <option value="Discharged" <?php if ($filterStatus == 'Discharged') echo 'selected'; ?>>Discharged</option>
                        <option value="Patient Vacated" <?php if ($filterStatus == 'Patient Vacated') echo 'selected'; ?>>Patient Vacated</option>
                        <option value="Cleaning in Progress" <?php if ($filterStatus == 'Cleaning in Progress') echo 'selected'; ?>>Cleaning in Progress</option>
                        <option value="Room Cleaned" <?php if ($filterStatus == 'Room Cleaned') echo 'selected'; ?>>Room Cleaned</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="search_room">Search by Room No</label>
                    <input type="text" name="search_room" class="form-control" value="<?php echo $searchRoom; ?>" id="search_room" placeholder="Room No">
                </div>
                <div class="form-group col-md-4">
                    <label for="filter_nursing_area">Filter by Nursing Area</label>
                    <input type="text" name="filter_nursing_area" class="form-control" value="<?php echo $filterNursingArea; ?>" id="filter_nursing_area" placeholder="Nursing Area">
                </div>
                <div class="form-group col-md-4">
                    <label for="filter_doctor">Filter by Doctor Name</label>
                    <input type="text" name="filter_doctor" class="form-control" value="<?php echo $filterDoctor; ?>" id="filter_doctor" placeholder="Doctor Name">
                </div>
                <div class="form-group col-md-2">
                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                </div>
            </div>
        </form>

        <br>

        <!-- Table for displaying records -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Room</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Nursing Area</th>
                    <th>Doctor Name</th>
                    <th>Modified Date</th>
                    <th>PC Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $resultset->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $row['Room']; ?></td>
                        <td><?php echo $row['Name']; ?></td>
                        <td>
                            <form action="" method="POST" class="form-inline">
                                <input type="hidden" name="edit_id" value="<?php echo $row['id']; ?>">
                                <select class="form-control" name="edit_status">
                                    <option value="Discharge Recommended" <?php if ($row['Status'] == 'Discharge Recommended') echo 'selected'; ?>>Discharge Recommended</option>
                                    <option value="Sent for Billing" <?php if ($row['Status'] == 'Sent for Billing') echo 'selected'; ?>>Sent for Billing</option>
                                    <option value="Final Billing" <?php if ($row['Status'] == 'Final Billing') echo 'selected'; ?>>Final Billing</option>
                                    <option value="Discharged" <?php if ($row['Status'] == 'Discharged') echo 'selected'; ?>>Discharged</option>
                                    <option value="Patient Vacated" <?php if ($row['Status'] == 'Patient Vacated') echo 'selected'; ?>>Patient Vacated</option>
                                    <option value="Cleaning in Progress" <?php if ($row['Status'] == 'Cleaning in Progress') echo 'selected'; ?>>Cleaning in Progress</option>
                                    <option value="Room Cleaned" <?php if ($row['Status'] == 'Room Cleaned') echo 'selected'; ?>>Room Cleaned</option>
                                </select>
                                <button type="submit" class="btn btn-success">Update</button>
                            </form>
                        </td>
                        <td><?php echo $row['Nursing_area']; ?></td>
                        <td><?php echo $row['Doctor_Name']; ?></td>
                        <td><?php echo $row['Modified_Date']; ?></td>
                        <td><?php echo $row['PC_Name']; ?></td>
                        <td>
                            <form method="POST" action="" style="display:inline-block">
                                <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Add New Record Modal Form -->
        <form action="" method="POST">
            <div class="form-group">
                <label for="room">Room No</label>
                <input type="text" name="room" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="name">Patient Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" class="form-control" required>
                    <option value="Discharge Recommended">Discharge Recommended</option>
                    <option value="Sent for Billing">Sent for Billing</option>
                    <option value="Final Billing">Final Billing</option>
                    <option value="Discharged">Discharged</option>
                    <option value="Patient Vacated">Patient Vacated</option>
                    <option value="Cleaning in Progress">Cleaning in Progress</option>
                    <option value="Room Cleaned">Room Cleaned</option>
                </select>
            </div>
            <div class="form-group">
                <label for="nursing_area">Nursing Area</label>
                <input type="text" name="nursing_area" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="doctor_name">Doctor Name</label>
                <input type="text" name="doctor_name" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Add Record</button>
        </form>
    </div>
</body>
</html>
