<?php
if (!isset($dbConnection)) {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_password = $_POST['db_password'];
    $db_name = $_POST['db_name'];
    $dbConnection = new mysqli($db_host, $db_user, $db_password, $db_name);
    $dbConnection->set_charset("utf8");
}