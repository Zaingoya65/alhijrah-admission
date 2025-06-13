<?php
session_start();
include '../includes/config.php';
include '../includes/session_auth.php';

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

$user_id = (int)$_SESSION['id'];
$b_form = htmlspecialchars($_SESSION['b_form']);

// Get student information with prepared statement
$stmt = $conn->prepare("SELECT sp.full_name, sp.guardian_name, sp.applied_campus 
                       FROM student_profiles sp 
                       WHERE sp.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: personal_info.php");
    exit();
}

$student = $result->fetch_assoc();
$full_name = htmlspecialchars($student['full_name']);
$guardian_name = htmlspecialchars($student['guardian_name']);
$applied_campus = htmlspecialchars($student['applied_campus'] ?? '');
$date = date('d-m-Y');
$valid_till = date('d-m-Y', strtotime('+10 days'));
$b_form_last4 = substr($b_form, -4);
$challan_number = "ALH-{$b_form_last4}-" . str_pad($user_id, 5, '0', STR_PAD_LEFT);

if (isset($_POST['generate_challan'])) {
    // Update payment status
    // $update_stmt = $conn->prepare("UPDATE student_profiles WHERE user_id = ?");
    // $update_stmt->bind_param(" ", $user_id);
    // $update_stmt->execute();
    // $update_stmt->close();

    require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');

    // Create new PDF document
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Al-Hijrah Trust');
    $pdf->SetAuthor('Al-Hijrah Trust');
    $pdf->SetTitle('Admission Fee Challan');
    $pdf->SetSubject('Payment Challan');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 10);
    
    // Add a page
    $pdf->AddPage();

    // Logo path
    $logo = '../assets/images/trust-logo.png';
    // $watermark = '../assets/images/trust-logo.png';

    // Bank details
    $bank_details = <<<EOD
