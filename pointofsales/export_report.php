<?php
session_start();
require_once 'config.php';
require_once 'classes/Customer.php';
require_once 'classes/Item.php';
require_once 'classes/Sales.php';
require_once 'classes/Transaction.php';
require_once 'includes/access_control.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

// Check if user has access to reports page (Manager level and above)
requireAccess(ACCESS_MANAGER);

// Check if user has access to reports (level 2+)
if ($_SESSION['level'] < 2) {
    header('Location: dashboard.php');
    exit;
}

// Initialize database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize classes
$customer = new Customer($pdo);
$item = new Item($pdo);
$sales = new Sales($pdo);
$transaction = new Transaction($pdo);

// Get report data
$report_type = $_GET['type'] ?? 'sales';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$report_data = [];

switch ($report_type) {
    case 'sales':
        $report_data = $sales->getSalesReport($start_date, $end_date);
        break;
    case 'customer':
        $report_data = $customer->getCustomerReport();
        break;
    case 'stock':
        $report_data = $item->getStockReport();
        break;
    case 'transaction':
        $report_data = $transaction->getTransactionReport($start_date, $end_date);
        break;
}

// Set filename based on report type and date
$filename = '';
switch ($report_type) {
    case 'sales':
        $filename = 'Laporan_Penjualan_' . $start_date . '_to_' . $end_date;
        break;
    case 'customer':
        $filename = 'Laporan_Customer_' . date('Y-m-d');
        break;
    case 'stock':
        $filename = 'Laporan_Stok_' . date('Y-m-d');
        break;
    case 'transaction':
        $filename = 'Laporan_Transaksi_' . $start_date . '_to_' . $end_date;
        break;
}

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
header('Cache-Control: max-age=0');

// Start output
echo "\xEF\xBB\xBF"; // UTF-8 BOM for proper encoding

// Report title
echo "<table border='1'>";
echo "<tr><td colspan='10' style='text-align:center; font-weight:bold; font-size:16px;'>";
echo "LAPORAN " . strtoupper(str_replace('_', ' ', $filename));
echo "</td></tr>";
echo "<tr><td colspan='10' style='text-align:center;'>";
echo "Tanggal Export: " . date('d/m/Y H:i:s');
echo "</td></tr>";
echo "<tr><td colspan='10'>&nbsp;</td></tr>";

// Headers based on report type
echo "<tr style='background-color:#f0f0f0; font-weight:bold;'>";
if ($report_type == 'sales') {
    echo "<td>No</td>";
    echo "<td>Sales No</td>";
    echo "<td>DO No</td>";
    echo "<td>Tanggal Sales</td>";
    echo "<td>Customer</td>";
    echo "<td>Total Penjualan</td>";
    echo "<td>Status</td>";
} elseif ($report_type == 'customer') {
    echo "<td>No</td>";
    echo "<td>Nama Customer</td>";
    echo "<td>Alamat</td>";
    echo "<td>Telepon</td>";
    echo "<td>Email</td>";
    echo "<td>Total Sales</td>";
} elseif ($report_type == 'stock') {
    echo "<td>No</td>";
    echo "<td>Nama Item</td>";
    echo "<td>UOM</td>";
    echo "<td>Harga Jual</td>";
    echo "<td>Total Terjual</td>";
    echo "<td>Stok Tersisa</td>";
} elseif ($report_type == 'transaction') {
    echo "<td>No</td>";
    echo "<td>Sales No</td>";
    echo "<td>Item</td>";
    echo "<td>Quantity</td>";
    echo "<td>Price</td>";
    echo "<td>Total</td>";
}
echo "</tr>";

// Data rows
$no = 1;
foreach ($report_data as $row) {
    echo "<tr>";
    echo "<td>" . $no . "</td>";
    
    if ($report_type == 'sales') {
        echo "<td>" . htmlspecialchars($row['do_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['do_number']) . "</td>";
        echo "<td>" . date('d/m/Y H:i:s', strtotime($row['tgl_sales'])) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_customer']) . "</td>";
        echo "<td>Rp " . number_format($row['total_sales']) . "</td>";
        echo "<td>" . ($row['status'] == 'FINAL' ? 'Lunas' : 'Belum Lunas') . "</td>";
    } elseif ($report_type == 'customer') {
        echo "<td>" . htmlspecialchars($row['nama_customer']) . "</td>";
        echo "<td>" . htmlspecialchars($row['alamat']) . "</td>";
        echo "<td>" . htmlspecialchars($row['telp']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>Rp " . number_format($row['total_purchase']) . "</td>";
    } elseif ($report_type == 'stock') {
        echo "<td>" . htmlspecialchars($row['nama_item']) . "</td>";
        echo "<td>" . htmlspecialchars($row['uom']) . "</td>";
        echo "<td>Rp " . number_format($row['harga_jual']) . "</td>";
        echo "<td>" . number_format($row['total_sold']) . "</td>";
        echo "<td>" . number_format($row['remaining_stock']) . "</td>";
    } elseif ($report_type == 'transaction') {
        echo "<td>" . htmlspecialchars($row['do_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_item']) . "</td>";
        echo "<td>" . number_format($row['quantity']) . "</td>";
        echo "<td>Rp " . number_format($row['price']) . "</td>";
        echo "<td>Rp " . number_format($row['total']) . "</td>";
    }
    
    echo "</tr>";
    $no++;
}

// Summary row
echo "<tr style='background-color:#f0f0f0; font-weight:bold;'>";
if ($report_type == 'sales') {
    echo "<td colspan='5'>TOTAL</td>";
    echo "<td>Rp " . number_format(array_sum(array_column($report_data, 'total_sales'))) . "</td>";
    echo "<td>-</td>";
} elseif ($report_type == 'customer') {
    echo "<td colspan='5'>TOTAL</td>";
    echo "<td>Rp " . number_format(array_sum(array_column($report_data, 'total_purchase'))) . "</td>";
} elseif ($report_type == 'stock') {
    echo "<td colspan='4'>TOTAL</td>";
    echo "<td>" . number_format(array_sum(array_column($report_data, 'total_sold'))) . "</td>";
    echo "<td>" . number_format(array_sum(array_column($report_data, 'remaining_stock'))) . "</td>";
} elseif ($report_type == 'transaction') {
    echo "<td colspan='5'>TOTAL</td>";
    echo "<td>Rp " . number_format(array_sum(array_column($report_data, 'total'))) . "</td>";
}
echo "</tr>";

echo "</table>";
?>
