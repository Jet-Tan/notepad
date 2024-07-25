<?php
session_start();
include 'config.php';
header('Content-Type: application/json');
$postData = file_get_contents('php://input');
$data = json_decode($postData, true);

if (isset($data['action'])) {
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $date_time = date('Y-m-d H:i:s');
    $time = time();

    if ($data['action'] === 'load' && isset($data['identifier'])) {
        $identifier = mysqli_real_escape_string($conn, $data['identifier']);

        $sql = "SELECT content, created_at, time_create, passwords FROM notes WHERE identifier = '$identifier'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            echo json_encode(array("success" => true, "content" => html_entity_decode($row['content']), "created_at" => $row['created_at'], "time_create" => $row['time_create'], "passwords" => $row['passwords']));
        } else {
            echo json_encode(array("success" => false, "message" => "No content found for the given identifier."));
        }
    } elseif ($data['action'] === 'update' && isset($data['identifier'])) {
        $currentIdentifier = mysqli_real_escape_string($conn, $data['identifier']);
        $content = isset($data['content']) ? mysqli_real_escape_string($conn, $data['content']) : '';
        $newIdentifier = isset($data['newIdentifier']) ? mysqli_real_escape_string($conn, $data['newIdentifier']) : $currentIdentifier;

        if (empty($content)) {
            echo json_encode(array("success" => false, "message" => "Content cannot be empty."));
            exit;
        }

        if ($newIdentifier !== $currentIdentifier) {
            $check_new_identifier_sql = "SELECT * FROM notes WHERE identifier = '$newIdentifier'";
            $check_new_identifier_result = mysqli_query($conn, $check_new_identifier_sql);

            if (mysqli_num_rows($check_new_identifier_result) > 0) {
                echo json_encode(array("success" => false, "message" => "New identifier already exists."));
                exit;
            }
        }

        if ($currentIdentifier !== '') {
            $check_sql = "SELECT * FROM notes WHERE identifier = '$currentIdentifier'";
            $check_result = mysqli_query($conn, $check_sql);

            if (mysqli_num_rows($check_result) > 0) {
                $content = htmlentities($content);
                $update_sql = "UPDATE notes SET content = '$content', identifier = '$newIdentifier', created_at = '$date_time' WHERE identifier = '$currentIdentifier'";
                if (mysqli_query($conn, $update_sql)) {
                    echo json_encode(array("success" => true, "identifier" => $newIdentifier));
                } else {
                    echo json_encode(array("success" => false, "message" => "Failed to update content: " . mysqli_error($conn)));
                }
            } else {
                $insert_sql = "INSERT INTO notes (identifier, content, created_at, time_create) VALUES ('$newIdentifier', '$content', '$date_time', '$time')";
                if (mysqli_query($conn, $insert_sql)) {
                    echo json_encode(array("success" => true, "identifier" => $newIdentifier));
                } else {
                    echo json_encode(array("success" => false, "message" => "Failed to create new note: " . mysqli_error($conn)));
                }
            }
        } else {
            echo json_encode(array("success" => false, "message" => "Current identifier is empty. Cannot update."));
        }
    } elseif ($data['action'] === 'add_password' && isset($data['identifier']) && isset($data['passwords'])) {
        $identifier = mysqli_real_escape_string($conn, $data['identifier']);
        $passwords = mysqli_real_escape_string($conn, $data['passwords']);

        if (empty($passwords)) {
            echo json_encode(array("success" => false, "message" => "Password cannot be empty."));
            exit;
        }

        $hashed_password = md5($passwords); // Use password_hash() for better security

        $check_sql = "SELECT * FROM notes WHERE identifier = '$identifier'";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            $stmt = $conn->prepare("UPDATE notes SET passwords = ?, created_at = ? WHERE identifier = ?");
            $stmt->bind_param("sss", $hashed_password, $date_time, $identifier);
        } else {
            $stmt = $conn->prepare("INSERT INTO notes (identifier, passwords, created_at, time_create) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $identifier, $hashed_password, $date_time, $time);
        }

        if ($stmt->execute()) {
            echo json_encode(array("success" => true));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to add password: " . $stmt->error));
        }
        $stmt->close();
    } elseif ($data['action'] === 'remove_password' && isset($data['identifier'])) {
        $identifier = mysqli_real_escape_string($conn, $data['identifier']);

        $sql = "UPDATE notes SET passwords = NULL WHERE identifier = '$identifier'";
        if (mysqli_query($conn, $sql)) {
            echo json_encode(array("success" => true));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to remove password: " . mysqli_error($conn)));
        }
    } elseif ($data['action'] === 'change_url' && isset($data['identifier']) && isset($data['newIdentifier'])) {
        $currentIdentifier = mysqli_real_escape_string($conn, $data['identifier']);
        $newIdentifier = mysqli_real_escape_string($conn, $data['newIdentifier']);

        if ($newIdentifier === '') {
            echo json_encode(array("success" => false, "message" => "New identifier cannot be empty."));
            exit;
        }

        if ($newIdentifier !== $currentIdentifier) {
            $check_new_identifier_sql = "SELECT * FROM notes WHERE identifier = '$newIdentifier'";
            $check_new_identifier_result = mysqli_query($conn, $check_new_identifier_sql);

            if (mysqli_num_rows($check_new_identifier_result) > 0) {
                echo json_encode(array("success" => false, "message" => "New identifier already exists."));
                exit;
            }
        }

        $sql = "UPDATE notes SET identifier = '$newIdentifier' WHERE identifier = '$currentIdentifier'";
        if (mysqli_query($conn, $sql)) {
            echo json_encode(array("success" => true, "identifier" => $newIdentifier));
        } else {
            echo json_encode(array("success" => false, "message" => "Failed to change identifier: " . mysqli_error($conn)));
        }
    } else {
        echo json_encode(array("success" => false, "message" => "Invalid action or missing parameters."));
    }
} else {
    echo json_encode(array("success" => false, "message" => "Action not specified."));
}

mysqli_close($conn);
