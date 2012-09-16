<?php

$this->load->view('header');

?>
<div class="container">
    <div class="row-fluid">
    	<div class="span8 schedule">
    		<h4>Today's Schedule</h4>
            <?php foreach ( $task_list as $task ): ?>
    		<div class="row-fluid">
                <?php 
                    switch ($task->priority){
                        case 0:
                            $priority_type = 'priority-optional';
                            break;
                        case 1:
                            $priority_type = 'priority-low';
                            break;
                        case 2:
                            $priority_type = 'priority-high';
                            break;
                        default:
                            $priority_type = 'priority-static';
                            break;
                    }
                ?>
    			<div class="span1 <?php echo $priority_type ?>">
    			</div>
    			<div class="span3 timeslot">
                    <?php echo $task->timeslot; ?>
    			</div>
    			<div class="span8 activity">
    				<?php echo $task->name; ?>
    			</div>
            </div>
            <?php endforeach ?>
    	</div>
    	<div class="span4 info-bar">
    	</div>
    </div>
    <?php if ( !is_null($conflicts) ): ?>
    <div class="row-fluid">
        <div class="span12 warning-bar">
            <p>We've detected conflicts due to deadlines and were unable to schedule the following tasks:</p>
            <?php foreach ($conflicts as $conflict_task): ?>
            <div class="row-fluid">
                <div class="span12 conflict">
                    <?php echo $conflict_task->name; ?>
                </div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>
</div>

<div class="modal hide fade" id="add-task" tabindex="-1" style="display: none;">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel">Add Task</h3>
    </div>
    <div class="modal-body">
        <form class="form-horizontal">
            <div class="control-group">
                <label class="control-label" for="inputTaskName">Name</label>
                <div class="controls">
                    <input type="text" id="inputTaskName" placeholder="Task Name">
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="inputPriority">Priority</label>
                <div class="controls">
                    <select id="inputPriority" placeholder="Priority">
                        <option value="2">High</option>
                        <option value="1" selected="selected">Low</option>
                        <option value="0">Optional</option>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="inputDuration">Duration</label>
                <div class="controls">
                    <select id="inputDuration" placeholder="Duration">
                        <option value="15">15 Minutes</option>
                        <option value="30" selected="selected">30 Minutes</option>
                        <option value="60">1 Hour</option>
                        <option value="120">2 Hour</option>
                        <option value="180">3 Hour</option>
                        <option value="240">4 Hour</option>
                        <option value="300">5 Hour</option>
                        <option value="360">6 Hour</option>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="inputDue">Due</label>
                <div class="controls">
                    <input type="text" id="inputDue" class="datepicker" placeholder="Due">
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
        <button class="btn btn-primary save-task" data-dismiss="modal" >Save Task</button>
    </div>
</div>
<?php

$this->load->view('footer');

?>