<?php
	$conn = new mysqli('localhost', 'root', '', 'sadp');

	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}
	
?>