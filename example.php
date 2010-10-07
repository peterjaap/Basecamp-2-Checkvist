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
/*
	Code written by Peter Jaap Blaakmeer <peterjaap@blaakmeer.com>, October 2010
*/

// Include Basecamp2Checkvist class
require_once("BC2CV.class.php");
// Make new object
$z = new BC2CV();
// Define in which list you want to put the tasks
$placeInChecklist = "BWD";
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
		if($parent_id!==false) {
			// Get parent_id from XML element
			$parent_id = $parent_id->{'id'};
			// Retrieve the todo items from the list
			$todoitems = $z->Basecamp("todoitems",$todolist->{'id'});
			foreach($todoitems as $todoitem) {
				echo $tab.$tab."<B>Todo item</B>: ".$todoitem->content." - synchronized!<br />";
				// Place the found items in the todo list
				$z->placeTaskInCheckvist($placeInChecklist,$parent_id,$todoitem->content);
				$i++;
			}
		}
	}
	echo "<br />";
	if($i!=0) {
		// Succes! Yippie, hoorayy, let's crack open some beers!
		echo "All tasks have been succesfully synchronized!";
	} else {
		// Both Basecamp and Checkvist are feeling perky.
		echo "There was nothing to synchronize.";
	}
}
?>
  </body>
</html>