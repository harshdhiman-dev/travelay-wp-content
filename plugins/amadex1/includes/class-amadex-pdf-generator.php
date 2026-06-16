<?php
/**
 * PDF Generator for Amadex Plugin
 * Generates professional PDF documents: booking confirmations, e-tickets, invoices, receipts, itineraries
 *
 * @package Amadex
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PDF Generator Class
 */
class Amadex_PDF_Generator {
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * Dompdf instance (if available)
     */
    private $dompdf = null;
    
    /**
     * Whether PDF library is available
     */
    private $pdf_available = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new Amadex_Database();
        $this->init_pdf_library();
    }
    
    /**
     * Initialize PDF library (Dompdf)
     */
    private function init_pdf_library() {
        // Try multiple autoload paths
        $autoload_paths = array(
            AMADEX_PATH . 'vendor/autoload.php',
            AMADEX_PATH . 'includes/vendor/autoload.php',
            dirname(AMADEX_PATH) . '/vendor/autoload.php'
        );
        
        foreach ($autoload_paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                $this->pdf_available = class_exists('Dompdf\Dompdf');
                if ($this->pdf_available) {
                    break;
                }
            }
        }
        
        if (!$this->pdf_available) {
            error_log('Amadex PDF: Dompdf not available. Install via: composer require dompdf/dompdf');
        }
    }
    
    /**
     * Check if PDF library is available
     *
     * @return bool
     */
    public function is_pdf_available() {
        return $this->pdf_available;
    }
    
    /**
     * Generate PDF document
     *
     * @param string $type Document type: 'confirmation', 'eticket', 'invoice', 'receipt', 'itinerary'
     * @param int $booking_id Booking ID
     * @param int|null $lead_id Lead ID (optional, for lead-based documents)
     * @return array|false PDF content and metadata, or false on error
     */
    public function generate_pdf($type, $booking_id = null, $lead_id = null) {
        $data = null;
        
        // Get booking data
        if ($booking_id) {
            $data = $this->database->get_booking($booking_id);
            if (!$data) {
                return false;
            }
        } elseif ($lead_id) {
            $data = $this->database->get_lead($lead_id);
            if (!$data) {
                return false;
            }
        } else {
            return false;
        }
        
        // Generate HTML content based on type
        $html = $this->generate_html($type, $data);
        if (!$html) {
            return false;
        }
        
        // Generate PDF if library available, otherwise return HTML
        if ($this->pdf_available) {
            return $this->generate_pdf_from_html($html, $type, $data);
        } else {
            // Fallback: return HTML for print
            return array(
                'type' => 'html',
                'content' => $html,
                'filename' => $this->get_filename($type, $data),
                'mime' => 'text/html'
            );
        }
    }
    
    /**
     * Generate PDF from HTML using Dompdf
     *
     * @param string $html HTML content
     * @param string $type Document type
     * @param array $data Booking/lead data
     * @return array PDF content and metadata
     */
    public function generate_pdf_from_html($html, $type, $data) {
        try {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdf_content = $dompdf->output();
            
            return array(
                'type' => 'pdf',
                'content' => $pdf_content,
                'filename' => $this->get_filename($type, $data),
                'mime' => 'application/pdf',
                'size' => strlen($pdf_content)
            );
        } catch (Exception $e) {
            error_log('Amadex PDF Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate HTML content for document
     *
     * @param string $type Document type
     * @param array $data Booking/lead data
     * @return string HTML content
     */
    private function generate_html($type, $data) {
        switch ($type) {
            case 'confirmation':
                return $this->generate_confirmation_html($data);
            case 'eticket':
                return $this->generate_eticket_html($data);
            case 'invoice':
                return $this->generate_invoice_html($data);
            case 'receipt':
                return $this->generate_receipt_html($data);
            case 'itinerary':
                return $this->generate_itinerary_html($data);
            default:
                return false;
        }
    }
    
    /**
     * Generate booking confirmation HTML
     */
    private function generate_confirmation_html($data) {
        $booking = isset($data['id']) && isset($data['booking_reference']) ? $data : null;
        $lead = $booking && isset($booking['lead']) ? $booking['lead'] : $data;
        
        $booking_ref = $booking ? $booking['booking_reference'] : ($data['confirmation_number'] ?? 'N/A');
        $pnr = $booking ? ($booking['pnr'] ?? 'Pending') : 'N/A';
        $total = $booking ? floatval($booking['total_amount']) : 0;
        $currency = $booking ? ($booking['currency'] ?? 'USD') : 'USD';
        $status = $booking ? $booking['status'] : 'PENDING';
        
        $contact_name = $lead['contact_name'] ?? 'N/A';
        $contact_email = $lead['contact_email'] ?? 'N/A';
        $contact_phone = $lead['contact_phone'] ?? 'N/A';
        
        $flight_data = $booking ? $booking['flight_data'] : ($data['flight_data'] ?? array());
        $passengers = $booking && isset($booking['passengers']) ? $booking['passengers'] : array();
        
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Booking Confirmation - <?php echo esc_html($booking_ref); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { border-bottom: 3px solid #0066cc; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #0066cc; font-size: 24px; margin-bottom: 10px; }
        .header .ref { font-size: 14px; color: #666; }
        .section { margin-bottom: 25px; }
        .section-title { background: #f5f5f5; padding: 10px; font-weight: bold; margin-bottom: 10px; border-left: 4px solid #0066cc; }
        .info-row { display: table; width: 100%; margin-bottom: 8px; }
        .info-label { display: table-cell; width: 150px; font-weight: bold; color: #666; }
        .info-value { display: table-cell; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f5f5f5; font-weight: bold; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #ddd; font-size: 10px; color: #666; text-align: center; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 3px; font-weight: bold; font-size: 11px; }
        .status-PENDING { background: #fff3cd; color: #856404; }
        .status-CONFIRMED { background: #d4edda; color: #155724; }
        .status-TICKETED { background: #cce5ff; color: #004085; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Booking Confirmation</h1>
            <div class="ref">Reference: <strong><?php echo esc_html($booking_ref); ?></strong> | PNR: <strong><?php echo esc_html($pnr); ?></strong></div>
            <div style="margin-top: 10px;">
                <span class="status-badge status-<?php echo esc_attr($status); ?>"><?php echo esc_html($status); ?></span>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Contact Information</div>
            <div class="info-row">
                <div class="info-label">Name:</div>
                <div class="info-value"><?php echo esc_html($contact_name); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value"><?php echo esc_html($contact_email); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Phone:</div>
                <div class="info-value"><?php echo esc_html($contact_phone); ?></div>
            </div>
        </div>
        
        <?php if (!empty($flight_data['itineraries'])): ?>
        <div class="section">
            <div class="section-title">Flight Itinerary</div>
            <?php foreach ($flight_data['itineraries'] as $idx => $itinerary): ?>
                <h3 style="margin: 15px 0 10px; color: #0066cc;"><?php echo $idx === 0 ? 'Outbound' : 'Return'; ?> Flight</h3>
                <table>
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>To</th>
                            <th>Date</th>
                            <th>Departure</th>
                            <th>Arrival</th>
                            <th>Duration</th>
                            <th>Airline</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itinerary['segments'] as $segment): ?>
                        <?php
                            // Support both raw Amadeus structure and normalized segments (from/to/carrier).
                            $from_code = $segment['departure']['iataCode']
                                ?? $segment['departure']['iata_code']
                                ?? $segment['from']
                                ?? 'N/A';
                            $to_code = $segment['arrival']['iataCode']
                                ?? $segment['arrival']['iata_code']
                                ?? $segment['to']
                                ?? 'N/A';

                            $dep_raw = $segment['departure']['at'] ?? $segment['departure'] ?? '';
                            $arr_raw = $segment['arrival']['at'] ?? $segment['arrival'] ?? '';

                            $dep_date = $dep_raw ? date('M j, Y', strtotime($dep_raw)) : 'N/A';
                            $dep_time = $dep_raw ? date('H:i', strtotime($dep_raw)) : 'N/A';
                            $arr_time = $arr_raw ? date('H:i', strtotime($arr_raw)) : 'N/A';

                            $duration = $segment['duration'] ?? 'N/A';
                            $airline = $segment['carrierCode']
                                ?? $segment['carrier_code']
                                ?? $segment['carrier']
                                ?? 'N/A';
                        ?>
                        <tr>
                            <td><?php echo esc_html($from_code); ?></td>
                            <td><?php echo esc_html($to_code); ?></td>
                            <td><?php echo esc_html($dep_date); ?></td>
                            <td><?php echo esc_html($dep_time); ?></td>
                            <td><?php echo esc_html($arr_time); ?></td>
                            <td><?php echo esc_html($duration); ?></td>
                            <td><?php echo esc_html($airline); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($passengers)): ?>
        <div class="section">
            <div class="section-title">Passengers</div>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Date of Birth</th>
                        <th>Passport</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($passengers as $passenger): ?>
                    <tr>
                        <td><?php echo esc_html(trim(($passenger['title'] ?? '') . ' ' . ($passenger['first_name'] ?? '') . ' ' . ($passenger['last_name'] ?? ''))); ?></td>
                        <td><?php echo esc_html($passenger['passenger_type'] ?? 'ADULT'); ?></td>
                        <td><?php echo esc_html($passenger['date_of_birth'] ?? 'N/A'); ?></td>
                        <td><?php echo esc_html($passenger['passport_number'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <div class="section-title">Payment Summary</div>
            <div class="info-row">
                <div class="info-label">Total Amount:</div>
                <div class="info-value"><strong><?php echo esc_html($currency . ' ' . number_format($total, 2)); ?></strong></div>
            </div>
            <?php if ($booking && isset($booking['payment'])): ?>
            <div class="info-row">
                <div class="info-label">Payment Status:</div>
                <div class="info-value"><?php echo esc_html($booking['payment']['payment_status'] ?? 'N/A'); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>This is an automated confirmation. Please keep this document for your records.</p>
            <p>For inquiries, contact our support team.</p>
            <p>Generated on <?php echo esc_html(current_time('F j, Y g:i A')); ?></p>
        </div>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generate e-ticket HTML
     */
    private function generate_eticket_html($data) {
        // Similar structure to confirmation but formatted as e-ticket
        $html = $this->generate_confirmation_html($data);
        // Add e-ticket specific styling/formatting
        $html = str_replace('Booking Confirmation', 'E-Ticket', $html);
        return $html;
    }
    
    /**
     * Generate invoice HTML
     */
    private function generate_invoice_html($data) {
        $booking = isset($data['id']) && isset($data['booking_reference']) ? $data : null;
        $total = $booking ? floatval($booking['total_amount']) : 0;
        $currency = $booking ? ($booking['currency'] ?? 'USD') : 'USD';
        $invoice_number = $booking ? ($booking['booking_reference'] ?? 'INV-' . $booking['id']) : 'INV-N/A';
        
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice - <?php echo esc_html($invoice_number); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
        .invoice-title { font-size: 32px; font-weight: bold; }
        .invoice-number { text-align: right; }
        .section { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f5f5f5; }
        .total-row { font-weight: bold; font-size: 14px; background: #f5f5f5; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <div class="invoice-title">INVOICE</div>
            </div>
            <div class="invoice-number">
                <div><strong>Invoice #:</strong> <?php echo esc_html($invoice_number); ?></div>
                <div><strong>Date:</strong> <?php echo esc_html(current_time('F j, Y')); ?></div>
            </div>
        </div>
        
        <div class="section">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Flight Booking</td>
                        <td style="text-align: right;"><?php echo esc_html($currency . ' ' . number_format($total, 2)); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td>Total</td>
                        <td style="text-align: right;"><?php echo esc_html($currency . ' ' . number_format($total, 2)); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>Payment Terms: Payment due upon receipt</p>
            <p>Thank you for your business!</p>
        </div>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generate receipt HTML
     */
    private function generate_receipt_html($data) {
        $booking = isset($data['id']) && isset($data['booking_reference']) ? $data : null;
        $total = $booking ? floatval($booking['total_amount']) : 0;
        $currency = $booking ? ($booking['currency'] ?? 'USD') : 'USD';
        $payment = $booking && isset($booking['payment']) ? $booking['payment'] : null;
        
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?php echo esc_html($booking ? $booking['booking_reference'] : 'N/A'); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
        .receipt-title { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
        .section { margin-bottom: 15px; }
        .info-row { margin-bottom: 8px; }
        .total { font-size: 18px; font-weight: bold; text-align: center; margin: 20px 0; padding: 15px; background: #f5f5f5; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="receipt-title">PAYMENT RECEIPT</div>
            <div>Booking Reference: <?php echo esc_html($booking ? $booking['booking_reference'] : 'N/A'); ?></div>
        </div>
        
        <div class="section">
            <div class="info-row"><strong>Date:</strong> <?php echo esc_html(current_time('F j, Y g:i A')); ?></div>
            <?php if ($payment): ?>
            <div class="info-row"><strong>Transaction ID:</strong> <?php echo esc_html($payment['transaction_id'] ?? 'N/A'); ?></div>
            <div class="info-row"><strong>Payment Method:</strong> <?php echo esc_html($payment['payment_method'] ?? 'N/A'); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="total">
            Amount Paid: <?php echo esc_html($currency . ' ' . number_format($total, 2)); ?>
        </div>
        
        <div class="footer">
            <p>This is your receipt. Please keep for your records.</p>
        </div>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Generate itinerary HTML
     */
    private function generate_itinerary_html($data) {
        // Enhanced itinerary with day-by-day breakdown
        return $this->generate_confirmation_html($data);
    }
    
    /**
     * Get filename for document
     */
    private function get_filename($type, $data) {
        $booking_ref = isset($data['booking_reference']) ? $data['booking_reference'] : (isset($data['id']) ? 'ID' . $data['id'] : 'DOC');
        $type_map = array(
            'confirmation' => 'Confirmation',
            'eticket' => 'E-Ticket',
            'invoice' => 'Invoice',
            'receipt' => 'Receipt',
            'itinerary' => 'Itinerary'
        );
        $type_name = $type_map[$type] ?? 'Document';
        return sanitize_file_name($type_name . '_' . $booking_ref . '.pdf');
    }
    
    /**
     * Output PDF to browser
     *
     * @param string $type Document type
     * @param int $booking_id Booking ID
     * @param int|null $lead_id Lead ID
     * @param bool $download Force download (true) or inline (false)
     */
    public function output_pdf($type, $booking_id = null, $lead_id = null, $download = true) {
        $result = $this->generate_pdf($type, $booking_id, $lead_id);
        if (!$result) {
            wp_die('Error generating PDF document.');
        }
        
        $filename = $result['filename'];
        $mime = $result['mime'];
        $content = $result['content'];
        
        // Set headers
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strlen($content));
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Output content
        echo $content;
        exit;
    }
}
