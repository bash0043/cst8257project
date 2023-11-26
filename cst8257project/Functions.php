<?php
include_once 'EntityClassLib.php';

function getPDO()
{
    $dbConnection = parse_ini_file("Project.ini");
    extract($dbConnection);
    return new PDO($dsn, $scriptUser, $scriptPassword);  
}


function getUserByIdAndPassword($UserId, $Password)
{
    $pdo = getPDO();
    
    $sql = "SELECT UserId FROM user WHERE UserId = '$UserId' AND Password = '$Password'";
    

        
    $resultSet = $pdo->query($sql);
    if ($resultSet)
    {
        $row = $resultSet->fetch(PDO::FETCH_ASSOC);
        if ($row)
        {
            return new UserId($row['UserId']);
        }
        else
        {
            return null;
        }
    }
    else
    {
        throw new Exception("Query failed! SQL statement: $sql");
    }
}

function validateFormData($enteredStudentID, $enteredPassword)
{
    $errors = [];

    if (empty($enteredStudentID)) {
        $errors['StudentId'] = 'Student ID is required.';
    }

    if (empty($enteredPassword)) {
        $errors['Password'] = 'Password is required.';
    }

    return $errors;
}

function handleLoginFormSubmission($connection)
{
    $enteredStudentID = $_POST['UserId'];
    $enteredPassword = $_POST['Password'];

    $errors = validateFormData($enteredStudentID, $enteredPassword);

    if (empty($errors)) {
        $query = "SELECT * FROM user WHERE UserId = :UserId";
        $stmt = $connection->prepare($query);
        $stmt->bindParam(':UserId', $enteredStudentID);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($enteredPassword, $user['Password'])) {
                $_SESSION['UserId'] = $enteredStudentID;

                if (isset($_SESSION['RequestedPage'])) {
                    $redirectPage = $_SESSION['RequestedPage'];
                    unset($_SESSION['RequestedPage']); 
                    header("Location: $redirectPage");
                    exit();
                } else {
                    header('Location: MyAlbums.php'); 
                    exit();
                }
            } else {
                $errors['login'] = 'Invalid Student ID or Password.';
            }
        } else {
            $errors['login'] = 'Invalid Student ID or Password.';
        }
    }

    return $errors;
}



function addNewUser($userId, $name, $phone, $password)
{
   $pdo = getPDO();
     
    $sql = "INSERT INTO user (UserId, Name, Phone, Password) VALUES( '$userId', '$name', '$phone', '$password')";
    $pdoStmt = $pdo->query($sql);
}

function createNewUserValidation($UserId, $Name, $Phone, $Password, $confirmPassword, $connection)
{
    $errors = [];

    if (empty($UserId)) {
        $errors['UserId'] = 'User ID is required.';
    }

    if (empty($Name)) {
        $errors['Name'] = 'Name is required.';
    }

    if (!preg_match('/^\d{3}-\d{3}-\d{4}$/', $Phone)) {
        $errors['Phone'] = 'Phone Number must be in the format of nnn-nnn-nnnn.';
    }

    if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,}$/', $Password)) {
        $errors['Password'] = 'Password must be at least 6 characters long and contain at least one uppercase letter, one lowercase letter, and one digit.';
    }

    $query = "SELECT * FROM user WHERE UserId = :UserId";
    $stmt = $connection->prepare($query);
    $stmt->bindParam(':UserId', $UserId);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $errors['UserId'] = 'User ID already exists.';
    }

    if ($Password !== $confirmPassword) {
        $errors['confirmPassword'] = 'Passwords do not match.';
    }

    return $errors;
}


function handleNewUserFormSubmission($connection)
{
    $UserId = $_POST['UserId'];
    $Name = $_POST['Name'];
    $Phone = $_POST['Phone'];
    $Password = $_POST['Password'];
    $confirmPassword = $_POST['confirmPassword'];

    $errors = createNewUserValidation($UserId, $Name, $Phone, $Password, $confirmPassword, $connection);
 
    if (empty($errors)) {
        // Hash the password before storing in the database
        $hashedPassword = password_hash($Password, PASSWORD_DEFAULT);

        $insertQuery = "INSERT INTO user (UserId, Name, Phone, Password) VALUES (:UserId, :Name, :Phone, :Password)";
        $stmt = $connection->prepare($insertQuery);
        $stmt->bindParam(':UserId', $UserId);
        $stmt->bindParam(':Name', $Name);
        $stmt->bindParam(':Phone', $Phone);
        $stmt->bindParam(':Password', $hashedPassword); // Store hashed password
        $stmt->execute();

        // Check if the inserted user can log in
        $query = "SELECT * FROM user WHERE UserId = :UserId AND Password = :Password";
        $stmt = $connection->prepare($query);
        $stmt->bindParam(':UserId', $UserId);
        $stmt->bindParam(':Password', $hashedPassword); // Use hashed password for comparison
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            session_start();
            $_SESSION['UserId'] = $UserId;
            header('Location: MyAlbums.php');
            die();
        }
    }

    return $errors;
}