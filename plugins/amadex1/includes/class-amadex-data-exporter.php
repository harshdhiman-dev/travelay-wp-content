<?php
/**
 * Data Exporter for Amadex Plugin
 * Exports leads and bookings to CSV, XLSX, and PDF formats
 *
 * @package Amadex
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Data Exporter Class
 */
class Amadex_Data_Exporter {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * PhpSpreadsheet available
     */
    private $spreadsheet_available = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new Amadex_Database();
        $this->check_spreadsheet_library();
    }
    
    /**
     * Check if PhpSpreadsheet is available
     */
    private function check_spreadsheet_library() {
        $autoload_paths = array(
            AMADEX_PATH . 'vendor/autoload.php',
            AMADEX_PATH . 'includes/vendor/autoload.php',
            dirname(AMADEX_PATH) . '/vendor/autoload.php'
        );
        
        foreach ($autoload_paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $this->spreadsheet_available = class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet');
                if ($this->spreadsheet_available) {
                    break;
                }
            }
        }
    }
    
    /**
     * Export leads
     *
     * @param string $format Format: 'csv', 'xlsx', 'pdf'
     * @param array $filters Filters to apply
     */
    public function export_leads($format = 'csv', $filters = array()) {
        // Get leads with filters
        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $leads = array();
            foreach ($filters['ids'] as $id) {
                $lead = $this->database->get_lead($id);
                if ($lead) {
                    $leads[] = $lead;
                }
            }
        } else {
            $filters['limit'] = 10000; // Large limit for export
            $leads = $this->database->get_leads($filters);
        }
        
        if (empty($leads)) {
            wp_die('No leads found to export.');
        }
        
        switch ($format) {
            case 'csv':
                $this->export_leads_csv($leads);
                break;
            case 'xlsx':
                if ($this->spreadsheet_available) {
                    $this->export_leads_xlsx($leads);
                } else {
                    $this->export_leads_csv($leads); // Fallback to CSV
                }
                break;
            case 'pdf':
                $this->export_leads_pdf($leads);
                break;
            default:
                $this->export_leads_csv($leads);
        }
    }
    
    /**
     * Export bookings
     *
     * @param string $format Format: 'csv', 'xlsx', 'pdf'
     * @param array $filters Filters to apply
     */
    public function export_bookings($format = 'csv', $filters = array()) {
        // Get bookings with filters
        if (isset($filters['ids']) && is_array($filters['ids'])) {
            $bookings = array();
            foreach ($filters['ids'] as $id) {
                $booking = $this->database->get_booking($id);
                if ($booking) {
                    $bookings[] = $booking;
                }
            }
        } else {
            $filters['limit'] = 10000;
            $bookings = $this->database->get_bookings($filters);
        }
        
        if (empty($bookings)) {
            wp_die('No bookings found to export.');
        }
        
        switch ($format) {
            case 'csv':
                $this->export_bookings_csv($bookings);
                break;
            case 'xlsx':
                if ($this->spreadsheet_available) {
                    $this->export_bookings_xlsx($bookings);
                } else {
                    $this->export_bookings_csv($bookings);
                }
                break;
            case 'pdf':
                $this->export_bookings_pdf($bookings);
                break;
            default:
                $this->export_bookings_csv($bookings);
        }
    }
    
    /**
     * Export leads to CSV
     */
    private function export_leads_csv($leads) {
        $filename = 'amadex_leads_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, array(
            'ID', 'Type', 'Status', 'Contact Name', 'Email', 'Phone', 'Source',
            'Flight Route', 'Airline', 'Amount', 'Currency', 'Environment',
            'Fraud Score', 'Risk Level', 'Created At', 'Updated At'
        ));
        
        // Data rows
        foreach ($leads as $lead) {
            $flight_data = is_string($lead['flight_data']) ? json_decode($lead['flight_data'], true) : $lead['flight_data'];
            $route = $this->extract_route($flight_data);
            $airline = $this->extract_airline($flight_data);
            
            fputcsv($output, array(
                $lead['id'],
                $lead['lead_type'] ?? '',
                $lead['status'] ?? '',
                $lead['contact_name'] ?? '',
                $lead['contact_email'] ?? '',
                $lead['contact_phone'] ?? '',
                $lead['source'] ?? '',
                $route,
                $airline,
                $lead['total_amount'] ?? '',
                $lead['currency'] ?? 'USD',
                $lead['environment'] ?? 'PRODUCTION',
                $lead['fraud_score'] ?? 0,
                $lead['fraud_risk_level'] ?? 'LOW',
                $lead['created_at'] ?? '',
                $lead['updated_at'] ?? ''
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export leads to XLSX
     */
    private function export_leads_xlsx($leads) {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Leads');
        
        // Headers
        $headers = array(
            'ID', 'Type', 'Status', 'Contact Name', 'Email', 'Phone', 'Source',
            'Flight Route', 'Airline', 'Amount', 'Currency', 'Environment',
            'Fraud Score', 'Risk Level', 'Created At', 'Updated At'
        );
        $sheet->fromArray($headers, null, 'A1');
        
        // Style headers
        $headerStyle = array(
            'font' => array('bold' => true),
            'fill' => array(
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'E0E0E0')
            )
        );
        $sheet->getStyle('A1:P1')->applyFromArray($headerStyle);
        
        // Data rows
        $row = 2;
        foreach ($leads as $lead) {
            $flight_data = is_string($lead['flight_data']) ? json_decode($lead['flight_data'], true) : $lead['flight_data'];
            $route = $this->extract_route($flight_data);
            $airline = $this->extract_airline($flight_data);
            
            $sheet->setCellValue('A' . $row, $lead['id']);
            $sheet->setCellValue('B' . $row, $lead['lead_type'] ?? '');
            $sheet->setCellValue('C' . $row, $lead['status'] ?? '');
            $sheet->setCellValue('D' . $row, $lead['contact_name'] ?? '');
            $sheet->setCellValue('E' . $row, $lead['contact_email'] ?? '');
            $sheet->setCellValue('F' . $row, $lead['contact_phone'] ?? '');
            $sheet->setCellValue('G' . $row, $lead['source'] ?? '');
            $sheet->setCellValue('H' . $row, $route);
            $sheet->setCellValue('I' . $row, $airline);
            $sheet->setCellValue('J' . $row, $lead['total_amount'] ?? '');
            $sheet->setCellValue('K' . $row, $lead['currency'] ?? 'USD');
            $sheet->setCellValue('L' . $row, $lead['environment'] ?? 'PRODUCTION');
            $sheet->setCellValue('M' . $row, $lead['fraud_score'] ?? 0);
            $sheet->setCellValue('N' . $row, $lead['fraud_risk_level'] ?? 'LOW');
            $sheet->setCellValue('O' . $row, $lead['created_at'] ?? '');
            $sheet->setCellValue('P' . $row, $lead['updated_at'] ?? '');
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $filename = 'amadex_leads_' . date('Y-m-d_His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Export leads to PDF
     */
    private function export_leads_pdf($leads) {
        $pdf_generator = new Amadex_PDF_Generator();
        
        // Generate a summary PDF report
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Leads Export Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table th, table td { padding: 5px; border: 1px solid #ddd; text-align: left; }
        table th { background: #f5f5f5; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Leads Export Report</h1>
        <p>Generated: <?php echo esc_html(current_time('F j, Y g:i A')); ?> | Total: <?php echo count($leads); ?></p>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Amount</th><th>Created</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leads as $lead): ?>
            <tr>
                <td><?php echo esc_html($lead['id']); ?></td>
                <td><?php echo esc_html($lead['contact_name'] ?? ''); ?></td>
                <td><?php echo esc_html($lead['contact_email'] ?? ''); ?></td>
                <td><?php echo esc_html($lead['status'] ?? ''); ?></td>
                <td><?php echo esc_html(($lead['currency'] ?? 'USD') . ' ' . number_format($lead['total_amount'] ?? 0, 2)); ?></td>
                <td><?php echo esc_html($lead['created_at'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
        <?php
        $html = ob_get_clean();
        
        if ($pdf_generator->is_pdf_available()) {
            $result = $pdf_generator->generate_pdf_from_html($html, 'report', array('id' => 0));
            if ($result) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="amadex_leads_' . date('Y-m-d_His') . '.pdf"');
                echo $result['content'];
                exit;
            }
        }
        
        // Fallback to HTML
        header('Content-Type: text/html');
        echo $html;
        exit;
    }
    
    /**
     * Export bookings to CSV
     */
    private function export_bookings_csv($bookings) {
        $filename = 'amadex_bookings_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, array(
            'ID', 'Reference', 'PNR', 'Status', 'Contact Name', 'Email', 'Phone',
            'Route', 'Passengers', 'Amount', 'Currency', 'Environment',
            'Fraud Score', 'Created At'
        ));
        
        foreach ($bookings as $booking) {
            $flight_data = is_string($booking['flight_data']) ? json_decode($booking['flight_data'], true) : $booking['flight_data'];
            $route = $this->extract_route($flight_data);
            $lead = isset($booking['lead']) ? $booking['lead'] : null;
            
            fputcsv($output, array(
                $booking['id'],
                $booking['booking_reference'] ?? '',
                $booking['pnr'] ?? '',
                $booking['status'] ?? '',
                $lead ? ($lead['contact_name'] ?? '') : '',
                $lead ? ($lead['contact_email'] ?? '') : '',
                $lead ? ($lead['contact_phone'] ?? '') : '',
                $route,
                $booking['passenger_count'] ?? 0,
                $booking['total_amount'] ?? 0,
                $booking['currency'] ?? 'USD',
                $booking['environment'] ?? 'PRODUCTION',
                $booking['fraud_score'] ?? 0,
                $booking['created_at'] ?? ''
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export bookings to XLSX
     */
    private function export_bookings_xlsx($bookings) {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bookings');
        
        $headers = array(
            'ID', 'Reference', 'PNR', 'Status', 'Contact Name', 'Email', 'Phone',
            'Route', 'Passengers', 'Amount', 'Currency', 'Environment', 'Fraud Score', 'Created At'
        );
        $sheet->fromArray($headers, null, 'A1');
        
        $headerStyle = array(
            'font' => array('bold' => true),
            'fill' => array(
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => array('rgb' => 'E0E0E0')
            )
        );
        $sheet->getStyle('A1:N1')->applyFromArray($headerStyle);
        
        $row = 2;
        foreach ($bookings as $booking) {
            $flight_data = is_string($booking['flight_data']) ? json_decode($booking['flight_data'], true) : $booking['flight_data'];
            $route = $this->extract_route($flight_data);
            $lead = isset($booking['lead']) ? $booking['lead'] : null;
            
            $sheet->setCellValue('A' . $row, $booking['id']);
            $sheet->setCellValue('B' . $row, $booking['booking_reference'] ?? '');
            $sheet->setCellValue('C' . $row, $booking['pnr'] ?? '');
            $sheet->setCellValue('D' . $row, $booking['status'] ?? '');
            $sheet->setCellValue('E' . $row, $lead ? ($lead['contact_name'] ?? '') : '');
            $sheet->setCellValue('F' . $row, $lead ? ($lead['contact_email'] ?? '') : '');
            $sheet->setCellValue('G' . $row, $lead ? ($lead['contact_phone'] ?? '') : '');
            $sheet->setCellValue('H' . $row, $route);
            $sheet->setCellValue('I' . $row, $booking['passenger_count'] ?? 0);
            $sheet->setCellValue('J' . $row, $booking['total_amount'] ?? 0);
            $sheet->setCellValue('K' . $row, $booking['currency'] ?? 'USD');
            $sheet->setCellValue('L' . $row, $booking['environment'] ?? 'PRODUCTION');
            $sheet->setCellValue('M' . $row, $booking['fraud_score'] ?? 0);
            $sheet->setCellValue('N' . $row, $booking['created_at'] ?? '');
            $row++;
        }
        
        foreach (range('A', 'N') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $filename = 'amadex_bookings_' . date('Y-m-d_His') . '.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Export bookings to PDF
     */
    private function export_bookings_pdf($bookings) {
        $pdf_generator = new Amadex_PDF_Generator();
        
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Bookings Export Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table th, table td { padding: 5px; border: 1px solid #ddd; text-align: left; }
        table th { background: #f5f5f5; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Bookings Export Report</h1>
        <p>Generated: <?php echo esc_html(current_time('F j, Y g:i A')); ?> | Total: <?php echo count($bookings); ?></p>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th><th>Reference</th><th>Status</th><th>Amount</th><th>Created</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $booking): ?>
            <tr>
                <td><?php echo esc_html($booking['id']); ?></td>
                <td><?php echo esc_html($booking['booking_reference'] ?? ''); ?></td>
                <td><?php echo esc_html($booking['status'] ?? ''); ?></td>
                <td><?php echo esc_html(($booking['currency'] ?? 'USD') . ' ' . number_format($booking['total_amount'] ?? 0, 2)); ?></td>
                <td><?php echo esc_html($booking['created_at'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
        <?php
        $html = ob_get_clean();
        
        if ($pdf_generator->is_pdf_available()) {
            $result = $pdf_generator->generate_pdf_from_html($html, 'report', array('id' => 0));
            if ($result) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="amadex_bookings_' . date('Y-m-d_His') . '.pdf"');
                echo $result['content'];
                exit;
            }
        }
        
        header('Content-Type: text/html');
        echo $html;
        exit;
    }
    
    /**
     * Extract route from flight data
     */
    private function extract_route($flight_data) {
        if (empty($flight_data['itineraries'])) {
            return 'N/A';
        }
        
        $routes = array();
        foreach ($flight_data['itineraries'] as $itinerary) {
            if (!empty($itinerary['segments'])) {
                $first = $itinerary['segments'][0];
                $last = end($itinerary['segments']);
                $routes[] = ($first['departure']['iataCode'] ?? '') . ' → ' . ($last['arrival']['iataCode'] ?? '');
            }
        }
        
        return implode(' / ', $routes);
    }
    
    /**
     * Extract airline from flight data
     */
    private function extract_airline($flight_data) {
        if (!empty($flight_data['validating_airline_codes'])) {
            return implode(', ', (array)$flight_data['validating_airline_codes']);
        }
        if (!empty($flight_data['itineraries'][0]['segments'][0]['carrierCode'])) {
            return $flight_data['itineraries'][0]['segments'][0]['carrierCode'];
        }
        return 'N/A';
    }
}
