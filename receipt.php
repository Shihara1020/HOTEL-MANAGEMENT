<?php
require 'db_connect.php';

if (!isset($_GET['payment_id'])) {
    die('Payment ID not specified');
}

$paymentId = (int)$_GET['payment_id'];

// Fetch payment details
$stmt = $pdo->prepare("
    SELECT p.*, c.name, c.phone, c.id_number, c.room_number, c.room_type, c.check_in, c.check_out,
           p.amount, p.payment_date, p.payment_method
    FROM payments p
    JOIN customers c ON p.customer_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$paymentId]);
$payment = $stmt->fetch();

if (!$payment) {
    die('Payment not found');
}

// Calculate nights stayed
$nights = (strtotime($payment['check_out']) - strtotime($payment['check_in'])) / (60 * 60 * 24);

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Hotel Management System');
$pdf->SetAuthor('Hotel Staff');
$pdf->SetTitle('Payment Receipt #' . $paymentId);
$pdf->SetSubject('Payment Receipt');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Add a page
$pdf->AddPage();

// Hotel information
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Grand Hotel', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 6, '123 Luxury Street, Hotel District', 0, 1, 'C');
$pdf->Cell(0, 6, 'Phone: (123) 456-7890 | Email: info@grandhotel.com', 0, 1, 'C');
$pdf->Ln(10);

// Receipt title
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'PAYMENT RECEIPT', 0, 1, 'C');
$pdf->Ln(5);

// Receipt details
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(40, 6, 'Receipt Number:', 0, 0);
$pdf->Cell(0, 6, 'REC-' . str_pad($paymentId, 6, '0', STR_PAD_LEFT), 0, 1);
$pdf->Cell(40, 6, 'Date:', 0, 0);
$pdf->Cell(0, 6, date('F j, Y', strtotime($payment['payment_date'])), 0, 1);
$pdf->Ln(5);

// Customer information
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'Customer Information', 0, 1);
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(40, 6, 'Name:', 0, 0);
$pdf->Cell(0, 6, $payment['name'], 0, 1);
$pdf->Cell(40, 6, 'Phone:', 0, 0);
$pdf->Cell(0, 6, $payment['phone'], 0, 1);
$pdf->Cell(40, 6, 'ID Number:', 0, 0);
$pdf->Cell(0, 6, $payment['id_number'], 0, 1);
$pdf->Cell(40, 6, 'Room:', 0, 0);
$pdf->Cell(0, 6, $payment['room_number'] . ' (' . $payment['room_type'] . ')', 0, 1);
$pdf->Cell(40, 6, 'Stay Duration:', 0, 0);
$pdf->Cell(0, 6, date('M j, Y', strtotime($payment['check_in'])) . ' to ' . date('M j, Y', strtotime($payment['check_out'])), 0, 1);
$pdf->Ln(5);

// Payment details
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'Payment Details', 0, 1);
$pdf->SetFont('helvetica', '', 12);

$pdf->Cell(40, 6, 'Payment Method:', 0, 0);
$pdf->Cell(0, 6, ucfirst(str_replace('_', ' ', $payment['payment_method'])), 0, 1);

$pdf->Cell(40, 6, 'Nights Stayed:', 0, 0);
$pdf->Cell(0, 6, $nights, 0, 1);

$pdf->Cell(40, 6, 'Amount:', 0, 0);
$pdf->Cell(0, 6, '$' . number_format($payment['amount'], 2), 0, 1);
$pdf->Ln(10);

// Thank you message
$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(0, 6, 'Thank you for choosing Grand Hotel!', 0, 1, 'C');
$pdf->Cell(0, 6, 'We hope to see you again soon.', 0, 1, 'C');

// Output the PDF
$pdf->Output('receipt_' . $paymentId . '.pdf', 'I');
?>