<?php
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_customer':
                $stmt = $pdo->prepare("
                    INSERT INTO customers (name, phone, id_number, room_number, room_type, check_in, check_out)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['phone'],
                    $_POST['id_number'],
                    $_POST['room_number'],
                    $_POST['room_type'],
                    $_POST['check_in'],
                    $_POST['check_out']
                ]);
                
                // Update room status to occupied
                $stmt = $pdo->prepare("
                    UPDATE rooms SET status = 'occupied' WHERE room_number = ?
                ");
                $stmt->execute([$_POST['room_number']]);
                break;
                
            case 'delete_customer':
                // Start transaction
                $pdo->beginTransaction();
                
                try {
                    // First delete related bookings
                    $stmt = $pdo->prepare("DELETE FROM bookings WHERE customer_id = ?");
                    $stmt->execute([$_POST['id']]);
                    
                    // Then delete related payments
                    $stmt = $pdo->prepare("DELETE FROM payments WHERE customer_id = ?");
                    $stmt->execute([$_POST['id']]);
                    
                    // Get room number before deleting customer
                    $stmt = $pdo->prepare("SELECT room_number FROM customers WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $customer = $stmt->fetch();
                    
                    // Delete customer
                    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    
                    // Update room status to available
                    if ($customer) {
                        $stmt = $pdo->prepare("
                            UPDATE rooms SET status = 'available' WHERE room_number = ?
                        ");
                        $stmt->execute([$customer['room_number']]);
                    }
                    
                    // Commit transaction
                    $pdo->commit();
                } catch (Exception $e) {
                    // Rollback if any error occurs
                    $pdo->rollBack();
                    die("Error: " . $e->getMessage());
                }
                break;
                
            case 'add_payment':
                // Get room type from customer
                $stmt = $pdo->prepare("SELECT room_type FROM customers WHERE id = ?");
                $stmt->execute([$_POST['customer_id']]);
                $customer = $stmt->fetch();
                
                $stmt = $pdo->prepare("
                    INSERT INTO payments (customer_id, room_type, amount, payment_date, payment_method)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['customer_id'],
                    $customer['room_type'],
                    $_POST['amount'],
                    $_POST['payment_date'],
                    $_POST['payment_method']
                ]);
                break;
                
            case 'add_booking':
                // Get room type from room number
                $stmt = $pdo->prepare("SELECT room_type FROM rooms WHERE room_number = ?");
                $stmt->execute([$_POST['room_number']]);
                $room = $stmt->fetch();
                
                $stmt = $pdo->prepare("
                    INSERT INTO bookings (customer_id, room_number, room_type, booking_date, status)
                    VALUES (?, ?, ?, ?, 'confirmed')
                ");
                $stmt->execute([
                    $_POST['customer_id'],
                    $_POST['room_number'],
                    $room['room_type'],
                    $_POST['booking_date']
                ]);
                break;
                
            case 'add_room':
                $stmt = $pdo->prepare("
                    INSERT INTO rooms (room_number, room_type, price_per_night, max_occupancy, amenities, description)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['room_number'],
                    $_POST['room_type'],
                    $_POST['price_per_night'],
                    $_POST['max_occupancy'],
                    $_POST['amenities'] ?? '',
                    $_POST['description'] ?? ''
                ]);
                break;

            case 'delete_room':
                $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                break;

            case 'update_room':
                $stmt = $pdo->prepare("
                    UPDATE rooms SET 
                        room_number = ?,
                        room_type = ?,
                        price_per_night = ?,
                        max_occupancy = ?,
                        amenities = ?,
                        description = ?,
                        status = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['room_number'],
                    $_POST['room_type'],
                    $_POST['price_per_night'],
                    $_POST['max_occupancy'],
                    $_POST['amenities'] ?? '',
                    $_POST['description'] ?? '',
                    $_POST['status'],
                    $_POST['id']
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