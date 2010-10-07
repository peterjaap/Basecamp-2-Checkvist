<? 
class BC2CV {
	private $bc_username = "username";
	private $bc_password = "password";
	private $bc_url = 'https://yoururl.basecamphq.com/';
	
	private $cv_username = "emailaddress";
	private $cv_password = "password";
	
	/* 
		Functions:
		- Basecamp
		- Checkvist
			- retrieveTasks
			- createTask
			- getChecklistId
			- placeTaskInCheckvist
		- array_find_r (private)
		
		Documentation:
			- Basecamp API documentation; http://developer.37signals.com/basecamp/index.shtml
			- Checkvist API documentation; http://checkvist.com/auth/api
	
		Todo:
			- make exception on duplicates check for tasks with different parents
			- add due date syncing
			- needs more error handling. it'll probably crash like a chimp in a banana-powered rocketship
			
		Changelog:
			- v1.0 released on 07-10-2010
	
		Class written by Peter Jaap Blaakmeer <peterjaap@blaakmeer.com>, October 2010
	*/
	
	public function Basecamp($item="projects",$id=null) {
		// General retrieval function for projects, todolists and todoitems from Basecamp
		$session = curl_init();
		if($item=="projects") {
			curl_setopt($session, CURLOPT_URL, $this->bc_url.'projects.xml');
		} elseif($item=="todolists") {
			curl_setopt($session, CURLOPT_URL, $this->bc_url.'projects/'.$id.'/todo_lists.xml');
		} elseif($item=="todoitems") {
			curl_setopt($session, CURLOPT_URL, $this->bc_url.'todo_lists/'.$id.'/todo_items.xml');
		}
		curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($session, CURLOPT_HTTPGET, 1);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session,CURLOPT_USERPWD,$this->bc_username . ":" . $this->bc_password);
		
		$response = curl_exec($session);
		$xmlobject = new SimpleXMLElement($response);
		curl_close($session);
		return $xmlobject;
	}

	public function Checkvist($item="checklists",$id=null) {
		// Retrieve all checklists from Checkvist
		$session = curl_init();
		if($item=="checklists") {
			curl_setopt($session, CURLOPT_URL, 'http://checkvist.com/checklists.xml');
		}
		curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($session, CURLOPT_HTTPGET, 1);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session,CURLOPT_USERPWD,$this->cv_username . ":" . $this->cv_password);
		$response = curl_exec($session);
		$xmlobject = new SimpleXMLElement($response);
		curl_close($session);
		return $xmlobject;
	}
	
	public function retrieveTasks($checklist_id) {
		// Retrieve all tasks from Checkvist
		$session = curl_init();
		$url = 'http://checkvist.com/checklists/'.$checklist_id.'/tasks.xml';
		curl_setopt($session, CURLOPT_URL, $url);
		curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($session, CURLOPT_HTTPGET, 1);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session,CURLOPT_USERPWD,$this->cv_username . ":" . $this->cv_password);
		$response = curl_exec($session);
		$xmlobject = new SimpleXMLElement($response);
		curl_close($session);
		foreach($xmlobject as $task) {
			$tasks[] = array("content"=>(string)$task->{'content'},"parent_id"=>(int)$task->{'parent_id'});
		}
		return $tasks;
	}

	public function createTask($checklist_id,$content,$parent=null) {
		$tasks = $this->retrieveTasks($checklist_id);
		if($this->array_find_r((string)$content,$tasks)===false) {
			// No duplicate tasks, so create it
			$session = curl_init();
			if($parent==null) {
				$data = 'task[content]='.urlencode($content);
			} else {
				$data = "task[content]=".urlencode($content)."&task[parent_id]=".urlencode($parent);
			}
			$url = 'http://checkvist.com/checklists/'.$checklist_id.'/tasks.xml?'.$data;
			curl_setopt($session, CURLOPT_URL, $url);
			curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($session, CURLOPT_POST, true);
			curl_setopt($session, CURLOPT_HEADER, false);
			curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($session,CURLOPT_USERPWD,$this->cv_username . ":" . $this->cv_password);
			$response = curl_exec($session);
			$xmlobject = new SimpleXMLElement($response);
			curl_close($session);
			return $xmlobject;
		} else {
			// Task already in Checkvist
			return false;
		}
	}

	public function getChecklistId($list) {
		// Finds checklist_id on the basis on listname ($list)
		$checklists = $this->Checkvist();
		foreach($checklists as $checklist) {
			if($checklist->{'name'}==$list) {
				$checklist_id = $checklist->{'id'};
			}
		}
		return $checklist_id;
	}

	public function placeTaskInCheckvist($list,$parent_id,$content) {
		// Retrieve checklist_id and place task in it
		$checklist_id = $this->getChecklistId($list);
		$content = (string)$content;
		
		if(isset($checklist_id)) {
			$this->createTask($checklist_id,$content,$parent_id);
		}
	}

	private function array_find_r($needle, $haystack, $partial_matches = false, $search_keys = false) {
		// array_search with recursive searching, optional partial matches and optional search by key - http://nl3.php.net/manual/en/function.array-search.php#95926
        if(!is_array($haystack)) return false;
        foreach($haystack as $key=>$value) {
            $what = ($search_keys) ? $key : $value;
            if($needle===$what) return $key;
            else if($partial_matches && @strpos($what, $needle)!==false) return $key;
            else if(is_array($value) && $this->array_find_r($needle, $value, $partial_matches, $search_keys)!==false) return $key;
        }
        return false;
    }
}
