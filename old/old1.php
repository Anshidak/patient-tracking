<?php
include_once("db_connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prepare SQL statements for security
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        // Handle edit action
        $room = $_POST['room'];
        $status = $_POST['status'];

        // Prepare to update status only
        $stmt = $conn->prepare("UPDATE `cleaning_table` SET `Status`=?, `Modified_Date`=NOW(), `PC_Name`=? WHERE `Room`=?");
        $stmt->bind_param("sss", $status, $_SERVER['REMOTE_ADDR'], $room);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['room']) && isset($_POST['name'])) {
        // Prepare to insert
        $room = $_POST['room'];
        $name = $_POST['name'];
        $status = $_POST['status'];
        $nursingArea = $_POST['nursing_area']; // Get nursing area

        // Insert a new room entry without checking for existing room
        $stmt = $conn->prepare("INSERT INTO `cleaning_table` (`Room`, `Name`, `Status`, `Nursing_area`, `Modified_Date`, `PC_Name`) VALUES (?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("sssss", $room, $name, $status, $nursingArea, $_SERVER['REMOTE_ADDR']);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete_id'])) {
        // Handle deletion (if needed)
        $delete_id = $_POST['delete_id'];
        $stmt = $conn->prepare("DELETE FROM `cleaning_table` WHERE `Room`=?");
        $stmt->bind_param("s", $delete_id);
        $stmt->execute();
        $stmt->close();
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
$sql_query = "SELECT `Room`, `Name`, `Status`, `Nursing_area`, `Modified_Date`, `PC_Name` FROM `cleaning_table` WHERE 1=1";

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
                <select name="filter_nursing_area" class="form-control" id="filter_nursing_area">
                    <option value="">All</option>
                    <option value="4A1" <?php if ($filterNursingArea == '4A1') echo 'selected'; ?>>4A1</option>
                    <option value="4A2" <?php if ($filterNursingArea == '4A2') echo 'selected'; ?>>4A2</option>
                    <option value="5A" <?php if ($filterNursingArea == '5A') echo 'selected'; ?>>5A</option>
                    <option value="6A" <?php if ($filterNursingArea == '6A') echo 'selected'; ?>>6A</option>
                    <option value="7A" <?php if ($filterNursingArea == '7A') echo 'selected'; ?>>7A</option>
                    <option value="4B" <?php if ($filterNursingArea == '4B') echo 'selected'; ?>>4B</option>
                    <option value="5B" <?php if ($filterNursingArea == '5B') echo 'selected'; ?>>5B</option>
                    <option value="6B" <?php if ($filterNursingArea == '6B') echo 'selected'; ?>>6B</option>
                    <option value="7B" <?php if ($filterNursingArea == '7B') echo 'selected'; ?>>7B</option>
                    <option value="8B" <?php if ($filterNursingArea == '8B') echo 'selected'; ?>>8B</option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="date_filter">Filter by Date</label>
                <input type="date" name="date_filter" class="form-control" id="date_filter" value="<?php echo htmlspecialchars($dateFilter); ?>">
        </div>
        </div>
        
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>

    <table class="table mt-4">
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
                        <?php
                        // Get the modified date and check if it matches today's date
                        $modifiedDate = new DateTime($row['Modified_Date']);
                        $today = new DateTime();
                        $isToday = $modifiedDate->format('Y-m-d') === $today->format('Y-m-d');
                        ?>
                        <button class="btn btn-warning" data-toggle="modal" data-target="#editModal" data-room="<?php echo htmlspecialchars($row['Room']); ?>" data-name="<?php echo htmlspecialchars($row['Name']); ?>" data-status="<?php echo htmlspecialchars($row['Status']); ?>" <?php echo !$isToday ? 'disabled' : ''; ?>>Edit</button>
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?php echo htmlspecialchars($row['Room']); ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this entry?')" <?php echo !$isToday ? 'disabled' : ''; ?>>Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal for adding patient -->
<div class="modal fade" id="addPatientModal" tabindex="-1" role="dialog" aria-labelledby="addPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPatientModalLabel">Add Patient</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="room">Room No</label>
                        <input type="text" class="form-control" name="room" required>
                    </div>
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" name="name" required>
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
                        <select name="nursing_area" class="form-control" required>
                            <option value="4A1">4A1</option>
                            <option value="4A2">4A2</option>
                            <option value="5A">5A</option>
                            <option value="6A">6A</option>
                            <option value="7A">7A</option>
                            <option value="4B">4B</option>
                            <option value="5B">5B</option>
                            <option value="6B">6B</option>
                            <option value="7B">7B</option>
                            <option value="8B">8B</option>
                        </select>
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

<!-- Modal for editing patient -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Patient</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_room">Room No</label>
                        <input type="text" class="form-control" name="room" id="edit_room" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_name">Name</label>
                        <input type="text" class="form-control" name="name" id="edit_name" readonly>
                    </div>
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select name="status" class="form-control" id="edit_status">
                            <option value="Discharge Recommended">Discharge Recommended</option>
                            <option value="Sent for Billing">Sent for Billing</option>
                            <option value="Final Billing">Final Billing</option>
                            <option value="Discharged">Discharged</option>
                            <option value="Patient Vacated">Patient Vacated</option>
                            <option value="Cleaning in Progress">Cleaning in Progress</option>
                            <option value="Room Cleaned">Room Cleaned</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="action" value="edit">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Script to populate edit modal
    $('#editModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); // Button that triggered the modal
        var room = button.data('room'); // Extract info from data-* attributes
        var name = button.data('name');
        var status = button.data('status');

        var modal = $(this);
        modal.find('#edit_room').val(room);
        modal.find('#edit_name').val(name);
        modal.find('#edit_status').val(status);
    });
</script>

<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script src="C:\wamp64\www\clean\dist\animation.js"></script>
</body>
</html>
