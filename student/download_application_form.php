<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../includes/config.php';
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');

if (!isset($_GET['user_id'])) {
    die("Invalid request: User ID not provided.");
}

$user_id = intval($_GET['user_id']);

// First, check database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Use prepared statement to prevent SQL injection
$sql = "
    SELECT 
        sp.*, 
        ru.b_form, 
        ru.email,
        sp.profile_image
    FROM 
        student_profiles sp
    INNER JOIN 
        registered_users ru 
        ON sp.user_id = ru.id
    WHERE 
        sp.user_id = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL prepare error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("SQL execute error: " . $stmt->error);
}

$result = $stmt->get_result();
if (!$result) {
    die("SQL get_result error: " . $stmt->error);
}

if ($result->num_rows === 0) {
    die("No application found for user ID: $user_id");
}

$application = $result->fetch_assoc();
$stmt->close();

class AdmissionPDF extends TCPDF {
    private $primaryColor = array(0, 102, 204);
    private $secondaryColor = array(245, 245, 245);
    private $profileImageHeight = 0;
    
    public function Header() {
        // Don't use the default header
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages().' | Generated on ' . date('F j, Y, g:i a'), 0, 0, 'C');
    }
    
    public function sectionTitle($title) {
        $this->SetFillColorArray($this->primaryColor);
        $this->SetTextColor(255);
        $this->SetFont('helvetica', 'B', 11);
        $this->Cell(0, 8, '  ' . $title, 0, 1, 'L', true);
        $this->SetTextColor(0);
        $this->Ln(3);
    }
    
    public function formRow($label, $value, $labelWidth = 45) {
        $this->SetFillColorArray($this->secondaryColor);
        $this->SetTextColor(0);
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell($labelWidth, 7, $label, 0, 0, 'L', true);
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 7, $value ?: 'N/A', 0, 1, 'L');
        $this->Ln(1);
    }
    
    public function twoColumnRow($label1, $value1, $label2, $value2, $colWidth = 80) {
        $this->SetFillColorArray($this->secondaryColor);
        $this->SetTextColor(0);
        
        // First column
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(20, 7, $label1, 0, 0, 'L', true);
        $this->SetFont('helvetica', '', 9);
        $this->Cell($colWidth-20, 7, $value1 ?: 'N/A', 0, 0, 'L');
        
        // Second column
        $this->SetFont('helvetica', 'B', 9);
        $this->Cell(20, 7, $label2, 0, 0, 'L', true);
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 7, $value2 ?: 'N/A', 0, 1, 'L');
        
        $this->Ln(1);
    }
    
    public function addProfileImage($imagePath, $x, $y) {
        if (file_exists($imagePath)) {
            // Get image dimensions to maintain aspect ratio
            list($width, $height) = getimagesize($imagePath);
            $ratio = $height/$width;
            $newWidth = 30; // Fixed width
            $newHeight = $newWidth * $ratio;
            
            $this->profileImageHeight = $newHeight;
            $this->Image($imagePath, $x, $y, $newWidth, $newHeight, '', '', 'T', false, 300, '', false, false, 0, 'L', false, false);
            return true;
        }
        return false;
    }
    
    public function getProfileImageHeight() {
        return $this->profileImageHeight;
    }
}

// Create new PDF document
$pdf = new AdmissionPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Al-Hijrah Trust');
$pdf->SetAuthor('Al-Hijrah Trust');
$pdf->SetTitle('Admission Form - ' . $application['full_name']);
$pdf->SetMargins(15, 15, 15);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(true);
$pdf->SetAutoPageBreak(true, 25);
$pdf->AddPage();

// Header Section with Logo and Profile Image
$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetTextColor(0, 102, 204);

// Add Logo (left side)
$logo = '../assets/images/trust-logo.png';
if (file_exists($logo)) {
    $pdf->Image($logo, 15, 10, 30, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
}

// Institution Info (middle)
$pdf->SetXY(50, 10);
$pdf->Cell(0, 8, 'AL-HIJRAH TRUST', 0, 1, 'L');
$pdf->SetX(50);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 6, 'Institute of Education', 0, 1, 'L');
$pdf->SetX(50);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Admission Application Form', 0, 1, 'L');

// Profile Picture (right side) with proper height calculation
$profileImage = '../uploads/' . $user_id . '/' . $application['profile_image'];
$imageAdded = false;
$imageYPosition = 10; // Initial Y position for image

if (!empty($application['profile_image']) && file_exists($profileImage)) {
    $imageAdded = $pdf->addProfileImage($profileImage, 160, $imageYPosition);
}

// Adjust header line position based on image height
$headerLineY = $imageAdded ? max(45, 10 + $pdf->getProfileImageHeight() + 5) : 45;
$pdf->SetDrawColor(0, 102, 204);
$pdf->SetLineWidth(0.5);
$pdf->Line(15, $headerLineY, 195, $headerLineY);
$pdf->SetY($headerLineY + 10);

// Application Summary
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(0, 102, 204);
$pdf->Cell(0, 8, 'APPLICATION SUMMARY', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0);

