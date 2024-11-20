<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once("db_connect.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add Patient
    if (isset($_POST['room']) && isset($_POST['name']) && isset($_POST['status']) && isset($_POST['nursing_area'])) {
        $room = htmlspecialchars($_POST['room']);
        $name = htmlspecialchars($_POST['name']);
        $status = htmlspecialchars($_POST['status']);
        $nursing_area = htmlspecialchars($_POST['nursing_area']);

        $stmt = $conn->prepare("INSERT INTO cleaning_table (Room, Name, Status, Nursing_area, Modified_Date, PC_Name) VALUES (?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("sssss", $room, $name, $status, $nursing_area, gethostname());
        $stmt->execute();
        $stmt->close();

        // Log the insertion
        $log_action = 'INSERT';
        logAction($conn, $log_action, $room, $name, $status, $nursing_area,'delete');
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
        $stmt_fetch = $conn->prepare("SELECT Name, Nursing_area, Room FROM cleaning_table WHERE id = ?");
        $stmt_fetch->bind_param("i", $id);
        $stmt_fetch->execute();
        $stmt_fetch->bind_result($name, $nursing_area, $room);
        $stmt_fetch->fetch();
        $stmt_fetch->close();

        $log_action = 'UPDATE';
        logAction($conn, $log_action, $room, $name, $status, $nursing_area);
    }

    // Delete
    if (isset($_POST['delete_id'])) {
        $delete_id = $_POST['delete_id'];

        // Fetch Room and Name for logging
        $stmt_fetch = $conn->prepare("SELECT Room, Name FROM cleaning_table WHERE id = ?");
        $stmt_fetch->bind_param("i", $delete_id);
        $stmt_fetch->execute();
        $stmt_fetch->bind_result($room, $name);
        $stmt_fetch->fetch();
        $stmt_fetch->close();

        // Delete the entry
        $stmt = $conn->prepare("DELETE FROM cleaning_table WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();

        // Log the deletion
        $log_action = 'DELETE';
        logAction($conn, $log_action, $room, $name, '', '');
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Function to log actions
function logAction($conn, $action, $room, $name, $status, $nursing_area) {
    $stmt_log = $conn->prepare("INSERT INTO cleaning_table_log (action, room, name, status, nursing_area, modified_date, pc_name, operation) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)");
    $pc_name = gethostname();
    $operation = strtolower($action); // e.g. insert, update, delete
    $stmt_log->bind_param("sssssss", $action, $room, $name, $status, $nursing_area, $pc_name, $operation);
    $stmt_log->execute();
    $stmt_log->close();
}

// Handle filtering and searching
$filterStatus = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
$searchRoom = isset($_GET['search_room']) ? $_GET['search_room'] : '';
$dateFilter = isset($_GET['date_filter']) ? $_GET['date_filter'] : date('Y-m-d'); // Default to today's date
$filterNursingArea = isset($_GET['filter_nursing_area']) ? $_GET['filter_nursing_area'] : ''; // New filter for nursing area

// Prepare SQL query with filtering
$sql_query = "SELECT id, Room, Name, Status, Nursing_area, Modified_Date, PC_Name FROM cleaning_table WHERE 1=1";

if ($filterStatus) {
    $sql_query .= " AND Status = ?";
}
if ($searchRoom) {
    $sql_query .= " AND Room LIKE ?";
}
if ($filterNursingArea) {
    $sql_query .= " AND Nursing_area = ?";
}
$sql_query .= " AND DATE(Modified_Date) = ?";
$sql_query .= " ORDER BY Room";

// Bind parameters if necessary
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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@700&family=Montserrat:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/style.css">

</head>
<body
>
<div class="container home">
    <center><h2 style="font-family: 'Roboto', sans-serif;"><u>ROOM CLEANING STATUS</u></h2></center>
    <br>

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
                <label for="date_filter">Date</label>
                <input type="date" name="date_filter" class="form-control" id="date_filter" value="<?php echo htmlspecialchars($dateFilter); ?>">
            </div>
            <div class="form-group col-md-4">
                <button type="submit" class="btn btn-primary" id="filter">Filter</button>
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
            <th><button class="btn btn-primary" data-toggle="modal" data-target="#addPatientModal">
                <img src="images/patient.png" alt="Add Patient" class="add-icon" 
                style = "height :30px;" />
            </button></th>
        </tr>
        </thead>
        <tbody>
            <?php 
            $today = date('Y-m-d'); // Get today's date
            while ($row = $resultset->fetch_assoc()): 
                $modifiedDate = substr($row['Modified_Date'], 0, 10); // Extract date part of Modified_Date
            ?>
            <tr>
                <td><?php echo htmlspecialchars($row['Room']); ?></td>
                <td><?php echo htmlspecialchars($row['Name']); ?></td>
                <td><?php echo htmlspecialchars($row['Status']); ?></td>
                <td><?php echo htmlspecialchars($row['Nursing_area']); ?></td>
                <td><?php echo htmlspecialchars($row['Modified_Date']); ?></td>
                <td><?php echo htmlspecialchars($row['PC_Name']); ?></td>
                <td>
                    <!-- Disable the button if the Modified_Date is not today -->
                    <button 
                        class="btn btn-warning" 
                        onclick="openEditStatusModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['Name']); ?>', '<?php echo htmlspecialchars($row['Room']); ?>', '<?php echo htmlspecialchars($row['Status']); ?>')"
                        <?php if ($modifiedDate != $today) echo 'disabled'; ?> 
                    >
                        <img src="images/edit.png" alt="Add Patient" class="add-icon" 
                        style = "height :30px;" />
                    </button>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                        <button type="button" class="btn btn-danger"  style="Display:none" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                            <img src="images/delete.png" alt="Add Patient" class="add-icon" 
                                style = "height :30px;" />
                        </button>
                        </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal for adding a patient -->
<div class="modal fade" id="addPatientModal" tabindex="-1" role="dialog" aria-labelledby="addPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPatientModalLabel">Add Patient</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="room">Room Number</label>
                        <input type="text" name="room" class="form-control" id="room" required>
                    </div>
                    <div class="form-group">
                        <label for="name">Patient Name</label>
                        <input type="text" name="name" class="form-control" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" class="form-control" id="status" required>
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
                        <select name="nursing_area" class="form-control" id="nursing_area" required>
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
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Patient</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal for editing the status -->
<div class="modal fade" id="editStatusModal" tabindex="-1" role="dialog" aria-labelledby="editStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStatusModalLabel">Edit Status</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editStatusForm" method="POST" action="">
                    <input type="hidden" id="editId" name="edit_id">
                    <p>Patient Name: <span id="patientName"></span></p>
                    <p>Room Number: <span id="roomNumber"></span></p>
                    <div class="form-group">
                        <label for="editStatus">Status</label>
                        <select name="edit_status" class="form-control" id="editStatus" required>
                            <option value="Discharge Recommended">Discharge Recommended</option>
                            <option value="Sent for Billing">Sent for Billing</option>
                            <option value="Final Billing">Final Billing</option>
                            <option value="Discharged">Discharged</option>
                            <option value="Patient Vacated">Patient Vacated</option>
                            <option value="Cleaning in Progress">Cleaning in Progress</option>
                            <option value="Room Cleaned">Room Cleaned</option>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openEditStatusModal(id, name, room, status) {
        $('#editId').val(id);
        $('#patientName').text(name);
        $('#roomNumber').text(room);
        $('#editStatus').val(status);
        $('#editStatusModal').modal('show');
    }
    
    $(document).ready(function () {
        $('#editStatusForm').on('submit', function (e) {
            e.preventDefault();
            const form = $(this);
            const data = form.serialize();
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to change the patient's status.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, update it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(form.attr('action'), data, function () {
                        location.reload(); // Reload the page after updating
                    });
                }
            });
        });
    });
</script>
<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit the delete form
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_id';
            input.value = id;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
