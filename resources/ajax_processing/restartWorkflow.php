<?php
		if ($_GET['resourceID']){
			$resourceID = $_GET['resourceID'];
            echo $resourceID;
            $action = $_GET['action'];
            $workflowDate = $_GET['workflow'];
			$resource = new Resource(new NamedArguments(array('primaryKey' => $resourceID)));

			//log who set off the restart
			$resource->workflowRestartLoginID = $loginID;
			$resource->workflowRestartDate = date( 'Y-m-d' );

			try {
				$resource->save();
                $resource->archiveWorkflow();
				$resource->enterNewWorkflow();
			} catch (Exception $e) {
				echo $e->getMessage();
			}

		}

?>
