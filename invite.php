<?php
include("vars.php");
function sendIcalEvent($from_name, $from_address, $to_name, $to_address, $startTime, $endTime, $subject, $description, $location)
{
    $domain = 'exchangecore.com';

    //Create Email Headers
    $mime_boundary = "----Meeting Booking----".MD5(TIME());

    $headers = "From: ".$from_name." <".$from_address.">\n";
    $headers .= "Reply-To: ".$from_name." <".$from_address.">\n";
    $headers .= "MIME-Version: 1.0\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\n";
    $headers .= "Content-class: urn:content-classes:calendarmessage\n";
    
    //Create Email Body (HTML)
    $message = "--$mime_boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\n";
    $message .= "Content-Transfer-Encoding: 8bit\n\n";
    $message .= "<html>\n";
    $message .= "<body>\n";
    $message .= '<p>'.$description.'</p>';
    $message .= "</body>\n";
    $message .= "</html>\n";
    $message .= "--$mime_boundary\r\n";

    $ical = 'BEGIN:VCALENDAR' . "\r\n" .
    'PRODID:-//Microsoft Corporation//Outlook 10.0 MIMEDIR//EN' . "\r\n" .
    'VERSION:2.0' . "\r\n" .
    'METHOD:REQUEST' . "\r\n" .
    'BEGIN:VTIMEZONE' . "\r\n" .
    'TZID:Eastern Time' . "\r\n" .
    'BEGIN:STANDARD' . "\r\n" .
    'DTSTART:20091101T020000' . "\r\n" .
    'RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=1SU;BYMONTH=11' . "\r\n" .
    'TZOFFSETFROM:-0400' . "\r\n" .
    'TZOFFSETTO:-0500' . "\r\n" .
    'TZNAME:EST' . "\r\n" .
    'END:STANDARD' . "\r\n" .
    'BEGIN:DAYLIGHT' . "\r\n" .
    'DTSTART:20090301T020000' . "\r\n" .
    'RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=2SU;BYMONTH=3' . "\r\n" .
    'TZOFFSETFROM:-0500' . "\r\n" .
    'TZOFFSETTO:-0400' . "\r\n" .
    'TZNAME:EDST' . "\r\n" .
    'END:DAYLIGHT' . "\r\n" .
    'END:VTIMEZONE' . "\r\n" .	
    'BEGIN:VEVENT' . "\r\n" .
    'ORGANIZER;CN="'.$from_name.'":MAILTO:'.$from_address. "\r\n" .
    'ATTENDEE;CN="'.$to_name.'";ROLE=REQ-PARTICIPANT;RSVP=TRUE:MAILTO:'.$to_address. "\r\n" .
    'LAST-MODIFIED:' . date("Ymd\TGis") . "\r\n" .
    'UID:'.date("Ymd\TGis", strtotime($startTime)).rand()."@".$domain."\r\n" .
    'DTSTAMP:'.date("Ymd\TGis"). "\r\n" .
    'DTSTART;TZID="Eastern Time":'.date("Ymd\THis", strtotime($startTime)). "\r\n" .
    'DTEND;TZID="Eastern Time":'.date("Ymd\THis", strtotime($endTime)). "\r\n" .
    'TRANSP:OPAQUE'. "\r\n" .
    'SEQUENCE:1'. "\r\n" .
    'SUMMARY:' . $subject . "\r\n" .
    'LOCATION:' . $location . "\r\n" .
    'CLASS:PUBLIC'. "\r\n" .
    'PRIORITY:5'. "\r\n" .
    'BEGIN:VALARM' . "\r\n" .
    'TRIGGER:-PT15M' . "\r\n" .
    'ACTION:DISPLAY' . "\r\n" .
    'DESCRIPTION:Reminder' . "\r\n" .
    'END:VALARM' . "\r\n" .
    'END:VEVENT'. "\r\n" .
    'END:VCALENDAR'. "\r\n";
    $message .= 'Content-Type: text/calendar;name="meeting.ics";method=REQUEST\n';
    $message .= "Content-Transfer-Encoding: 8bit\n\n";
    $message .= $ical;

    $mailsent = mail($to_address, $subject, $message, $headers);

    return ($mailsent)?(true):(false);
}

