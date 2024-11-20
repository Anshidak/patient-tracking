<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once("db_connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prepare SQL statements for security
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        // Handle edit action
        $id = $_POST['id'];
        $status = $_POST['status'];

        // Prepare to update status only based on `id`
        $stmt = $conn->prepare("UPDATE `cleaning_table` SET `Status`=?, `Modified_Date`=NOW(), `PC_Name`=? WHERE `id`=?");
        $stmt->bind_param("ssi", $status, $_SERVER['REMOTE_ADDR'], $id);
        if (!$stmt->execute()) {
            error_log('Database Error: ' . $stmt->error);
        }
        $stmt->close();

        // Fetch the Name and Nursing Area for the log
        $stmt_fetch = $conn->prepare("SELECT `Name`, `Nursing_area`, `Room` FROM `cleaning_table` WHERE `id`=?");
        $stmt_fetch->bind_param("i", $id);
        $stmt_fetch->execute();
        $stmt_fetch->bind_result($name, $nursingArea, $room);
        $stmt_fetch->fetch();
        $stmt_fetch->close();

        // Log the update into the log table
        $stmt_log = $conn->prepare("INSERT INTO cleaning_table_log (action, room, name, status, nursing_area, modified_date, pc_name, operation) VALUES (?, ?, ?, ?, ?, NOW(), ?, 'update')");
        $log_action = 'UPDATE';
        $stmt_log->bind_param("sssss", $log_action, $room, $name, $status, $nursingArea, $_SERVER['REMOTE_ADDR']);
        if (!$stmt_log->execute()) {
            error_log('Database Error: ' . $stmt_log->error);
        }
        $stmt_log->close();

    } elseif (isset($_POST['room']) && isset($_POST['name'])) {
        // Handle insertion of a new entry
        $room = $_POST['room'];
        $name = $_POST['name'];
        $status = $_POST['status'];
        $nursingArea = $_POST['nursing_area']; // Get nursing area

        // Insert a new room entry
        $stmt = $conn->prepare("INSERT INTO `cleaning_table` (`Room`, `Name`, `Status`, `Nursing_area`, `Modified_Date`, `PC_Name`) VALUES (?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("sssss", $room, $name, $status, $nursingArea, $_SERVER['REMOTE_ADDR']);
        if (!$stmt->execute()) {
            error_log('Database Error: ' . $stmt->error);
        }
        $stmt->close();

        // Log the insertion into the log table
        $stmt_log = $conn->prepare("INSERT INTO cleaning_table_log (action, room, name, status, nursing_area, modified_date, pc_name, operation) VALUES (?, ?, ?, ?, ?, NOW(), ?, 'insert')");
        if ($stmt_log === false) {
            error_log('Error in log query: ' . $conn->error);
        }
        $log_action = 'INSERT';
        $stmt_log->bind_param("sssss", $log_action, $room, $name, $status, $nursingArea, $_SERVER['REMOTE_ADDR']);
        if (!$stmt_log->execute()) {
            error_log('Database Error: ' . $stmt_log->error);
        }
        $stmt_log->close();

    } elseif (isset($_POST['delete_id'])) {
        // Handle deletion action
        $delete_id = $_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM `cleaning_table` WHERE `id`=?");
        $stmt->bind_param("i", $delete_id);
        if (!$stmt->execute()) {
            error_log('Database Error: ' . $stmt->error);
        }
        $stmt->close();

        // Log the deletion into the log table using the room
        $stmt_fetch = $conn->prepare("SELECT `Room` FROM `cleaning_table` WHERE `id`=?");
        $stmt_fetch->bind_param("i", $delete_id);
        $stmt_fetch->execute();
        $stmt_fetch->bind_result($room);
        $stmt_fetch->fetch();
        $stmt_fetch->close();

        $stmt_log = $conn->prepare("INSERT INTO cleaning_table_log (action, room, operation, performed_at) VALUES (?, ?, 'delete', NOW())");
        $log_action = 'DELETE';
        $stmt_log->bind_param("ss", $log_action, $room);
        if (!$stmt_log->execute()) {
            error_log('Database Error: ' . $stmt_log->error);
        }
        $stmt_log->close();
    }

    // After processing form data, redirect to prevent resubmission on refresh
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Handle filtering and searching
$filterStatus = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$searchRoom = isset($_GET['search_room']) ? $_GET['search_room'] : '';
$dateFilter = isset($_GET['date_filter']) ? $_GET['date_filter'] : date('Y-m-d'); // Default to today's date
$filterNursingArea = isset($_GET['filter_nursing_area']) ? $_GET['filter_nursing_area'] : ''; // New filter for nursing area

// Prepare SQL query with filtering, searching, and date filter
$sql_query = "SELECT `id`, `Room`, `Name`, `Status`, `Nursing_area`, `Modified_Date`, `PC_Name` FROM `cleaning_table` WHERE 1=1";

if ($filterStatus) {
    $sql_query .= " AND `Status` = ?";
}

if ($searchRoom) {
    $searchRoom = mysqli_real_escape_string($conn, $searchRoom);
    $sql_query .= " AND `Room` LIKE ?";
}

if ($filterNursingArea) {
    $sql_query .= " AND `Nursing_area` = ?";
}

$sql_query .= " AND DATE(`Modified_Date`) = ?"; // Filter by date
$sql_query .= " ORDER BY `Room`";
$stmt = $conn->prepare($sql_query);

// Bind parameters if necessary
$params = [];
$types = '';

if ($filterStatus) {
    $params[] = $filterStatus;
    $types .= 's';
}

if ($searchRoom) {
    $params[] = "%$searchRoom%"; // Adjust for room search
    $types .= 's';
}

if ($filterNursingArea) {
    $params[] = $filterNursingArea;
    $types .= 's';
}

$params[] = $dateFilter; // Date filter
$types .= 's';

// Prepare statement
$stmt->bind_param($types, ...$params);
$stmt->execute();
$resultset = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Room Cleaning Status</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container home">
    <center><h2><u>ROOM CLEANING STATUS</u></h2></center>
    <br>

    <button class="btn btn-primary" data-toggle="modal" data-target="#addPatientModal">Add Patient</button>

    <hr>

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
                <input type="text" name="search_room" class="form-control" id="search_room" value="<?php echo htmlspecialchars($searchRoom); ?>">
            </div>
            <div class="form-group col-md-4">
                <label for="filter_nursing_area">Filter by Nursing Area</label>
                <input type="text" name="filter_nursing_area" class="form-control" id="filter_nursing_area" value="<?php echo htmlspecialchars($filterNursingArea); ?>">
            </div>
        </div>
        <div class="form-row align-items-end">
            <div class="form-group col-md-4">
                <label for="date_filter">Filter by Date</label>
                <input type="date" name="date_filter" class="form-control" id="date_filter" value="<?php echo htmlspecialchars($dateFilter); ?>">
            </div>
            <div class="form-group col-md-4">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </div>
    </form>

    <table class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>Room No</th>
                <th>Name</th>
                <th>Status</th>
                <th>Nursing Area</th>
                <th>Modified Date</th>
                <th>PC Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $resultset->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['Room']); ?></td>
                <td><?php echo htmlspecialchars($row['Name']); ?></td>
                <td><?php echo htmlspecialchars($row['Status']); ?></td>
                <td><?php echo htmlspecialchars($row['Nursing_area']); ?></td>
                <td><?php echo htmlspecialchars($row['Modified_Date']); ?></td>
                <td><?php echo htmlspecialchars($row['PC_Name']); ?></td>
                <td>
                    <button class="btn btn-info" data-toggle="modal" data-target="#editModal"
                            data-id="<?php echo $row['id']; ?>"
                            data-room="<?php echo htmlspecialchars($row['Room']); ?>"
                            data-name="<?php echo htmlspecialchars($row['Name']); ?>"
                            data-status="<?php echo htmlspecialchars($row['Status']); ?>">
                        Edit
                    </button>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this entry?');">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Add Patient Modal -->
<div class="modal fade" id="addPatientModal" tabindex="-1" role="dialog" aria-labelledby="addPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPatientModalLabel">Add Patient</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="room">Room No</label>
                        <input type="text" name="room" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" class="form-control">
                            <option value="Cleaning in Progress">Cleaning in Progress</option>
                            <option value="Room Cleaned">Room Cleaned</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="nursing_area">Nursing Area</label>
                        <input type="text" name="nursing_area" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Patient Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Patient</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id"> <!-- Hidden ID for edit -->
                    <div class="form-group">
                        <label for="edit_room">Room No</label>
                        <input type="text" name="room" id="edit_room" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_name">Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="Cleaning in Progress">Cleaning in Progress</option>
                            <option value="Room Cleaned">Room Cleaned</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="action" value="edit" class="btn btn-primary">Update Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $('#editModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var room = button.data('room');
        var name = button.data('name');
        var status = button.data('status');
        var id = button.data('id'); // Ensure you have the ID

        var modal = $(this);
        modal.find('#edit_room').val(room);
        modal.find('#edit_name').val(name);
        modal.find('#edit_status').val(status);
        modal.find('#edit_id').val(id); // Populate hidden ID
    });
</script>

<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
