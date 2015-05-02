<?php
$mageFilename = 'app/Mage.php';


if (!file_exists($mageFilename)) {
    echo $mageFilename . ' was not found';
}
require_once $mageFilename;
umask(0);

Mage::app('default');
Mage::app()->getCache()->clean();

$report_date = date('d-m-Y',strtotime('yesterday'));

function getResultsByRange($from, $to, $report_date)
{
	
	$sql = "SELECT Increment_id, created_at,sel_returned_val, sel_cancelled_val, state, status, base_grand_total 
			FROM sales_flat_order 
			WHERE created_at >= '".$from."' AND created_at <= '".$to."' ORDER BY Increment_id DESC";

	$resource = Mage::getSingleton('core/resource');
	     
	$readConnection = $resource->getConnection('core_read');

	$results = $readConnection->fetchAll($sql);

	$canceled_orders = 0;

	$fake_orders = 0;

	$pending_orders = 0;

	$canceled_revenue = 0;

	$total_revenue = 0;

	$total_orders = count($results);

	foreach ($results as $order) 
	{
		if($order['status']=='canceled')
		{
			$canceled_orders++;
			$canceled_revenue += $order['base_grand_total'];
		}

		if($order['sel_cancelled_val']=='Fake order')
		{
			$fake_orders++;
		}

		if($order['status']=='pending')
		{
			$pending_orders++;
			$pending_revenue += $order['base_grand_total'];
		}

		$total_revenue +=  $order['base_grand_total'];
	}

		

	$cancel_rev_percentage = number_format(round(($canceled_revenue/intval($total_revenue))*100));

	$pending_rev_percentage = number_format(round(($pending_revenue/$total_revenue)*100));

	$confirmed_revenue = $total_revenue - ($canceled_revenue + $pending_revenue);

	$confirmed_rev_percentage = number_format(round(($confirmed_revenue/$total_revenue)*100));

	$confirmed_revenue = number_format(round(($total_revenue - ($canceled_revenue + $pending_revenue))));

	$total_revenue = number_format(round($total_revenue));

	$canceled_revenue = number_format(round($canceled_revenue));

	$pending_revenue = number_format(round($pending_revenue));

	$confirmed_orders = $total_orders - $canceled_orders - $pending_orders;

	$confirmed_percentage = number_format(round(($confirmed_orders/$total_orders)*100));

	$canceled_percentage = number_format(round(($canceled_orders/$total_orders)*100));

	$fake_percentage = number_format(round(($fake_orders/$total_orders)*100));

	$pending_percentage = number_format(round(($pending_orders/$total_orders)*100));

	

	$mailbody = "<tr>
					<td style='border: 1px solid black; text-align:center;'>$report_date</td>
					
					<td style='border: 1px solid black; text-align:center;'>$total_orders</td>
					
					<td style='border: 1px solid black; text-align:center;'>$confirmed_orders</td>
					<td style='border: 1px solid black;text-align:center;'>$confirmed_percentage %</td>
					
					<td style='border: 1px solid black; text-align:center;'>$canceled_orders</td>
					<td style='border: 1px solid black; text-align:center;'>$canceled_percentage %</td>
					
					<td style='border: 1px solid black; text-align:center;'>-</td>
					<td style='border: 1px solid black; text-align:center;'>-</td>

					<td style='border: 1px solid black; text-align:center;'>$fake_orders</td>
					<td style='border: 1px solid black; text-align:center;'>$fake_percentage %</td>
					
					<td style='border: 1px solid black; text-align:center;'>$pending_orders</td>
					<td style='border: 1px solid black; text-align:center;'>$pending_percentage %</td>
					
					<td style='border: 1px solid black; text-align:center;'>$total_revenue</td>
					
					<td style='border: 1px solid black; text-align:center;'>$canceled_revenue</td>
					<td style='border: 1px solid black; text-align:center;'>$cancel_rev_percentage %</td>
					
					<td style='border: 1px solid black; text-align:center;'>$confirmed_revenue</td>
					<td style='border: 1px solid black; text-align:center;'>$confirmed_rev_percentage %</td>
					
					<td style='border: 1px solid black; text-align:center;'>$pending_revenue</td>
					<td style='border: 1px solid black; text-align:center;'>$pending_rev_percentage %</td>
				</tr>";

		return $mailbody;
}


