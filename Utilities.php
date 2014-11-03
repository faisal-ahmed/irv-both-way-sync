<?php
/*
 * Created on January 6, 2014
 * Author: Mohammad Faisal Ahmed <faisal.ahmed0001@gmail.com>
 */

//require_once 'PHPMailer/PHPMailerAutoload.php';

//Zoho Modules Name
define("AUTH_TOKEN", "34ecaa61682c758a68da32554db3d0c9");

define("DRIP_CAMPAIGNID", "1150401000000077164");

define("LEAD_MODULE", "Leads");
define("ACCOUNT_MODULE", "Accounts");
define("CONTACT_MODULE", "Contacts");
define("POTENTIAL_MODULE", "Potentials");
define("CAMPAIGN_MODULE", "Campaigns");
define("CASE_MODULE", "Cases");
define("SOLUTION_MODULE", "Solutions");
define("PRODUCT_MODULE", "Products");
define("PRICE_BOOK_MODULE", "PriceBooks");
define("QUOTE_MODULE", "Quotes");
define("INVOICE_MODULE", "Invoices");
define("SALES_ORDER_MODULE", "SalesOrders");
define("VENDOR_MODULE", "Vendors");
define("PURCHASE_ORDER_MODULE", "PurchaseOrders");
define("EVENT_MODULE", "Events");
define("TASK_MODULE", "Tasks");
define("CALL_MODULE", "Calls");

function debug($data){
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

function sendBirthdayReminder($data) {
    $from = 'admin@pattayacondo.com';
    $to = 'faisal.ahmed0001@gmail.com';
    $subject = "Clients Birthday Reminder";
    $messageHeader = "The clients below will have their birthday coming up:";
    $messageBody = '';
    $messageFooter = "<br/><br/>Thanks<br/>Letsflycheaper Admin";
    foreach ($data as $key => $value) {
        $messageBody .= "<br/><br/>Name of Client: {$value['name']}<br/>Email of Client: {$value['email']}<br/>Date of Birth: {$value['dob']}<br/>Contact Owner: {$value['owner']}<br/>Unique ID: {$value['unique']}";
    }
    $message = $messageHeader . $messageBody . $messageFooter;
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= "From: $from" . "\r\n";

    /*$mail = new PHPMailer;

//    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = 'ssl://smtp.gmail.com';  // Specify main and backup server
    $mail->Port='25';
    $mail->SMTPAuth = true;                               // Enable SMTP authentication
    $mail->Username = '************';                            // SMTP username
    $mail->Password = '************';                           // SMTP password
    $mail->SMTPSecure = 'ssl';                            // Enable encryption, 'ssl' also accepted

    $mail->WordWrap = 50;
    $mail->SMTPDebug = 1;
    $mail->From = $from;
    $mail->addAddress($to);               // Name is optional
    $mail->isHTML(true);                                  // Set email format to HTML

    $mail->Subject = $subject;
    $mail->Body    = $message;

    if(!$mail->send()) {
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
        exit;
    }*/
    $mailStatus = mail($to, $subject, $message, $headers);
    debug($mailStatus);
    echo "$to $from";
}

?>