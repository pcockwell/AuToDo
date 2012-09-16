$(document).ready(function(){

	function closeDialog () {
		$('#add-task').modal('hide'); 
	};

	function okClicked () {
		closeDialog ();
	};

    $("#inputDue").datetimepicker({
    	ampm: true,
    	stepMinute: 10,
    	minDate: "0d"
    });
});