$mailHead = "<!DOCTYPE html>
		<html lang='en'>
		<head>
			<meta charset='UTF-8'>
			<title>CC Metrics : Update for <?php echo $report_date;?> | Overcart.com</title>
		</head>
		<body>

			<div style='margin:20px; padding:10px;'>
				
				<p>Hi Team, <br>Please find below the CC Metrics update for $report_date<br></p>
				
				<br>

				<div style='margin-top:10px;'>
					<table style='border: 1px solid black;'>
						<thead style='background:rgba(192, 192, 185, 1);border: 1px solid black;'>
							<tr>
								<th style='border: 1px solid black;width:85px;'>Date</th>
								<th style='border: 1px solid black;'>Total Orders (Nos.)</th>
								<th colspan='2' style='border: 1px solid black; '>Confirmed (Nos.)</th>
								<th colspan='2' style='border: 1px solid black;'>Cancelled (Nos.)</th>
								<th colspan='2' style='border: 1px solid black;'>Duplicate Orders</th>
								<th colspan='2' style='border: 1px solid black;'>Fake (Nos.)</th>
								<th colspan='2' style='border: 1px solid black;'>On Hold/Pending (Nos.)</th>
								<th style='border: 1px solid black;'>Total Revenue Booked (Rs.)</th>
								<th colspan='2' style='border: 1px solid black;'>Total Revenue Cancelled (Rs.)</th>
								<th colspan='2' style='border: 1px solid black;'>Total Revenue Confirmed (Rs.)</th>
								<th colspan='2' style='border: 1px solid black;'>Total Revenue Pending (Rs.)</th>
							</tr>
						</thead>
						<tbody>";

$from = date('Y-m-d H:i:s',strtotime("yesterday"));
$to = date('Y-m-d H:i:s',strtotime("today"));

$now = date('Y-m-d H:i:s',strtotime("now"));
$bodyToday = getResultsByRange($to, $now, date('d-m-Y'));

$bodyYesterday = getResultsByRange($from, $to, date('d-m-Y',strtotime("yesterday")));


$from = date('Y-m-d H:i:s',strtotime("last Monday"));
$bodyLastWeek = getResultsByRange($from, $now, "This Week");


$from = date('Y-m-01 00:00:00');   //$from = date('Y-m-d H:i:s',strtotime("today -30days"));
$bodyLastMonth = getResultsByRange($from, $now, "This Month");

 
$mailFoot = "</tbody>
					</table>
				</div>

				<p><br>Regards, <br> Customer Care <br> Overcart.com | Exit10 Marketing</p>

			</div>
		</body>
		</html>";

$completeMail = $mailHead.$bodyToday.$bodyYesterday.$bodyLastWeek.$bodyLastMonth.$mailFoot;

$senderemail = 'anil.jaiswal@exit10.in';
$mail = Mage::getModel('core/email');
$mail->setToName('Overcart');
// $mail->setToEmail('operations@gamesinc.in'); //operations@gamesinc.in
$mail->setBody($completeMail);
$mail->setSubject("CC Metrics : Update for $report_date");
$mail->setFromEmail($senderemail);
$mail->setFromName('Anil Jaiswal');
$mail->setType('html');
try
{
	
	// $mail->send();
	echo $completeMail;
	echo 'mail sent to Overcart';
}
catch (Exception $e) 
{
    echo 'Error sending email.';
}
$mail->setToName('Anish');
// $mail->setToEmail('anish.kataria@overcart.com'); //operations@gamesinc.in
try
{
	
	// $mail->send();
	// echo 'mail sent to Anish';
	// echo $completeMail;
}
catch (Exception $e) 
{
    echo 'Error sending email.';
}
?>