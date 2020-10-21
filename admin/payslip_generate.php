<?php
	include 'includes/session.php';

	$range = $_POST['date_range'];
	$ex = explode(' - ', $range);
	$from = date('Y-m-d', strtotime($ex[0]));
	$to = date('Y-m-d', strtotime($ex[1]));

	$sql = "SELECT *, SUM(amount) as total_amount FROM deductions";
    $query = $conn->query($sql);
   	$drow = $query->fetch_assoc();
    $deduction = $drow['total_amount'];

	$from_title = date('M d, Y', strtotime($ex[0]));
	$to_title = date('M d, Y', strtotime($ex[1]));

	require_once('../tcpdf/tcpdf.php');
    $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetTitle('Payslip: '.$from_title.' - '.$to_title);
    $pdf->SetHeaderData('', '', PDF_HEADER_TITLE, PDF_HEADER_STRING);
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    $pdf->SetDefaultMonospacedFont('helvetica');
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    $pdf->SetMargins(PDF_MARGIN_LEFT, '10', PDF_MARGIN_RIGHT);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(TRUE, 10);
    $pdf->SetFont('courier', '', 11);
    $pdf->AddPage();
    $contents = '';

	$sql = "SELECT *, SUM(num_hr) AS total_hr, attendance.employee_id AS empid, employees.employee_id AS employee FROM attendance LEFT JOIN employees ON employees.id=attendance.employee_id LEFT JOIN position ON position.id=employees.position_id WHERE date BETWEEN '$from' AND '$to' GROUP BY attendance.employee_id ORDER BY employees.lastname ASC, employees.firstname ASC";

	$query = $conn->query($sql);
	while($row = $query->fetch_assoc()){
		$empid = $row['empid'];

      	$casql = "SELECT *, SUM(amount) AS cashamount FROM cashadvance WHERE employee_id='$empid' AND date_advance BETWEEN '$from' AND '$to'";

      	$caquery = $conn->query($casql);
      	$carow = $caquery->fetch_assoc();
      	$cashadvance = $carow['cashamount'];

		$gross = $row['rate'] * $row['total_hr'];
		$total_deduction = $deduction + $cashadvance;
  		$net = $gross - $total_deduction;

		$contents .= '
			<h1 align="center">SLIP GAJI</h1>
			<h2 align="center">PT. Lima Karsa Kreasi Tama</h2>
			<h4 align="center">'.$from_title." - ".$to_title.'</h4>
			<table cellspacing="0" cellpadding="3">
    	       	<tr>
            		<td width="25%" align="right">Nama Karyawan: </td>
                 	<td width="25%"><b>'.$row['firstname']." ".$row['lastname'].'</b></td>
				 	<td width="25%" align="right">Tarif Per Jam: </td>
                 	<td width="25%" align="right">'.number_format($row['rate']).'</td>
    	    	</tr>
    	    	<tr>
    	    		<td width="25%" align="right">ID Karyawan: </td>
				 	<td width="25%">'.$row['employee'].'</td>
				 	<td width="25%" align="right">Jumlah Jam: </td>
				 	<td width="25%" align="right">'.number_format($row['total_hr']).'</td>
    	    	</tr>
    	    	<tr>
    	    		<td></td>
    	    		<td></td>
				 	<td width="25%" align="right"><b>Gaji Kotor: </b></td>
				 	<td width="25%" align="right"><b>'.number_format(($row['rate']*$row['total_hr'])).'</b></td>
    	    	</tr>
    	    	<tr>
    	    		<td></td>
    	    		<td></td>
				 	<td width="25%" align="right">Potongan: </td>
				 	<td width="25%" align="right">'.number_format($deduction).'</td>
    	    	</tr>
    	    	<tr>
    	    		<td></td>
    	    		<td></td>
				 	<td width="25%" align="right">Kas Bon: </td>
				 	<td width="25%" align="right">'.number_format($cashadvance).'</td>
    	    	</tr>
    	    	<tr>
    	    		<td></td>
    	    		<td></td>
				 	<td width="25%" align="right"><b>Jumlah Potongan:</b></td>
				 	<td width="25%" align="right"><b>'.number_format($total_deduction).'</b></td>
    	    	</tr>
    	    	<tr>
    	    		<td></td>
    	    		<td></td>
				 	<td width="25%" align="right"><b>Gaji Bersih:</b></td>
				 	<td width="25%" align="right"><b>'.number_format($net).'</b></td>
    	    	</tr>
    	    </table>
    	    <br><hr>
		';
	}
    $pdf->writeHTML($contents);
    $pdf->Output('slipgaji.pdf', 'I');

?>
