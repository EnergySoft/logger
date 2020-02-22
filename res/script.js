$(document).ready(function() {
    $('.group_header').click(function(){
        $(this).parent().toggleClass('extended');
    });
    $('.object_header').click(function(){
        $(this).parent().toggleClass('extended');
    });
});

$('#ext_all_groups').click(function(){
    $('.group').addClass('extended');
});

function extend_table(c, obj){
    $('.tr_cycle_'+c).removeClass('row_hidden');
    $('#tr_ex_cycle_'+c).hide();
}