jQuery(document).ready(function($){

    $('.fcs_btn').on('click', function(e){
        // alert('k');
        $('.error-msg').val('');
        var email=$('.fcs_input').val();
        var expr = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
        var checked_email=expr.test(email);

        if(checked_email){
            $('#discount-form').submit();
        }else{
            e.preventDefault();
            $('.error-msg').html('Please enter valid email.');
        }
    });
});