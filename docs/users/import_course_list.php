<?php
/************************************************************************/
/* ATutor																*/
/************************************************************************/
/* Copyright (c) 2002-2004 by Greg Gay, Joel Kronenberg & Heidi Hazelton*/
/* Adaptive Technology Resource Centre / University of Toronto			*/
/* http://atutor.ca														*/
/*																		*/
/* This program is free software. You can redistribute it and/or		*/
/* modify it under the terms of the GNU General Public License			*/
/* as published by the Free Software Foundation.						*/
/************************************************************************/

$section = 'users';
define('AT_INCLUDE_PATH', '../include/');
require (AT_INCLUDE_PATH.'vitals.inc.php');

$course = intval($_REQUEST['course']);
$title = _AT('course_enrolment');

function checkUserInfo($record) {
//function checkUserInfo(list($row['fname'],...)) {
	global $db;

	if($record['fname']=='') {
		$record['fname'] = $record[0];
		$record['lname'] = $record[1];
		$record['email'] = $record[2];
		$record['uname'] = $record[3];
	}

	//error flags for this record
	$record['err_email'] = FALSE;
	$record['err_uname'] = FALSE;
	$record['exists'] = FALSE;

	/* email check */
	if ($record['email'] == '') {
		$record['err_email'] = _AT('import_err_email_missing');
	} else if (!eregi("^[a-z0-9\._-]+@+[a-z0-9\._-]+\.+[a-z]{2,3}$", $record['email'])) {
		$record['err_email'] = _AT('import_err_email_invalid');
	}
	$sql="SELECT * FROM ".TABLE_PREFIX."members WHERE email LIKE '".$record['email']."'";
	$result = mysql_query($sql,$db);
	if (mysql_num_rows($result) != 0) {
		$row = mysql_fetch_array($result);
		$record['exists'] = _AT('import_err_email_exists');
		$record['fname'] = $row['first_name']; 
		$record['lname'] = $row['last_name'];
		$record['email'] = $row['email'];
		$record['uname'] = $row['login'];
	}

	/* login check */
	if (empty($record['uname'])) {
		$record['uname'] = stripslashes($record['fname'][0].$record['lname']);
	} 

	if (!(eregi("^[a-zA-Z0-9_]([a-zA-Z0-9_])*$", $record['uname']))) {
		$record['err_uname'] = _AT('import_err_username_invalid');
	} 
	$sql = "SELECT * FROM ".TABLE_PREFIX."members WHERE login='".$record['uname']."'";
	$result = mysql_query($sql,$db);
	if ((mysql_num_rows($result) != 0) && !$record['exists']) {
		$record['err_uname'] = _AT('import_err_username_exists');
	} else if ($_POST['login'] == ADMIN_USERNAME) {
		$record['err_uname'] = _AT('import_err_username_exists');
	}	

	return $record;
}

require(AT_INCLUDE_PATH.'cc_html/header.inc.php');

if ($_POST['submit'] && !$_POST['verify']) {
	if ($_FILES['file']['size'] < 1) {
		$errors[] = AT_ERROR_FILE_NOT_SELECTED;		
	} else {
		$fp = fopen($_FILES['file']['tmp_name'],'r');
		while ($data = fgetcsv($fp, 100000, ',')) {									
			if ($data[2]=='' || empty($data[2])) {
				$errors[] = AT_ERROR_INCORRECT_FILE_FORMAT;
				break;
			} else {
				$students[] = checkUserInfo($data);
			}
		}
	}
	print_errors($errors);
}

