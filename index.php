<?php
require_once "env.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: Origin, X-Requested-With, Content, Accept, Content-Type, Authorization");
header("Access-Control-Allow-Headers: GET, POST, PUT, DELETE, PATCH, OPTIONS");
header("location: ". _UI_URL);
?>