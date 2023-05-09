<?php

$host = 'localhost';
$dbname = 'test_db';
$username = 'root';
$password = '';
$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

function saveEvent($event_name, $user_status) {
    global $pdo;

    $user_ip = $_SERVER['REMOTE_ADDR'];
    $timestamp = time();

    $stmt = $pdo->prepare("INSERT INTO events (event_name, user_status, user_ip, timestamp) VALUES (:event_name, :user_status, :user_ip, :timestamp)");
    $stmt->execute([
        'event_name' => $event_name,
        'user_status' => $user_status,
        'user_ip' => $user_ip,
        'timestamp' => $timestamp
    ]);

    return $pdo->lastInsertId();
}

function getStatistics($event_name, $start_date, $end_date, $aggregation_type) {
    global $pdo;

    $query = "SELECT ";
    switch ($aggregation_type) {
        case 'event_count':
            $query .= "event_name, COUNT(*) as count FROM events ";
            break;
        case 'user_count':
            $query .= "user_ip, COUNT(DISTINCT user_ip) as count FROM events ";
            break;
        case 'user_status_count':
            $query .= "user_status, COUNT(*) as count FROM events ";
            break;
        default:
            return ['error' => 'Invalid aggregation type'];
    }
    $query .= "WHERE event_name = :event_name AND timestamp >= :start_date AND timestamp <= :end_date GROUP BY ";
    switch ($aggregation_type) {
        case 'event_count':
            $query .= "event_name";
            break;
        case 'user_count':
            $query .= "user_ip";
            break;
        case 'user_status_count':
            $query .= "user_status";
            break;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'event_name' => $event_name,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return json_encode($results);
}