if ($_POST['submit']=='' || !empty($errors)) {
	//step one - upload file
?>
	<table cellspacing="1" cellpadding="0" border="0" class="bodyline" summary="" width="90%">
	<tr><th class="cyan"><?php echo _AT('list_import_course_list');  ?></th></tr>
	<tr><td class="row1"><?php echo _AT('list_import_howto'); ?></td></tr>
	<tr><td height="1" class="row2"></td></tr>
	<tr><td class="row1" align="center">

	<form enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
	<input type="hidden" name="MAX_FILE_SIZE" value="30000" />
	<input type="hidden" name="course" value="<?php echo $course; ?>" />
	<label for="course_list"><?php echo _AT('import_course_list'); ?>: </label>
	<input type="file" name="file" id="course_list" class="formfield" />
	<input type="submit" name="submit" value="<?php echo _AT('list_import_course_list');  ?>" class="button" />
	</form>

	</td></tr>
	</table>

	<p><br /><a href="users/enroll_admin.php?course=<?php echo $course; ?>#results"><?php echo _AT('list_return_to_enrollment'); ?></a> 
	</p>

<?php

} else {	
	//step two - verify information

	if ($_POST['verify']) {
		//check verified data for errors
		for ($i=0; $i<$_POST['count']; $i++) {			
			if (!$_POST['remove'.$i]) {
				$students[] = checkUserInfo(array($_POST['fname'.$i], $_POST['lname'.$i], $_POST['email'.$i], $_POST['uname'.$i]));
				if (!empty($students[$i]['err_email']) || !empty($students[$i]['err_uname'])) {
					$still_errors = TRUE;
				}
			}
		}
		if (!$still_errors && ($_POST['submit']==_AT('import_course_list'))) {			
			//step three - make new users in DB, enroll all, and redirect w/ feedback		
//debug ($students);		

			foreach ($students as $student) {
				$stud_id=0;
				$name = $student['fname'].' '.$student['lname'];

				if (empty($student['exists'])) {
					//make new user
					$sql = "INSERT INTO ".TABLE_PREFIX."members (member_id, login, password, email, first_name, last_name, gender, creation_date) VALUES (0, '".$student['uname']."', '".$student['uname']."', '".$student['email']."', '".$student['fname']."', '".$student['lname']."', '', NOW())";
					if($result = mysql_query($sql,$db)) {
						echo _AT('list_new_member_created', $name);
						$stud_id = mysql_insert_id();
						$student['exists'] = _AT('import_err_email_exists');
					}
				} 

				$sql = "SELECT member_id FROM ".TABLE_PREFIX."members WHERE email='".$student['email']."'";
				if ($result = mysql_query($sql,$db)) {
					$row=mysql_fetch_array($result);	
					$stud_id = $row['member_id'];
				} else {
					$errors[] = AT_ERROR_LIST_IMPORT_FAILED;					
				}

				//enroll student				
				$sql = "INSERT INTO ".TABLE_PREFIX."course_enrollment (member_id, course_id, approved) VALUES ('$stud_id', '".$_POST['course']."', 'y')";

				if($result = mysql_query($sql,$db)) {
					echo _AT('list_member_enrolled', $name);
				} else {
					echo _AT('list_member_already_enrolled', $name);
				}
			}
			echo '<p><br /><a href="users/enroll_admin.php?course='.$course.'#results">'._AT('list_return_to_enrollment').'</a></p>';
		}
	} 
	if (!$_POST['verify'] || $still_errors || ($_POST['submit']==_AT('resubmit'))) {
		
		//output results table		
		echo _AT('import_course_list_verify');

		echo '<br /><br /><form enctype="multipart/form-data" action="'.$_SERVER['PHP_SELF'].'" method="post">';
		echo '<input type="hidden" name="verify" value="1" />';	
		echo'<input type="hidden" name="course" value="'.$course.'" />';
		
		echo '<table width="100%" cellspacing="1" cellpadding="0" border="0" class="bodyline" summary="">';
		echo '<tr><th class="cyan" colspan="6">'._AT('list_import_results').'</th></tr>';
		echo '<tr><th class="cat" scope="col">'._AT('status').'</th><th class="cat" scope="col">'._AT('first_name').'</th><th class="cat" scope="col">'._AT('last_name').'</th><th class="cat" scope="col">'._AT('email').'</th><th class="cat" scope="col">'._AT('username').'</th><th class="cat" scope="col">'._AT('remove').'</th></tr>';

		$err_count = 0;
		$i=0;
		foreach ($students as $student) {
			echo '<tr><small>';
			echo '<td class="row1"><font color="red">';

			//give status
			if(!empty($student['err_email'])) {
				echo $student['err_email'];
			} 			
			if(!empty($student['err_uname'])) {
				if(!empty($student['err_email'])) {
					echo '<br />';
				}
				echo $student['err_uname'];				
			} 		
			if (empty($student['err_uname']) && empty($student['err_email'])) {
				echo '</font><font color="green">'._AT('ok');								
				if (!empty($student['exists'])) {
					echo ' - '.$student['exists'];
				}
			} else {
				$err_count++;
			}
			echo '</font></td>';	

			if (empty($student['exists'])) {
				echo '<td class="row1"><input type="text" name="fname'.$i.'" class="formfield" value="'.$student['fname'].'" size="10" /></td>';
				echo '<td class="row1"><input type="text" name="lname'.$i.'" class="formfield" value="'.$student['lname'].'" size="10" /></td>';
				echo '<td class="row1"><input type="text" name="email'.$i.'" class="formfield" value="'.$student['email'].'" size="14" /></td>';				
				echo '<td class="row1"><input type="text" name="uname'.$i.'" class="formfield" value="'.$student['uname'].'" size="10" />';	
				echo '<td class="row1" align="center"><input type="checkbox" name="remove'.$i.'" />';
			} else {
				echo '<input type="hidden" name="fname'.$i.'" value="'.$student['fname'].'" />';		
				echo '<input type="hidden" name="lname'.$i.'" value="'.$student['lname'].'" />';		
				echo '<input type="hidden" name="email'.$i.'" value="'.$student['email'].'" />';		
				echo '<input type="hidden" name="uname'.$i.'" value="'.$student['uname'].'" />';		

				echo '<td class="row1">'.$student['fname'].'</td>';
				echo '<td class="row1">'.$student['lname'].'</td>';
				echo '<td class="row1">'.$student['email'].'</td>';
				echo '<td class="row1">'.$student['uname'].'</td>';
				echo '<td class="row1" align="center"><input type="checkbox" name="remove'.$i.'" />';		
			}
			$i++;
			echo '</tr>';
		}
		echo '<tr><td class="row1" colspan="6" align="center"><input type="submit" name="submit" value="'._AT('resubmit').'" class="button" /> ';
		
		if ($still_errors || $err_count>0) {	
			echo '<input type="submit" name="submit" value="'._AT('import_course_list').'" class="button" disabled="disabled" />';			
		} else {
			echo '<input type="submit" name="submit" value="'._AT('import_course_list').'" class="button" />';
		}
		
		echo '</td></tr></table>';
		echo '<input type="hidden" name="count" value="'.count($students).'" /></form>';
	} 	
} 

echo '<br /><br />';
require(AT_INCLUDE_PATH.'cc_html/footer.inc.php');
?>