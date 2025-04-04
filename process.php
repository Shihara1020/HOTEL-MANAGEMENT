<?php
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_customer':
                $stmt = $pdo->prepare("
                    INSERT INTO customers (name, phone, id_number, room_number, check_in, check_out)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['phone'],
                    $_POST['id_number'],
                    $_POST['room_number'],
                    $_POST['check_in'],
                    $_POST['check_out']
                ]);
                break;
                
            case 'delete_customer':
                $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                break;
                
            case 'add_payment':
                $stmt = $pdo->prepare("
                    INSERT INTO payments (customer_id, amount, payment_date)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['customer_id'],
                    $_POST['amount'],
                    $_POST['payment_date']
                ]);
                break;
                
            case 'add_booking':
                $stmt = $pdo->prepare("
                    INSERT INTO bookings (customer_id, room_number, booking_date, status)
                    VALUES (?, ?, ?, 'confirmed')
                ");
                $stmt->execute([
                    $_POST['customer_id'],
                    $_POST['room_number'],
                    $_POST['booking_date']
                ]);
                break;
        }
        
        // Redirect back to prevent form resubmission
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>