$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(40, 8, 'Application ID:', 0, 0, 'L', true);
$pdf->Cell(50, 8, 'ALH-' . str_pad($application['user_id'], 5, '0', STR_PAD_LEFT), 0, 0, 'L');
$pdf->Cell(40, 8, 'Submission Date:', 0, 0, 'L', true);
$pdf->Cell(0, 8, date('F j, Y', strtotime($application['submitted_at'])), 0, 1, 'L');

$pdf->Cell(40, 8, 'Applicant Name:', 0, 0, 'L', true);
$pdf->Cell(50, 8, $application['full_name'], 0, 0, 'L');
$pdf->Cell(40, 8, 'Application Year:', 0, 0, 'L', true);
$pdf->Cell(0, 8, $application['application_year'], 0, 1, 'L');

$pdf->Ln(8);

// 1. Personal Information
$pdf->sectionTitle('1. PERSONAL INFORMATION');
$pdf->twoColumnRow('Full Name:', $application['full_name'], 'Father/Guardian:', '        ' . $application['guardian_name']);

$pdf->twoColumnRow('Date of Birth:', ' '.$application['date_of_birth'], 'B-Form No:', $application['b_form']);
$pdf->twoColumnRow('Phone:', $application['phone_number'], 'Email:', $application['email']);
$pdf->Ln(5);

// 2. Guardian Information
$pdf->sectionTitle('2. GUARDIAN INFORMATION');
$pdf->twoColumnRow('Name:', $application['guardian_name'], 'CNIC:', $application['guardian_cnic']);
$pdf->twoColumnRow('Occupation:', $application['guardian_occupation'], 'Phone:', $application['phone_number']);
$pdf->Ln(5);

// 3. Address Information
$pdf->sectionTitle('3. ADDRESS INFORMATION');
$pdf->formRow('Current Address:','        ' . $application['current_address'], 25);
$pdf->formRow('Permanent Address:','        ' . $application['permanent_address'], 25);
$pdf->twoColumnRow('City:', $application['city'], 'Postal Code:', $application['postal_code']);
$pdf->twoColumnRow('Domicile Province:', '           ' .$application['domicile_province'], 'Domicile District:','        ' . $application['domicile_district']);
$pdf->Ln(5);

// 4. Educational Background
$pdf->sectionTitle('4. EDUCATIONAL BACKGROUND');
$pdf->formRow('Last School Attended:','       ' . $application['last_school_name'], 30);
$pdf->formRow('School Address:', $application['last_school_address'], 30);
$pdf->twoColumnRow('Class Completed:','               ' . $application['last_school_class'], 'Result (%):', $application['last_school_result']);
$pdf->twoColumnRow('Medium:', $application['last_school_medium'], 'School Type:', $application['last_school_type']);
$pdf->Ln(5);

// 5. Application Details
$pdf->sectionTitle('5. APPLICATION DETAILS');
$pdf->twoColumnRow('Applied Campus:','        ' . $application['applied_campus'], 'Status:', $application['application_status']);
$pdf->Ln(5);

// 6. Emergency Contact
$pdf->sectionTitle('6. EMERGENCY CONTACT');
$pdf->twoColumnRow('Contact Person:','        ' . $application['emergency_contact'], 'Relationship:', $application['emergency_relation']);
$pdf->twoColumnRow('Phone:', $application['emergency_phone'], '', '');
$pdf->Ln(8);

// 7. Declaration
$pdf->sectionTitle('7. DECLARATION');
$pdf->SetFont('helvetica', '', 9);
$declaration = "I, " . $application['full_name'] . ", hereby declare that all the information provided in this application form is true, complete, and accurate to the best of my knowledge. I understand that any false or misleading information may result in the rejection of my application or termination of admission if discovered later.";
$pdf->MultiCell(0, 6, $declaration, 0, 'L');
$pdf->Ln(10);

// Signatures
$signatureY = $pdf->GetY();
$pdf->SetFont('helvetica', '', 9);

// Applicant Signature
$pdf->SetX(30);
$pdf->Cell(60, 6, '_________________________', 0, 0, 'C');
$pdf->SetX(105);
$pdf->Cell(60, 6, '_________________________', 0, 1, 'C');

$pdf->SetX(30);
$pdf->Cell(60, 5, 'Applicant Signature', 0, 0, 'C');
$pdf->SetX(105);
$pdf->Cell(60, 5, 'Admission Officer', 0, 1, 'C');

$pdf->SetX(30);
$pdf->Cell(60, 5, 'Date: ' . date('F j, Y'), 0, 0, 'C');
$pdf->SetX(105);
$pdf->Cell(60, 5, 'Date: ___________________', 0, 1, 'C');

// Official Stamp Area (if needed)
$pdf->SetY($signatureY + 20);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 5, 'For Official Use Only', 0, 1, 'C');
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', '', 9);

// Output the PDF
$pdf->Output('Admission_Form_' . preg_replace('/\s+/', '_', $application['full_name']) . '.pdf', 'D');
exit;