<?php 
include("vars.php");
require("validate_user.php");
if((!isset($_REQUEST['id']) || empty($_REQUEST['id'])) && (!isset($_REQUEST['mid']) || empty($_REQUEST['mid']))) {
   echo "No id's passed. Exiting....";
   exit;
}
include("header.php");
$id="";
      
// Create connection
try {
    $conn = new PDO("mysql:host=$servername;dbname=patch", $username, $password);
    $sql = "select mrq.coord_mail,mrq.maintenance_requests_id,mrq.updates_log,mrq.id,mrq.host,mrq.logfile,mrq.status,mrq.auto_patch,mrq.os_type,mrq.started_date,mrq.completed_date,mr.to,mr.cc,mrq.scheduled_date,mr.coordination,TIMESTAMPDIFF(MINUTE,mrq.scheduled_date,now()) as mins,mrq.sla,mrh.auto_patch as auto from maintenance_registered_hosts mrh,maintenance_requests mr, maintenance_requests_queue mrq where mrh.host=mrq.host and ";

     if(isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
       $sql .= "mr.id=".$_REQUEST['id'];
        $id=$_REQUEST['id'];
     } else if(isset($_REQUEST['mid']) && !empty($_REQUEST['mid'])) {
       $sql .= " mrq.id=".$_REQUEST['mid'];
        $id=$_REQUEST['mid'];
     }
     $sql .= " and mrq.maintenance_requests_id=mr.id";
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $conn = null;
    if(isset($result['id']) && !empty($result['id'])) {
?>
<div class="container" style="">
<div id="head" >
	<h1 class="page-header">Patching Action Page</h1>
</div>
<div style="" class="row">
<div class="panel panel-primary">
  <div class="panel-heading">Info/Action <span class="pull-right"></span></div>
    <dl class="dl-horizontal">
		<dt>Host:</dt>
		<dd><?php echo $result['host']." ";
		if($result['sla'] == 'T1'){
		  echo "<span class='label label-danger'> ".$result['sla']; 
		}
		else if($result['sla'] == 'T2'){
		  echo "<span class='label label-warning'>".$result['sla']; 
                }
                else {
		  echo "<span class='label label-primary'>".$result['sla']; 
 		}?> </span></dd>
		<dt>Status:</dt>
		<dd><?php echo $result['status']; ?></dd>
		<dt>Owner:</dt>
		<dd><?php echo $result['to']; ?></dd>
		<dt>OS Type:</dt>
		<dd><?php echo $result['os_type']; ?></dd>
		<dt>Maintenance Date:</dt>
		<dd><?php echo $result['scheduled_date']; ?> </dd>
		<dt>Started At:</dt>
		<dd><?php echo $result['started_date']; ?> </dd>
		<dt>Completed At:</dt>
		<dd><?php echo $result['completed_date']; ?></dd>
		<dt>Last Patched:</dt>
    </dl>
    <div class="btn-toolbar text-center" style="padding:0px 10px 10px 10px;"role="toolbar">
<?php if($result['coordination'] == 'Y') {?>
<div style="position:absolute;top:280px;left:50%;" class="label label-danger"><h4><span class="label label-danger;" >Note: This host coordination REQUIRED</span></h4></div>
<?php
if($result['coord_mail'] == 'N' && $result['mins'] < 120 && $result['status'] == 'QUEUED') {
  echo " <a class='btn btn-danger btn-lg' style='margin-top:5px;' href='patching_status.php?mid=".$result['maintenance_requests_id']."&id=".$result['id']."&status=COORD_MAIL'><span class='glyphicon glyphicon-envelope'><br/>Send Co-ordination Email</span</a>";
}
?>
<?php

}
if($result['auto_patch'] == 'Y' || $result['auto'] == 'Y') {
if( $result['mins'] < 0) {
  echo "<h2 class='label label-info'>Scheduled Date is yet to come".$result['mins']."</h2>";
}
if(!empty($result['logfile']) && $result['status'] != 'QUEUED') {
?>
<a class='btn btn-info btn-lg' style='width:200px;margin-top:5px;' href="/patch/show.php?log=<?php echo $result['logfile']; ?>"><span class="glyphicon glyphicon-file"><br/>Log</span></a>
<?php 
} 
else {
if($result['status'] == 'QUEUED') {
  echo " <a class='btn btn-danger btn-lg' style='width:200px;margin-top:5px;'  href='patching_window_change.php?mid=".$result['maintenance_requests_id']."&id=".$result['id']."'><span class='glyphicon glyphicon-edit'><br/>Reschedule</span> </a>";
}
  echo " <button class='btn btn-warning btn-lg' style='width:200px;margin-top:5px;' onclick='javascript:cancel_maintenance(".$result['maintenance_requests_id'].",".$result['id'].",\"CANCELLED\")'><span class='glyphicon glyphicon-remove-sign'><br>Cancel</span></button>";
}

if(!empty($result['updates_log'])) {
echo "<span class='label label-info'>User Action Log: </span>";
echo preg_replace('/\r/','<br>',$result['updates_log']); 
}
if($result['status'] == 'FAILED' || $result['status'] == 'TIMEOUT') {
echo "<div> <a class='btn btn-success btn-lg' style='width:200px;margin-top:5px;' href='patching_status.php?mid=".$result['maintenance_requests_id']."&id=".$result['id']."&status=COMPLETED'><span class='glyphicon glyphicon-ok'><br/>Completed</span></a></div>";
echo "<div> <a class='btn btn-primary btn-lg' style='width:200px;margin-top:5px;' href='patching_status.php?mid=".$result['maintenance_requests_id']."&id=".$result['id']."&status=RERUN'><span class='glyphicon glyphicon-repeat'> <br>Rerun</span></a></div>";
  echo "<div> <button class='btn btn-warning btn-lg' style='width:200px;margin-top:5px;' onclick='javascript:cancel_maintenance(".$result['maintenance_requests_id'].",".$result['id'].",\"CANCELLED\")'><span class='glyphicon glyphicon-remove-sign'><br/>Cancel</span></button></div>";
}
}
else {
if( $result['status'] == 'QUEUED') {

  echo " <a class='btn btn-danger btn-lg' style='width:200px;margin-top:5px;'  href='patching_window_change.php?mid=".$result['maintenance_requests_id']."&id=".$result['id']."'><span class='glyphicon glyphicon-edit'><br/>Reschedule</span> </a>";
?>
<a class='btn btn-primary btn-lg' style='width:200px;margin-top:5px;' href="patching_auto.php?mid=<?php echo $_REQUEST['id']; ?>&id=<?php echo $result['id']; ?>&status=QUEUED" > <span class="glyphicon glyphicon-play"><br />Set Auto</span></a>
<?php
}
if($result['status'] == 'RUNNING' || $result['status'] == 'FAILED' || $result['status'] == 'QUEUED') {
  echo " <button class='btn btn-warning btn-lg' style='width:200px;margin-top:5px;' onclick='javascript:cancel_maintenance(".$result['maintenance_requests_id'].",".$result['id'].",\"CANCELLED\")'><span class='glyphicon glyphicon-remove-sign'><br/>Cancel</span></button>";
}
?>
<?php if($result['status'] == 'COMPLETED' || $result['status'] == 'CANCELLED') { ?>
<h1 class="label label-info" style="padding:5px;">
INFO: Patching is completed for this Host
</h1>
<br>
<b><span class="label label-warning">User Action LOG: </span></b>
<?php 
$log_msg = preg_replace('/\r/','<br>',$result['updates_log']);
$log_msg = preg_replace('/RUNNING/','<span class="label label-info">RUNNING</span>',$log_msg);
$log_msg = preg_replace('/COMPLETED/','<span class="label label-success">COMPLETED</span>',$log_msg);
echo $log_msg;
}
else { 
if($result['mins'] > 0 && $result['mins'] < 175 ) {
?>
 <a class='btn btn-lg <?php if($result['status'] == 'QUEUED') { echo 'btn-info'; } else { echo 'btn-success';} ?>' style='width:200px;margin-top:5px;' href="patching_status.php?mid=<?php echo $result['maintenance_requests_id']; ?>&id=<?php echo $result['id']; ?>&status=<?php if($result['status'] == 'QUEUED') { echo 'RUNNING'; } else { echo 'COMPLETED';} ?>"><?php if($result['status'] == 'QUEUED') { echo '<span class="glyphicon glyphicon-send"><br/>Started'; } else { echo '<span class="glyphicon glyphicon-plane"><br/>Completed';} ?></span></a>
<?php if($result['status'] == 'QUEUED' && $result['os_type'] != 'WINDOWS') { ?>
<!-- <a class='btn btn-primary btn-lg' style='width:200px;margin-top:5px;' href="patching_auto.php?mid=<?php #echo $_REQUEST['id']; ?>&id=<?php #echo $result['id']; ?>&status=QUEUED" ><span class="glyphicon glyphicon-play"><br />Set Auto</span></a> -->
<?php 
}
if(!empty($result['updates_log'])) {
echo "<br><br>";
echo "<span class='label label-info'>User Action Log: </span>";
$log_msg = preg_replace('/\r/','<br>',$result['updates_log']);
$log_msg = preg_replace('/RUNNING/','<span class="label label-info">RUNNING</span>',$log_msg);
$log_msg = preg_replace('/COMPLETED/','<span class="label label-success">COMPLETED</span>',$log_msg);
echo $log_msg;
}
}
else {
if($result['status'] == 'RUNNING') {
?>
<a class='btn btn-primary btn-lg' style='width:200px;margin-top:5px;' href="patching_status.php?mid=<?php echo $_result['maintenance_requests_id']; ?>&id=<?php echo $result['id']; ?>&status=RUNNING" ><span class="glyphicon glyphicon-ok" ><br/>Completed </span></a>
<?php
}
else {
if($result['mins'] < 180) {
  echo "<h2 class='label label-info'>Scheduled Date is yet to come</h2>";
}
else {
   echo "<h2 class='label label-danger'>Scheduled Date has passed for this host</h2>";
}
}
}
}
}
?>
</div>

<?php
}
else {
    echo "<h1 class='label label-danger'> ERROR: This patching request cannot be processed. No entry present for this request ID </h1>";
}
}
catch(PDOException $e)
    {
    echo $sql . "<br>" . $e->getMessage();
    }

$conn = null;
?>
</div>
</div>
</div>
</div>
</div>
<script>
function cancel_maintenance(id,mid,state) {
    var comment = prompt("Please provide reason to cancel this maintenance");
//alert(comment);
    if ( comment == null) {
      return;
    }
    if (comment === "" || comment == null) {
      alert("You havenot entered any comment. Please try again");
    }
    else {
      window.location = "./patching_cancel.php?mid="+id+"&id="+mid+"&comment="+comment;
    }
}
</script>
