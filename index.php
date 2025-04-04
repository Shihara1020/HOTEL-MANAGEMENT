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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Hotel Management System</h1>
            <nav>
                <ul>
                    <li><a href="#customers">Customers</a></li>
                    <li><a href="#payments">Payments</a></li>
                    <li><a href="#bookings">Bookings</a></li>
                    <li><a href="#reports">Reports</a></li>
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
                            <input type="text" id="room_number" name="room_number" required>
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
                                <td><?= date('M j, Y', strtotime($customer['check_in'])) ?></td>
                                <td><?= date('M j, Y', strtotime($customer['check_out'])) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_customer">
                                        <input type="hidden" name="id" value="<?= $customer['id'] ?>">
                                        <button type="submit" class="btn btn-danger">Remove</button>
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
                                <th>Date</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?= htmlspecialchars($payment['name']) ?></td>
                                <td><?= htmlspecialchars($payment['room_number']) ?></td>
                                <td>$<?= number_format($payment['amount'], 2) ?></td>
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
                                <td><strong>$<?= number_format(array_sum(array_column($payments, 'amount')), 2) ?></strong></td>
                                <td colspan="2"></td>
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
                                <input type="text" id="booking_room_number" name="room_number" required>
                            </div>
                            <button type="submit" class="btn">Confirm Booking</button>
                        </form>
                    </div>
                </div>

                <div class="table-container">
                    <h3>Upcoming Bookings</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Room No.</th>
                                <th>Booking Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingBookings as $booking): ?>
                            <tr>
                                <td><?= htmlspecialchars($booking['name']) ?></td>
                                <td><?= htmlspecialchars($booking['phone']) ?></td>
                                <td><?= htmlspecialchars($booking['room_number']) ?></td>
                                <td><?= date('M j, Y', strtotime($booking['booking_date'])) ?></td>
                                <td>
                                    <span class="status-badge <?= $booking['status'] ?>">
                                        <?= ucfirst($booking['status']) ?>
                                    </span>
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
                            <span class="amount">$<?= number_format($monthlyIncome, 2) ?></span>
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
                </div>

                <div class="table-container">
                    <h3>Monthly Income History</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Total Income</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("
                                SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, 
                                       SUM(amount) as total 
                                FROM payments 
                                GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                                ORDER BY month DESC
                            ");
                            $monthlyHistory = $stmt->fetchAll();
                            foreach ($monthlyHistory as $history): ?>
                            <tr>
                                <td><?= date('F Y', strtotime($history['month'] . '-01')) ?></td>
                                <td>$<?= number_format($history['total'], 2) ?></td>
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