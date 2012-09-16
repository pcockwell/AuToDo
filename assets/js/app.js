
/**
 * APP
 * Top-level namespace for application code.
 */
var APP = APP || {};

$(document).ready(function(){

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

    $(".activity").click(function(eventObj){
    	var task_name = eventObj.srcElement.innerText;
    	var index = $(".activity").index(eventObj.srcElement);
    	var task = APP.tasks[index];

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
    		$(".info-bar").append("<p>Task Time Remaining: " + hours + ":" + minutes + "</p>");

    	}
    });

    $(".conflict").click(function(eventObj){
    	var task_name = eventObj.srcElement.innerText;
    	var index = $(".conflict").index(eventObj.srcElement);
    	var task = APP.conflicts[index];

    	$(".info-bar").empty();

    	$(".info-bar").append("<h4>" + task.name + "</h4>");

    	if ( task.hasOwnProperty("priority") && task.priority < 3 ){
    		var priority_text = "";

			switch(task.priority){
				case 0:
					priority_text += "Optional";
				 	break;
				case 1:
					priority_text += "Low";
				  	break;
				case 2:
					priority_text += "High";
				  	break;
			}
    		$(".info-bar").append("<p>Priority: " + priority_text + "</p>");
    	}

    	if ( task.hasOwnProperty("timeslot") ){
    		$(".info-bar").append("<p>Timeslot: " + task.timeslot + "</p>");
    	}

    	if ( task.hasOwnProperty("due") ){
    		$(".info-bar").append("<p>Due: " + task.due + "</p>");
    	}
    });
});