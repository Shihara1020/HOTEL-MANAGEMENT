<?php
require 'db_connect.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'process.php';
}

// Fetch data for tables
$stmt = $pdo->query("SELECT * FROM customers ORDER BY created_at DESC");
$customers = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT p.*, c.name, c.room_number 
    FROM payments p 
    JOIN customers c ON p.customer_id = c.id 
    ORDER BY p.payment_date DESC
");
$payments = $stmt->fetchAll();

// Monthly income
$currentMonth = date('Y-m');
$stmt = $pdo->prepare("
    SELECT SUM(amount) as total 
    FROM payments 
    WHERE DATE_FORMAT(payment_date, '%Y-%m') = ?
");
$stmt->execute([$currentMonth]);
$monthlyIncome = $stmt->fetch()['total'] ?? 0;

// Upcoming bookings
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT b.*, c.name, c.phone 
    FROM bookings b 
    JOIN customers c ON b.customer_id = c.id 
    WHERE b.booking_date >= ? AND b.status = 'confirmed'
    ORDER BY b.booking_date ASC
");
$stmt->execute([$today]);
$upcomingBookings = $stmt->fetchAll();

// Available rooms
$stmt = $pdo->query("SELECT * FROM rooms WHERE status = 'available' ORDER BY room_number");
$availableRooms = $stmt->fetchAll();

// All rooms for management
$stmt = $pdo->query("SELECT * FROM rooms ORDER BY room_number");
$allRooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WD REST</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
            <h1>WD REST MANAGEMENT SYSTERM</h1>
            <nav>
                <ul>
                    <li><a href="#customers"><i class="fas fa-users"></i> Customers</a></li>
                    <li><a href="#rooms"><i class="fas fa-door-open"></i> Rooms</a></li>
                    <li><a href="#payments"><i class="fas fa-money-bill-wave"></i> Payments</a></li>
                    <li><a href="#bookings"><i class="fas fa-calendar-alt"></i> Bookings</a></li>
                    <li><a href="#reports"><i class="fas fa-chart-line"></i> Reports</a></li>
                </ul>
            </nav>
        </header>

        <main>
            <!-- Customer Management Section -->
            <section id="customers" class="card">
                <h2><i class="fas fa-users"></i> Customer Management</h2>
                <div class="form-container">
                    <form id="customerForm" method="POST">
                        <input type="hidden" name="action" value="add_customer">
                        <div class="form-group">
                            <label for="name">Full Name:</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number:</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="id_number">ID Number:</label>
                            <input type="text" id="id_number" name="id_number" required>
                        </div>
                        <div class="form-group">
                            <label for="room_number">Room Number:</label>
                            <select id="room_number" name="room_number" required onchange="updateRoomDetails()">
                                <option value="">Select Room</option>
                                <?php foreach ($availableRooms as $room): ?>
                                <option value="<?= $room['room_number'] ?>" 
                                        data-type="<?= $room['room_type'] ?>"
                                        data-price="<?= $room['price_per_night'] ?>">
                                    <?= $room['room_number'] ?> (<?= $room['room_type'] ?> - $<?= $room['price_per_night'] ?>/night)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="room_type">Room Type:</label>
                            <input type="text" id="room_type" name="room_type" readonly>
                        </div>
                        <div class="form-group">
                            <label for="room_price">Price Per Night:</label>
                            <input type="text" id="room_price" name="room_price" readonly>
                        </div>
                        <div class="form-group">
                            <label for="check_in">Check-in Date:</label>
                            <input type="text" id="check_in" name="check_in" class="datepicker" required>
                        </div>
                        <div class="form-group">
                            <label for="check_out">Check-out Date:</label>
                            <input type="text" id="check_out" name="check_out" class="datepicker" required>
                        </div>
                        <button type="submit" class="btn">Save Customer</button>
                    </form>
                </div>

                <div class="table-container">
                    <h3>Current Guests</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>ID Number</th>
                                <th>Room No.</th>
                                <th>Room Type</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?= htmlspecialchars($customer['name']) ?></td>
                                <td><?= htmlspecialchars($customer['phone']) ?></td>
                                <td><?= htmlspecialchars($customer['id_number']) ?></td>
                                <td><?= htmlspecialchars($customer['room_number']) ?></td>
                                <td><?= htmlspecialchars($customer['room_type']) ?></td>
                                <td><?= date('M j, Y', strtotime($customer['check_in'])) ?></td>
                                <td><?= date('M j, Y', strtotime($customer['check_out'])) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_customer">
                                        <input type="hidden" name="id" value="<?= $customer['id'] ?>">
                                        <button type="submit" class="btn btn-danger" 
                                onclick="return confirm('Are you sure you want to delete this customer and all their bookings/payments?')">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Room Management Section -->
            <section id="rooms" class="card">
                <h2><i class="fas fa-door-open"></i> Room Management</h2>
                
                <div class="form-container">
                    <form id="roomForm" method="POST">
                        <input type="hidden" name="action" value="add_room">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_room_number">Room Number:</label>
                                <input type="text" id="new_room_number" name="room_number" required>
                            </div>
                            <div class="form-group">
                                <label for="new_room_type">Room Type:</label>
                                <select id="new_room_type" name="room_type" required>
                                    <option value="">Select Type</option>
                                    <option value="Standard">Standard</option>
                                    <option value="Deluxe">Deluxe</option>
                                    <option value="Suite">Suite</option>
                                    <option value="Executive">Executive</option>
                                    <option value="Presidential">Presidential</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="price_per_night">Price Per Night:</label>
                                <input type="number" id="price_per_night" name="price_per_night" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label for="max_occupancy">Max Occupancy:</label>
                                <input type="number" id="max_occupancy" name="max_occupancy" min="1" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="amenities">Amenities:</label>
                            <textarea id="amenities" name="amenities" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea id="description" name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn">Add Room</button>
                    </form>
                </div>

                <div class="table-container">
                    <h3>Room Inventory</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Room No.</th>
                                <th>Type</th>
                                <th>Price/Night</th>
                                <th>Occupancy</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allRooms as $room): ?>
                            <tr>
                                <td><?= htmlspecialchars($room['room_number']) ?></td>
                                <td><?= htmlspecialchars($room['room_type']) ?></td>
                                <td>RS. <?= number_format($room['price_per_night'], 2) ?></td>
                                <td><?= $room['max_occupancy'] ?></td>
                                <td>
                                    <span class="status-badge <?= $room['status'] ?>">
                                        <?= ucfirst($room['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="editRoom(<?= $room['id'] ?>)" class="btn btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_room">
                                        <input type="hidden" name="id" value="<?= $room['id'] ?>">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Payment Section -->
            <section id="payments" class="card">
                <h2><i class="fas fa-money-bill-wave"></i> Payment Management</h2>
                <div class="form-container">
                    <form id="paymentForm" method="POST">
                        <input type="hidden" name="action" value="add_payment">
                        <div class="form-group">
                            <label for="customer_id">Customer:</label>
                            <select id="customer_id" name="customer_id" required>
                                <option value="">Select Customer</option>
                                <?php foreach ($customers as $customer): ?>
                                <option value="<?= $customer['id'] ?>">
                                    <?= htmlspecialchars($customer['name']) ?> (Room: <?= htmlspecialchars($customer['room_number']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="amount">Amount:</label>
                            <input type="number" id="amount" name="amount" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="payment_date">Payment Date:</label>
                            <input type="text" id="payment_date" name="payment_date" class="datepicker" required>
                        </div>
                        <div class="form-group">
                            <label for="payment_method">Payment Method:</label>
                            <select id="payment_method" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="debit_card">Debit Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <button type="submit" class="btn">Record Payment</button>
                    </form>
                </div>

                <div class="table-container">
                    <h3>Payment History</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Room No.</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= htmlspecialchars($payment['name']) ?></td>
                                <td><?= htmlspecialchars($payment['room_number']) ?></td>
                                <td>RS. <?= number_format($payment['amount'], 2) ?></td>
                                <td>
                                    <span class="payment-method">
                                        <?php 
                                        $method = $payment['payment_method'];
                                        $icon = '';
                                        switch($method) {
                                            case 'credit_card': $icon = '<i class="fas fa-credit-card"></i>'; break;
                                            case 'debit_card': $icon = '<i class="fas fa-credit-card"></i>'; break;
                                            case 'bank_transfer': $icon = '<i class="fas fa-university"></i>'; break;
                                            case 'cash': $icon = '<i class="fas fa-money-bill-wave"></i>'; break;
                                            default: $icon = '<i class="fas fa-question-circle"></i>';
                                        }
                                        echo $icon . ' ' . ucfirst(str_replace('_', ' ', $method));
                                        ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                                <td>
                                    <button onclick="printReceipt(<?= $payment['id'] ?>)" class="btn btn-receipt">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2"><strong>Total</strong></td>
                                <td><strong>RS. <?= number_format(array_sum(array_column($payments, 'amount')), 2) ?></strong></td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            <!-- Booking Section -->
            <section id="bookings" class="card">
                <h2><i class="fas fa-calendar-alt"></i> Room Booking</h2>
                <div class="booking-container">
                    <div class="calendar-container">
                        <div id="calendar"></div>
                    </div>
                    <div class="booking-form">
                        <form id="bookingForm" method="POST">
                            <input type="hidden" name="action" value="add_booking">
                            <input type="hidden" id="selected_date" name="booking_date">
                            <div class="form-group">
                                <label for="booking_customer_id">Customer:</label>
                                <select id="booking_customer_id" name="customer_id" required>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer['id'] ?>">
                                        <?= htmlspecialchars($customer['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="booking_room_number">Room Number:</label>
                                <select id="booking_room_number" name="room_number" required>
                                    <option value="">Select Room</option>
                                    <?php foreach ($availableRooms as $room): ?>
                                    <option value="<?= $room['room_number'] ?>" data-type="<?= $room['room_type'] ?>">
                                        <?= $room['room_number'] ?> (<?= $room['room_type'] ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" id="booking_room_type" name="room_type">
                            </div>
                            <button type="submit" class="btn">Confirm Booking</button>
                        </form>
                    </div>
                </div>

                <!-- Upcoming Bookings Table -->
<div class="table-container">
    <h3>Upcoming Bookings</h3>
    <table>
        <thead>
            <tr>
                <th>Customer</th>
                <th>Phone</th>
                <th>Room No.</th>
                <th>Room Type</th>
                <th>Booking Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($upcomingBookings as $booking): ?>
            <tr>
                <td><?= htmlspecialchars($booking['name']) ?></td>
                <td><?= htmlspecialchars($booking['phone']) ?></td>
                <td><?= htmlspecialchars($booking['room_number']) ?></td>
                <td><?= htmlspecialchars($booking['room_type']) ?></td>
                <td><?= date('M j, Y', strtotime($booking['booking_date'])) ?></td>
                <td>
                    <span class="status-badge <?= $booking['status'] ?>">
                        <?= ucfirst($booking['status']) ?>
                    </span>
                </td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="cancel_booking">
                        <input type="hidden" name="id" value="<?= $booking['id'] ?>">
                        <button type="submit" class="btn btn-warning" 
                                onclick="return confirm('Are you sure you want to cancel this booking?')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
            </section>


























            
            <!-- Reports Section -->
            <section id="reports" class="card">
                <h2><i class="fas fa-chart-line"></i> Reports</h2>
                <div class="report-container">
                    <div class="report-card">
                        <h3>Monthly Income</h3>
                        <div class="income-display">
                            <span class="amount">RS. <?= number_format($monthlyIncome, 2) ?></span>
                            <span class="month"><?= date('F Y') ?></span>
                        </div>
                    </div>
                    <div class="report-card">
                        <h3>Current Guests</h3>
                        <div class="guests-display">
                            <span class="count"><?= count($customers) ?></span>
                            <span class="label">Active Guests</span>
                        </div>
                    </div>
                    <div class="report-card">
                        <h3>Room Occupancy</h3>
                        <div class="occupancy-display">
                            <span class="rate">
                                <?php 
                                $totalRooms = count($allRooms);
                                $occupiedRooms = count(array_filter($allRooms, fn($room) => $room['status'] === 'occupied'));
                                $occupancyRate = $totalRooms > 0 ? ($occupiedRooms / $totalRooms) * 100 : 0;
                                echo number_format($occupancyRate, 1) . '%';
                                ?>
                            </span>
                            <span class="label">Occupancy Rate</span>
                        </div>
                    </div>
                </div>

                <div class="table-container">
                    <h3>Monthly Income History</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Total Income</th>
                                <th>Booking Count</th>
                                <th>Average Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("
                                SELECT 
                                    DATE_FORMAT(payment_date, '%Y-%m') as month, 
                                    SUM(amount) as total,
                                    COUNT(*) as count,
                                    AVG(amount) as average
                                FROM payments 
                                GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                                ORDER BY month DESC
                            ");
                            $monthlyHistory = $stmt->fetchAll();
                            foreach ($monthlyHistory as $history): ?>
                            <tr>
                                <td><?= date('F Y', strtotime($history['month'] . '-01')) ?></td>
                                <td>RS. <?= number_format($history['total'], 2) ?></td>
                                <td><?= $history['count'] ?></td>
                                <td>RS. <?= number_format($history['average'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; <?= date('Y') ?> Hotel Management System. All rights reserved.</p>
        </footer>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="script.js"></script>
</body>
</html>