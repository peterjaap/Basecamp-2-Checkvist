<?
error_reporting(E_ALL); 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 3.2//EN">
<html>
  <head>
    <title>Sync</title>
	<style>span.indent { margin-left:30px; }</style>
  </head>
  <body>
<?
$start = microtime(true);
/*
	Code written by Peter Jaap Blaakmeer <peterjaap@blaakmeer.com>, October 2010
*/

// Include Basecamp2Checkvist class
require_once("BC2CV.class.php");
// Make new object
$z = new BC2CV("bc_username","bc_password","bc_url","cv_username","cv_password");
// Define in which list you want to put the tasks
$placeInChecklist = "name_of_your_list";
// Retrieve all projects
$projects = $z->Basecamp();
// Define tab for aesthetic purposes
$tab = "<span class=\"indent\">&nbsp;</span>";

// Loop through all projects to gather todo lists and items and insert them into Checkvist
foreach($projects->project as $project) {
	echo "<B>Project</B>: ".$project->{'name'}."<br />";
	
	// Retrieve all todo lists from Basecamp
	$todolists = $z->Basecamp("todolists",$project->{'id'});

	foreach($todolists as $todolist) {
		echo $tab."<B>Todo list</B>: ".$todolist->{'name'}."<br />";
		// Retrieve checklist_id
		$checklist_id = $z->getChecklistId($placeInChecklist);
		// Get parent_id XML element
		$parent_id = $z->createTask($checklist_id,$todolist->{'name'});
		// Check whether the task already exists
		$i=0;
		$todolist_vars = get_object_vars($todolist);
		// Retrieve milestones
		$milestones = $z->Basecamp("milestones",$project->{'id'});
		// Check if there is a milestone set for the entire project
		if(isset($todolist_vars['milestone-id']) AND !empty($todolist_vars['milestone-id'])) {
			// Find the corresponding milestone and its duedate
			foreach($milestones as $milestone) {
				if($milestone->{'id'}==$todolist_vars['milestone-id']) {
					$duedate = strtotime($milestone->{'deadline'});
				}
			}
		}
		
		if($parent_id!==false) {
			// Get parent_id from XML element
			$parent_id = $parent_id->{'id'};
			// Retrieve the todo items from the list
			$todoitems = $z->Basecamp("todoitems",$todolist->{'id'});
			foreach($todoitems as $todoitem) {
				//print_r($todoitem->{'due-at'}); //     [due-at] => 2010-10-06T22:00:00Z
				echo $tab.$tab."<B>Todo item</B>: ".$todoitem->{'content'}." - synchronized!<br />";
				// Check if the todo item itself has a duedate. If so; use that one.
				$vars = get_object_vars($todoitem);
				if(isset($vars['due-at']) AND !is_object($vars['due-at'])) {
					$duedate = strtotime("+1 day ",strtotime(substr($vars['due-at'],0,10)));
				}
				if(!is_numeric($duedate)) { $duedate = false; }
				if((string)$vars['completed']=="false") { $completed = false; } else { $completed = true; }
				// Place the found items in the todo list
				// Make it so #1
				$z->placeTaskInCheckvist($placeInChecklist,$parent_id,$todoitem->{'content'},$completed,$duedate);
				$i++;
			}
		}
	}
	echo "<br />";
	$end = microtime(true);
	$time = $end-$start;
	if($i!=0) {
		// Succes! Yippie, hoorayy, let's crack open some beers!
		echo "All tasks have been succesfully synchronized and it only took me ".$time." seconds!";
	} else {
		// Both Basecamp and Checkvist are feeling perky.
		echo "There was nothing to synchronize. I just wasted ".$time." precious seconds.";
	}
}
?>
  </body>
</html>

