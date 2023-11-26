<?php include("./common/header.php"); 

    if (!isset($_SESSION['UserId'])) {
        
        $_SESSION['RequestedPage'] = $_SERVER['REQUEST_URI'];
        
        header('Location: Login.php');
        
        exit();
    }

?>


<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <div class="container">
            <p></p>
            <h1>Upload Pictures</h1>
        </div>
    </body>
</html>

<?php include('./common/footer.php'); ?>