function send_invite($id) {
$from_name = "EISTOOL";        
$from_address = "eis-tool@imasoft.com";        
$location = "";
include("vars.php");
try {
    $conn = new PDO("mysql:host=$servername;dbname=patch", $username, $password);
    $sql = "select maintenance_requests.id, hostname, maintenance_requests.to, cc, scheduled_date, ADDTIME(scheduled_date, '00:05:00') as scheduled_date_end,requested_by,os_type,maintenance_registered_hosts.contact,maintenance_requests.coordination from maintenance_requests,maintenance_registered_hosts where maintenance_requests.id=$id and invited='N' and maintenance_registered_hosts.auto_patch = 'N' and maintenance_requests.hostname=maintenance_registered_hosts.host";
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    // set the resulting array to associative
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $conn = null;
    #print_r($result);
    if(isset($result['id']) && !empty($result['id']) ) {
      $to_name = "Unix Ops";        
      $to_address = "unix-ops-internal@imasoft.com";        
      if(preg_match("/^dev.*/", $result['hostname'], $match)) {
        $to_name = "ITOC Ops";        
        $to_address = "itoc-internal@imasoft.com";        
	if($result['coordination'] == 'N' ){
		return ;
	}
      }
      elseif(preg_match("/WINDOWS/", $result['os_type'], $match)) {
        $to_name = "ITOC Windows";
	$to_address = "itoc-win@imasoft.com";
      }
      $startTime = $result['scheduled_date'];
      $endTime = $result['scheduled_date_end'];
      $subject = "Patching for Host ".$result['hostname'];
      $description = "https://$localhost/$serverpath/patching_update.php?id=$id";
      sendIcalEvent($from_name, $from_address, $to_name, $to_address, $startTime, $endTime, $subject, $description, $location);

return "Invite Sent";
     }
     else {
	return "No record found or already invite sent";
     }
    }
catch(PDOException $e)
    {
    echo "Connection failed: " . $e->getMessage();
    }
$conn = null;

#echo "Invite Sent";
}

function add_maintenance_requests_queue($id) {
$from_name = "EISTOOL";        
$from_address = "admin@imasoft.com";        
$location = "";

include("vars.php");


// Create connection
try {
    $conn = new PDO("mysql:host=$servername;dbname=patch", $username, $password);
    $sql = "select maintenance_requests.id, hostname, os_type, scheduled_date,notification_type from maintenance_requests, maintenance_registered_hosts where maintenance_requests.id=$id and maintenance_registered_hosts.host=maintenance_requests.hostname";
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $curl = "curl -H 'accept: text/plain' https://unix-access.imasoft.com/query/sla/".$result['hostname']." -k";
    $sla=exec($curl);
    $itoc=0;
    $auto_patch='N';
    if(preg_match('/^dev/',$result['hostname']) && $result['notification_type'] != 2) {
       $itoc=1;
       $auto_patch='Y';
    }
    elseif(preg_match('/WINDOWS/',$result['os_type'])) {
       $itoc=2;
    }
    // set the resulting array to associative
    #print_r($result);
    if(isset($result['id']) && !empty($result['id']) ) {
      $query = "insert into maintenance_requests_queue (host,status,os_type,scheduled_date,maintenance_requests_id,sla,itoc,auto_patch) values('".$result['hostname']."','QUEUED','".$result['os_type']."','".$result['scheduled_date']."',".$result['id'].",'$sla',$itoc,'$auto_patch')";
      #$query = "insert into maintenance_requests_queue (host,status,os_type,scheduled_date,maintenance_requests_id) values('".$result['hostname']."','QUEUED','".$result['os_type']."','".$result['scheduled_date']."',".$result['id'].")";
      $conn->exec($query);
      $conn = null;
     }
     else {
      $conn = null;
        return "No record found or already invite sent";
     }
    }
catch(PDOException $e)
    {
    echo "Connection failed: " . $e->getMessage();
    }
$conn = null;

#var_dump($argv);#echo $_REQUEST['id'];
#echo "Invite Sent";
}
?>
