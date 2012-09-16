
/**
 * APP
 * Top-level namespace for application code.
 */
var APP = APP || {};

$(document).ready(function(){

	if ( APP.hasOwnProperty("tasks") ){
		var now = new Date();
		var cur_time = now.getHours() * 60 + now.getMinutes();
		for ( task_index in APP.tasks ){
			var task = APP.tasks[task_index];
			var task_timeslot = convert_str_to_timeslot(task.timeslot);
			if ( cur_time >= task_timeslot[0] && cur_time < task_timeslot[1] ){
    			populate_info_bar(task, "activity");
    			break;
			} 
		}
	}

	$('.save-task').click(function() {
		var data = {};
		$("#add-task").find(".controls").children().each( function(index, element){
			if ( $(element).val() == null || $(element).val() == "" ){
				return;
			}
			data[$(element).attr('id')] = ( $.isNumeric( $(element).val() ) ) ? parseInt($(element).val()) : $(element).val();
		});
		data['inputDue'] = (new Date(data['inputDue'])).getTime() / 1000;

		$.ajax({
			url: 'save_task',
			type: 'POST',
			data: data,
			success: function(response){
				response = $.parseJSON(response);
				if ( response.hasOwnProperty('result') && response.result == 'false' ){
					if ( response.error == "User not logged in" ){
						window.location.replace("/autodo");
					}
				}else{
					window.location.reload()
				}
			}
		});
	});

    $("#inputDue").datetimepicker({
    	ampm: true,
    	stepMinute: 10,
    	minDate: "0d"
    });

    $(".row-fluid").click(function(eventObj){
    	var task;
    	var type;
    	if ( $(eventObj.srcElement).parent().has(".conflict").length > 0 ){
	    	var task_name = $(eventObj.srcElement).parent().children(".conflict")[0].innerText;
	    	conflict_index = $(".warning-bar").children(".row-fluid").index($(eventObj.srcElement).parent());
			task = APP.conflicts[conflict_index];
			type = "conflict";
    		
    	}else if ( $(eventObj.srcElement).parent().has(".activity").length > 0 ){
	    	var task_name = $(eventObj.srcElement).parent().children(".activity")[0].innerText;
	    	activity_index = $(".schedule").children(".row-fluid").index($(eventObj.srcElement).parent());
			task = APP.tasks[activity_index];
			type = "activity";
    	}else{
    		return;
    	}

    	populate_info_bar(task, type);
    });
});

function populate_info_bar( task, type ){

    	$(".info-bar").empty();

    	$(".info-bar").append("<h4>" + task.name + "</h4>");

    	if ( task.hasOwnProperty("priority") && task.priority < 3 ){
    		var priority_text = "";

			switch(task.priority){
				case 0:
					priority_text  += "Optional";
				 	break;
				case 1:
					priority_text  += "Low";
				  	break;
				case 2:
					priority_text  += "High";
				  	break;
			}
    		$(".info-bar").append("<p>Priority: " + priority_text + "</p>");
    	}

    	if ( type == "conflict" && task.hasOwnProperty("duration") ){

		    var hours = Math.floor( task.duration / 60);          
		    var minutes = task.duration % 60;
    		$(".info-bar").append("<p>Duration: " + hours + ":" + pad(minutes,2) + "</p>");

    	}

    	if ( task.hasOwnProperty("timeslot") ){
    		$(".info-bar").append("<p>Timeslot: " + task.timeslot + "</p>");
    	}

    	if ( task.hasOwnProperty("recurrences") && task.recurrences.length > 1 ){
    		var recurrence_text = "";
    		for ( var index = 0; index < task.recurrences.length; index++ ){
    			if ( index != 0 ){
    				recurrence_text  += ", ";
    			}
    			switch(task.recurrences[index]){
					case 0:
    					recurrence_text  += "Sunday";
					 	break;
					case 1:
    					recurrence_text  += "Monday";
					  	break;
					case 2:
    					recurrence_text  += "Tuesday";
					  	break;
					case 3:
    					recurrence_text  += "Wednesday";
					  	break;
					case 4:
    					recurrence_text  += "Thursday";
					  	break;
					case 5:
    					recurrence_text  += "Friday";
					  	break;
					case 6:
    					recurrence_text  += "Saturday";
					  	break;
				}
    		}
    		$(".info-bar").append("<p>Weekly Recurrences: " + recurrence_text + "</p>");
    	}

    	if ( task.hasOwnProperty("due") ){
    		$(".info-bar").append("<p>Due: " + task.due + "</p>");
    	}

    	if ( task.hasOwnProperty("duration_remaining") ){
    		var hours = Math.floor( task.duration_remaining / 60);          
		    var minutes = task.duration_remaining % 60;
    		$(".info-bar").append("<p>Task Time Remaining: " + hours + ":" + pad(minutes,2) + "</p>");

    	}

}

function pad(number, length) {
   
    var str = '' + number;
    while (str.length < length) {
        str = '0' + str;
    }
   
    return str;

}

function convert_str_to_timeslot(timestr){

	var times = timestr.split("-");

	var ret = [];

	for ( i in times ){
		var hr = parseInt(times[i].split(":")[0]);
		var min = parseInt(times[i].split(":")[1]);
		ret.push( hr * 60 + min );
	}

	return ret;
}