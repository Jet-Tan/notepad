<?php
session_start();

require_once 'config.php';

$request_uri = trim($_SERVER['REQUEST_URI'], '/');
$parts = explode('/', $request_uri);

$identifier = end($parts);
$identifier = mysqli_real_escape_string($conn, $identifier);

$stmt = mysqli_prepare($conn, "SELECT passwords FROM notes WHERE identifier = ?");
mysqli_stmt_bind_param($stmt, "s", $identifier);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if ($row && !empty($row['passwords'])) {
    if (!isset($_COOKIE['authenticated']) || $_COOKIE['authenticated'] !== $identifier) {
        $_SESSION['identifier'] = $identifier;
        header("Location: https://dev.canbds.com/notepad/login.php/$identifier");
        exit();
    }
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notepad</title>
    <link rel="shortcut icon" type="image/x-icon" href="logo.png" />
    <link rel="stylesheet" type="text/css" href="styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css">
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <style>
        body {
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 95%;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        h3 {
            color: #333;
            font-weight: 900;
            text-align: center;
        }

        .new-note,
        .share-url {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .new-note:hover,
        .share-url:hover {
            background-color: #0056b3;
        }

        .text-main {
            position: relative;
            margin-top: 20px;
        }

        textarea {
            width: 100%;
            padding: 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 15px;
            font-family: Arial, Helvetica, sans-serif;
            resize: none;
            height: 500px;
        }

        textarea:focus {
            outline: none;
            border-color: #007bff;
        }

        .change-url,
        .add-password {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #ffc107;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            outline: none;
        }

        .change-url:hover,
        .add-password:hover {
            background-color: #e0a800;
        }

        .status-icon {
            position: absolute;
            top: 10px;
            right: 30px;
        }

        .success-icon {
            color: #28a745;
            height: 10px;
            width: 10px;
        }

        .failure-icon {
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
                margin: 20px auto;
                height: auto;
            }

            .new-note,
            .share-url {
                margin-bottom: 10px;
            }

            .text-main {
                margin-top: 10px;
            }
        }

        .popover {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            border: 1px solid #ccc;
            padding: 10px;
            z-index: 1;
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            function generateRandomIdentifier() {
                return Math.random().toString(36).substring(2, 10);
            }

            function updateIdentifier(currentIdentifier, newIdentifier) {
                fetch('connect.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'change_url',
                            identifier: currentIdentifier,
                            newIdentifier: newIdentifier
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Server Response:', data);
                        if (data.success) {
                            var updatedIdentifier = newIdentifier || currentIdentifier;
                            document.getElementById("edit-url").textContent = window.location.origin + '/notepad/' + updatedIdentifier;
                            history.pushState({}, '', window.location.origin + '/notepad/' + updatedIdentifier);
                            currentIdentifier = updatedIdentifier;
                            window.location.reload();
                            showStatusIcon(true);
                        } else {
                            console.error('Error:', data.message);
                            if (data.message === "New identifier already exists.") {
                                alert('The new identifier already exists. Please choose a different one.');
                            }
                            showStatusIcon(false);
                        }
                    })
                    .catch((error) => {
                        console.error('Error:', error);
                        showStatusIcon(false);
                    });
            }

            function showStatusIcon(success) {
                var iconElement = document.getElementById('status-icon');
                if (success) {
                    iconElement.innerHTML = '<i class="fas fa-check-circle success-icon"></i>';
                } else {
                    iconElement.innerHTML = '<i class="fas fa-times-circle failure-icon" hidden></i>';
                }
            }

            function loadContent(identifier) {
                fetch('connect.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'load',
                            identifier: identifier
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Load Content Response:', data);
                        if (data.success) {
                            document.getElementById('contents').value = data.content;
                        } else {
                            console.error('Error:', data.message);
                        }
                    })
                    .catch((error) => {
                        console.error('Error:', error);
                    });
            }

            var initialIdentifier = generateRandomIdentifier();
            var currentIdentifier = window.location.pathname.split('/').pop();
            if (!currentIdentifier || currentIdentifier.length === 0) {
                currentIdentifier = initialIdentifier;
                window.history.replaceState({}, '', window.location.origin + '/notepad/' + initialIdentifier);
            }
            document.getElementById("edit-url").textContent = window.location.origin + '/notepad/' + currentIdentifier;

            loadContent(currentIdentifier);

            var textarea = document.getElementById('contents');
            textarea.addEventListener('blur', function() {
                var content = textarea.value.trim();
                fetch('connect.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'update',
                            identifier: currentIdentifier,
                            content: content
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Server Response:', data);
                        if (data.success) {
                            showStatusIcon(true);
                        } else {
                            console.error('Error:', data.message);
                            showStatusIcon(false);
                        }
                    })
                    .catch((error) => {
                        console.error('Error:', error);
                        showStatusIcon(false);
                    });
            });

            document.getElementById('update-identifier-form').addEventListener('submit', function(event) {
                event.preventDefault();
                var newIdentifier = document.getElementById('new-identifier-input').value.trim();
                if (newIdentifier && newIdentifier !== currentIdentifier) {
                    updateIdentifier(currentIdentifier, newIdentifier);
                } else {
                    console.error('Invalid input or same identifier.');
                }
            });

            document.querySelector('.change-url').addEventListener('click', function() {
                var popover = document.getElementById('popover-content');
                popover.style.display = (popover.style.display === 'block' ? 'none' : 'block');
            });

            document.querySelector('.add-password').addEventListener('click', function() {
                if (this.textContent === 'Add password') {
                    var newPassword = prompt('Enter password:');
                    if (newPassword !== null) {
                        fetch('connect.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    action: 'add_password',
                                    identifier: currentIdentifier,
                                    passwords: newPassword
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log('Add Password Response:', data);
                                if (data.success) {
                                    alert('Password added successfully!');
                                    checkPasswordStatus(currentIdentifier);
                                } else {
                                    console.error('Error:', data.message);
                                    alert('Failed to add password: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while adding password.');
                            });
                    }
                } else if (this.textContent === 'Remove password') {
                    var confirmation = confirm('Are you sure you want to remove the password?');
                    if (confirmation) {
                        fetch('connect.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    action: 'remove_password',
                                    identifier: currentIdentifier
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log('Remove Password Response:', data);
                                if (data.success) {
                                    alert('Password removed successfully!');
                                    checkPasswordStatus(currentIdentifier);
                                } else {
                                    console.error('Error:', data.message);
                                    alert('Failed to remove password: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while removing password.');
                            });
                    }
                }
            });

            function checkPasswordStatus(identifier) {
                fetch('connect.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'load',
                            identifier: identifier
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Load Password Status Response:', data);
                        if (data.success) {
                            if (data.passwords) {
                                document.querySelector('.add-password').textContent = 'Remove password';
                            } else {
                                document.querySelector('.add-password').textContent = 'Add password';
                            }
                        } else {
                            console.error('Error:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }

            checkPasswordStatus(currentIdentifier);

        });
    </script>
    <script>
        $(document).ready(function() {
            var editUrl = $('#edit-url').text();
            $('#share-url').on('click', function() {
                var tempInput = $('<input>');
                $('body').append(tempInput);
                tempInput.val(editUrl).select();
                document.execCommand('copy');
                tempInput.remove();
                $('#share-url').attr('title', 'Copied!').tooltip('show');
                setTimeout(function() {
                    $('#share-url').attr('title', 'Click to copy').tooltip('hide');
                }, 1000);
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var popover = document.getElementById('popover-content');
            var button = document.getElementById('change-url-button');
            button.addEventListener('click', function() {
                popover.style.display = 'block';
                var buttonRect = button.getBoundingClientRect();
                popover.style.top = (buttonRect.bottom + window.scrollY) + 'px';
                popover.style.left = buttonRect.left + 'px';
            });
            document.addEventListener('click', function(event) {
                if (!popover.contains(event.target) && event.target !== button) {
                    popover.style.display = 'none';
                }
            });
        });
    </script>
</head>

<body>
    <div class="container">
        <h3>NOTEPAD ONLINE</h3>
        <div class="content">
            <div>
                <a href="https://dev.canbds.com/notepad/" target="_blank" class="new-note btn btn-primary">New note</a>
                <button class="share-url btn btn-primary ms-2" id="share-url" data-bs-toggle="tooltip" data-bs-placement="top" title="Copy">Copy url</button>
                <div hidden>
                    <strong>Edit url:</strong>
                    <span id="edit-url">https://example.com/edit/12345</span>
                </div>

            </div>

            <div class="text-main">
                <textarea id="contents" class="form-control" rows="15" spellcheck="false"></textarea>
                <span id="status-icon" class="status-icon"></span>
            </div>
            <div style="display: flex;">
                <form id="update-identifier-form">
                    <div class="popover" id="popover-content">
                        <input type="text" id="new-identifier-input" class="form-control" placeholder="Enter new identifier">
                        <button type="submit" class="btn btn-primary mt-2">Save</button>
                    </div>
                    <button type="button" id="change-url-button" class="change-url btn btn-warning">Change Url</button>
                </form>
                <?php if ($row && !empty($row['passwords'])) : ?>
                    <div class="add-password btn btn-warning ms-2">Remove password</div>
                <?php else : ?>
                    <div class="add-password btn btn-warning ms-2">Add password</div>
                <?php endif; ?>
            </div>
        </div>
</body>

</html>