Bank: United Bank Limited (UBL)
Branch: Main Branch, Ziarat
Account Title: Al-Hijrah Trust
Account No: 1234-5678901234
Branch Code: 1234
EOD;

    // Define copies
    $copies = [
        'BANK COPY' => [
            'color' => [230, 240, 255],  // Light blue
            'note' => 'Note: Submit this copy at the bank along with payment.'
        ],
        'AL-HIJRAH COPY' => [
            'color' => [255, 240, 230],  // Light orange
            'note' => 'Note: Bank will return this copy with stamp after payment.'
        ],
        'STUDENT COPY' => [
            'color' => [230, 255, 240],  // Light green
            'note' => 'Note: Keep this copy for your records. Submit receipt to office.'
        ]
    ];

    // Section dimensions
    $section_width = 90;
    $section_height = 180;
    $x_positions = [10, 105, 200];
    $y_start = 15;

    foreach ($copies as $copy_name => $copy_data) {
        $x = $x_positions[array_search($copy_name, array_keys($copies))];
        $y = $y_start;
        
        // Set copy background color
        $pdf->SetFillColor($copy_data['color'][0], $copy_data['color'][1], $copy_data['color'][2]);
        $pdf->Rect($x, $y, $section_width, $section_height, 'F');
        $pdf->Rect($x, $y, $section_width, $section_height);
        
        // Current Y position tracker
        $current_y = $y + 5;

        // Logo and Header
        if (file_exists($logo)) {
            $pdf->Image($logo, $x + 5, $current_y, 15, 15, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
        }
        
        $pdf->SetXY($x + 25, $current_y);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell($section_width - 25, 7, 'AL-HIJRAH TRUST', 0, 2, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell($section_width - 25, 5, strtoupper($applied_campus) . ' CAMPUS', 0, 2, 'L');
        $pdf->Cell($section_width - 25, 5, 'ADMISSION FEE CHALLAN', 0, 2, 'L');
        
        $current_y = $pdf->GetY() + 3;

        // Copy header
        $pdf->SetXY($x, $current_y);
        $pdf->SetFont('helvetica', 'B', 10);
        // $pdf->SetFillColor(255, 255, 255);
        $pdf->Cell($section_width, 7, $copy_name, 0, 2, 'C', true);
        $current_y = $pdf->GetY() + 2;

        // Challan info
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY($x + 5, $current_y);
        $pdf->Cell(40, 5, 'Challan No:', 0, 0, 'L');
        $pdf->Cell(40, 5, $challan_number, 0, 1, 'L');
        
        $pdf->SetX($x + 5);
        $pdf->Cell(40, 5, 'Issue Date:', 0, 0, 'L');
        $pdf->Cell(40, 5, $date, 0, 1, 'L');
        
        $pdf->SetX($x + 5);
        $pdf->Cell(40, 5, 'Valid Till:', 0, 0, 'L');
        $pdf->Cell(40, 5, $valid_till, 0, 1, 'L');
        
        $current_y = $pdf->GetY() + 3;

        // Bank details
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY($x + 5, $current_y);
        $pdf->Cell($section_width - 10, 5, 'BANK DETAILS:', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY($x + 5, $current_y + 5);
        $pdf->MultiCell($section_width - 10, 5, $bank_details, 0, 'L');
        $current_y = $pdf->GetY() + 3;

        // Payment table
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY($x + 5, $current_y);
        $pdf->Cell(60, 7, 'Particular', 1, 0, 'C', true);
        $pdf->Cell(20, 7, 'Amount', 1, 1, 'C', true);
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetX($x + 5);
        $pdf->Cell(60, 7, 'Admission Processing Fee', 1, 0, 'L');
        $pdf->Cell(20, 7, '150', 1, 1, 'C');
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetX($x + 5);
        $pdf->Cell(60, 7, 'Total Amount Payable', 1, 0, 'L', true);
        $pdf->Cell(20, 7, '150', 1, 1, 'C', true);
        
        $current_y = $pdf->GetY() + 2;
        
        // Amount in words
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetXY($x + 5, $current_y);
        $pdf->Cell($section_width - 10, 5, 'Amount in Words: One Hundred Fifty Rupees Only', 0, 1, 'L');
        $current_y = $pdf->GetY() + 5;

        // Student details
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY($x + 5, $current_y);
        $pdf->Cell($section_width - 10, 5, 'STUDENT DETAILS:', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY($x + 5, $current_y + 5);
        $pdf->Cell($section_width - 10, 5, 'Name: ' . $full_name, 0, 1, 'L');
        $pdf->SetX($x + 5);
        $pdf->Cell($section_width - 10, 5, 'Father/Guardian: ' . $guardian_name, 0, 1, 'L');
        $pdf->SetX($x + 5);
        $pdf->Cell($section_width - 10, 5, 'B-Form No: ' . $b_form, 0, 1, 'L');
        $pdf->SetX($x + 5);
        $pdf->Cell($section_width - 10, 5, 'Campus: ' . $applied_campus, 0, 1, 'L');
        
        $current_y = $pdf->GetY() + 10;

        // Signatures
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetXY($x + 5, $current_y);
        $pdf->Cell(40, 12, '_________________________', 0, 0, 'C');
        $pdf->Cell(35, 12, '___________________', 0, 1, 'C');
        
        $pdf->SetX($x + 5);
        $pdf->Cell(40, 5, 'Bank Officer Stamp & Signature', 0, 0, 'C');
        $pdf->Cell(35, 5, 'Parent/Guardian Signature', 0, 1, 'C');
        
        $current_y = $pdf->GetY() + 3;

        // Copy note
        $pdf->SetFont('helvetica', 'I', 7);
        $pdf->SetXY($x + 5, $current_y);
        $pdf->MultiCell($section_width - 10, 4, $copy_data['note'], 0, 'L');
        
        // Add watermark if available
        // if (file_exists($watermark)) {
        //     $pdf->Image($watermark, $x + 15, $y + 50, 60, 60, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
        // }
    }

    // Output PDF
    $filename = 'AlHijrah_Challan_' . $challan_number . '.pdf';
    $pdf->Output($filename, 'D');
    exit();
}
?>

<?php include '../includes/stud_header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row g-4">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <!-- Card Header -->
                <div class="card-header bg-primary bg-opacity-10 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">Payment Challan</h4>
                            <p class="mb-0 text-muted">Generate and download your admission fee challan</p>
                        </div>
                        <span class="badge bg-primary">
                            <i class="fas fa-file-invoice-dollar me-1"></i> Fee Payment
                        </span>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- Payment Information -->
                    <div class="alert alert-primary border-0 rounded-4 mb-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-info-circle fs-4"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="alert-heading">Payment Instructions</h5>
                                <p class="mb-2">Please generate and download the challan form below. Take it to any UBL branch and pay the admission processing fee of <strong>150 PKR</strong>.</p>
                                <hr>
                                <p class="mb-0"><i class="fas fa-exclamation-circle me-1"></i> After payment, please submit the bank receipt to the school office and keep your student copy safe.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Challan Preview -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>Challan Preview</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="p-3 border rounded-3 h-100" style="background-color: #e6f0ff;">
                                        <h6 class="text-center mb-3">BANK COPY</h6>
                                        <div class="text-center mb-3">
                                            <i class="fas fa-university fa-3x text-primary"></i>
                                        </div>
                                        <p class="small text-center">Submit this copy at the bank along with payment</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded-3 h-100" style="background-color: #fff2e6;">
                                        <h6 class="text-center mb-3">AL-HIJRAH COPY</h6>
                                        <div class="text-center mb-3">
                                            <i class="fas fa-school fa-3x text-warning"></i>
                                        </div>
                                        <p class="small text-center">Bank will return this copy with stamp after payment</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded-3 h-100" style="background-color: #e6ffe6;">
                                        <h6 class="text-center mb-3">STUDENT COPY</h6>
                                        <div class="text-center mb-3">
                                            <i class="fas fa-user-graduate fa-3x text-success"></i>
                                        </div>
                                        <p class="small text-center">Keep this copy for your records</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fee Details -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Fee Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Particulars</th>
                                            <th class="text-end">Amount (PKR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Admission Processing Fee</td>
                                            <td class="text-end">150.00</td>
                                        </tr>
                                        <tr class="table-active">
                                            <th>Total Payable</th>
                                            <th class="text-end">150.00</th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3 small text-muted">
                                <i class="fas fa-info-circle me-1"></i> Amount in words: One Hundred Fifty Rupees Only
                            </div>
                        </div>
                    </div>

                    <!-- Generate Button -->
                    <form method="POST" class="text-center mt-4">
                        <button type="submit" name="generate_challan" class="btn btn-primary px-4 py-2">
                            <i class="fas fa-file-download me-2"></i> Generate & Download Challan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .rounded-4 {
        border-radius: 1rem !important;
    }
    
    .card {
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
</style>

