<?php
date_default_timezone_set("Asia/Kolkata"); //gets set to "UTC" by magento somewhere in this script itself
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

	$duplicate_order = 0;
	
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

		if($order['sel_cancelled_val']=='Duplicate order')
		{
			$duplicate_order++;	
		}

		if($order['status']=='pending' || $order['status'] == 'pending_payment')
		{
			$pending_orders++;
			$pending_revenue += $order['base_grand_total'];
		}

		if($order['status']=='rto'){
			$rto++;
			$rto_revenue += $order['base_grand_total'];
		}

		if($order['status']=='returned'){
			$returned++;
			$returned_revenue += $order['base_grand_total'];
		}

		if($order['status']=='reverse pickup'){
			$reverse++;
			$reverse_revenue += $order['base_grand_total'];
		}

		$total_revenue +=  $order['base_grand_total'];
	}

	$cancel_rev_percentage = number_format(round(($canceled_revenue/intval($total_revenue))*100));

	$pending_rev_percentage = number_format(round(($pending_revenue/$total_revenue)*100));

	$confirmed_revenue = $total_revenue - ($canceled_revenue + $pending_revenue + $rto_revenue + $returned_revenue + $reverse_revenue);

	$confirmed_rev_percentage = number_format(round(($confirmed_revenue/$total_revenue)*100));

	$confirmed_revenue = number_format(round(($total_revenue - ($canceled_revenue + $pending_revenue))));

	$total_revenue = number_format(round($total_revenue));

	$canceled_revenue = number_format(round($canceled_revenue));

	$pending_revenue = number_format(round($pending_revenue));

	$confirmed_orders = $total_orders - $canceled_orders - $pending_orders;

	$confirmed_percentage = number_format(round(($confirmed_orders/$total_orders)*100));

	$canceled_percentage = number_format(round(($canceled_orders/$total_orders)*100));

	$duplicate_percentage = number_format(round(($duplicate_order/$total_orders)*100));

	$fake_percentage = number_format(round(($fake_orders/$total_orders)*100));

	$pending_percentage = number_format(round(($pending_orders/$total_orders)*100));
	

	$mailbody = "<tr style='display: table-row;vertical-align: inherit;border-color: inherit;'>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;width: 100px;'>$report_date</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;'>$total_orders</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;'>$confirmed_orders</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;width: 30px;'>$confirmed_percentage%</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;'>$canceled_orders</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;width:30px;'>$canceled_percentage%</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;'>$duplicate_order</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal; width:30px;'>$duplicate_percentage%</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;'>$fake_orders</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;width:30px;'>$fake_percentage%</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;'>$pending_orders</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;width:30px;'>$pending_percentage%</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;'>$total_revenue</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;'>$canceled_revenue</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;width:30px;'>$cancel_rev_percentage%</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;'>$confirmed_revenue</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;width:30px;'>$confirmed_rev_percentage%</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;'>$pending_revenue</td>
					<td style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: normal;width:30px;'>$pending_rev_percentage%</td>
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
					<table 
						style='
						border: 1px solid #000;width: 100%;
						max-width: 100%;
						margin-bottom: 20px;
						background-color: transparent;
						border-spacing: 0;
						border-collapse: collapse;
						white-space: normal;
						line-height: normal;
						font-weight: normal;
						font-size: medium;
						font-variant: normal;
						font-style: normal;
						color: -webkit-text;
						text-align: start;
						display: table;
						font-family: Helvetica Neue,Helvetica,Arial,sans-serif;
						'>
						<thead 
						style='
						display: table-header-group;
						vertical-align: middle;
						border-color: inherit;
						'>
							<tr style='display: table-row;
							vertical-align: inherit;
							border-color: inherit;
								'>
								<th style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: bold;width: 100px;' >Date</th>
								<th style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: bold;'>Total Orders (Nos.)</th>
								<th colspan='2' style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: bold;width: 100px;'>Confirmed (Nos.)</th>
								<th colspan='2' style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: bold;'>Cancelled (Nos.)</th>
								<th colspan='2' style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: bold;'>Duplicate Orders</th>
								<th colspan='2' style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: bold;width: 100px;'>Fake (Nos.)</th>
								<th colspan='2' style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: bold;'>On Hold/Pending (Nos.)</th>
								<th style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: bold;'>Total Revenue Booked (Rs.)</th>
								<th colspan='2' style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: bold;'>Total Revenue Cancelled (Rs.)</th>
								<th colspan='2' style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: bold;'>Total Revenue Confirmed (Rs.)</th>
								<th colspan='2' style='border-top: 0;border-bottom-width: 2px;border: 1px solid #ddd;vertical-align: bottom;border-bottom: 2px solid #ddd;padding: 8px;line-height: 1.42857143;text-align: center;font-weight: bold;'>Total Revenue Pending (Rs.)</th>
							</tr>
						</thead>
						<tbody style= 'box-sizing: border-box;
						display: table-row-group;
						vertical-align: middle;
						border-color: inherit;
						box-sizing: border-box;'>";


date_default_timezone_set("Asia/Kolkata");

// DO NOT DELETE THESE COMMENTS:
// echo date_default_timezone_get();echo "<br>";
// echo "now - ";var_dump(date('Y-m-d H:i:s',strtotime("now",time())));echo "<br/>"; //current IST time
// echo "today - ";var_dump(date('Y-m-d H:i:s',strtotime("today")));echo "<br/>"; // today's date; 00:00:00 time
// echo "yesterday - ";var_dump(date('Y-m-d H:i:s',strtotime("yesterday")));echo "<br/>";// yesterday's date; 00:00:00 time
// echo "last Monday - ";var_dump(date('Y-m-d H:i:s',strtotime("last Monday")));echo "<br/>";//last monday's date; ; 00:00:00 time
// echo "first day of this month - ";var_dump(date('Y-m-d H:i:s',strtotime("first day of this month")));echo "<br/>"; // 1st of this month; CURRENT IST time
// echo "last day of last month - ";var_dump(date('Y-m-d H:i:s',strtotime("last day of last month")));echo "<br/>";echo"<hr/>";// 30th of prev. month; CURRENT IST time
//-Shikhar

$now = date('Y-m-d H:i:s',strtotime("now"));

//today:
$from = date('Y-m-d H:i:s',strtotime("today"));
$to = $now;
$report_period = date('d-m-Y');
// echo "today";echo "<br/>";
// var_dump($from);echo "<br/>";
// var_dump($to);echo "<br/>";
// echo "report period: ";var_dump($report_period); echo "<hr/>";
$bodyToday = getResultsByRange($to, $now, $report_period);// 00:00:00 today -> now (18:30:00 if cronjob is set at 6:30pm)

//yesterday
$from = date('Y-m-d H:i:s',strtotime("yesterday"));
$to = date('Y-m-d H:i:s',strtotime("today"));
$report_period = date('d-m-Y',strtotime("yesterday"));
// echo "yesterday";echo "<br/>";
// var_dump($from);echo "<br/>";
// var_dump($to);echo "<br/>";
// echo "report period: ";var_dump($report_period); echo "<hr/>";
$bodyYesterday = getResultsByRange($from, $to, $report_period);// 00:00:00 yesterday -> 00:00:00 today

//This week
$from = date('Y-m-d 00:00:00',strtotime("last Monday"));// 00:00:00 instead of H:i:s
$to = $now;
$report_period =  "This Week";
// var_dump($from);echo "<br/>";
// var_dump($to);echo "<br/>";
// echo "report period: ";var_dump($report_period); echo "<hr/>";
$bodyLastWeek = getResultsByRange($from, $to, $report_period); // LastMOnday 00:00:00 to -> now (18:30:00 if cronjob is set at 6:30pm)

//This month
$from = date('Y-m-d 00:00:00',strtotime("first day of this month"));// 00:00:00 instead of H:i:s
$to = $now;
$report_period =  "This Month";
// var_dump($from);echo "<br/>";
// var_dump($to);echo "<br/>";
// echo "report period: ";var_dump($report_period); echo "<hr/>";
$bodyThisMonth = getResultsByRange($from, $now, $report_period); // 1st of this month 00:00:00 to -> now (18:30:00 if cronjob is set at 6:30pm)


$from = date('Y-m-d 00:00:00',strtotime("first day of last month"));// 00:00:00 instead of H:i:s
$to = date('Y-m-d 00:00:00',strtotime("last day of last month"));// 00:00:00 instead of H:i:s
$report_period =  "Last Month";
// var_dump($from);echo "<br/>";
// var_dump($to);echo "<br/>";
// echo "report period: ";var_dump($report_period); echo "<hr/>";
$bodyLastMonth = getResultsByRange($from, $to, $report_period); // 1st_previousMonth 00:00:00 -> lastDay_previousMonth 00:00:00

 
$mailFoot = "</tbody >
					</table>
				</div>

				<p><br>Regards, <br> Customer Care <br> Overcart.com | Exit10 Marketing</p>

			</div>
		</body>
		</html>";

$completeMail = $mailHead.$bodyToday.$bodyYesterday.$bodyLastWeek.$bodyThisMonth.$bodyLastMonth.$mailFoot;

$senderemail = 'anil.jaiswal@overcart.com';
$mail = Mage::getModel('core/email');
$mail->setToName('Team Overcart');
// $mail->setToEmail('team@overcart.com'); //operations@gamesinc.in
$mail->setToEmail('shikhar.91939@gmail.com');
$mail->setBody($completeMail);
$mail->setSubject("CC Metrics : Update for $report_date");
$mail->setFromEmail($senderemail);
$mail->setFromName('Anil Jaiswal');
$mail->setType('html');
try
{
	
	// $mail->send();
	echo $completeMail;
	echo 'email initiated';

}
catch (Exception $e) 
{
    Mage::logException($e);
}
