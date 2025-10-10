$(document).ready(function() {
    // Transfer form validation
    $('#transferForm').on('submit', function(e) {
        e.preventDefault();
        let valid = true;
        const amountRegex = /^\d+(\.\d{1,2})?$/;

        if ($('#fromAccount').val() === '') {
            $('#fromAccountError').show();
            valid = false;
        } else {
            $('#fromAccountError').hide();
        }

        if ($('#toAccount').val() === '') {
            $('#toAccountError').show();
            valid = false;
        } else {
            $('#toAccountError').hide();
        }

        if (!amountRegex.test($('#transferAmount').val()) || $('#transferAmount').val() <= 0) {
            $('#amountError').show();
            valid = false;
        } else {
            $('#amountError').hide();
        }

        if (valid) {
            alert('Transfer submitted successfully! (Mock)');
            this.submit();
        }
    });
    
    // Password toggle for login form
    $(document).on('click', '.toggle-password', function() {
        const target = $($(this).data('target'));
        if (!target || !target.length) return;
        const type = target.attr('type') === 'password' ? 'text' : 'password';
        target.attr('type', type);
        $(this).text(type === 'password' ? 'Show' : 'Hide');
    });

    // Open account form: basic password confirmation check
    $(document).on('submit', 'form[action="create_account.php"]', function(e) {
        const p1 = $('#password').val() || $('#passwordReg').val() || $('#password');
        const p2 = $('#passwordConfirm').val();
        if (p2 !== undefined && p1 !== p2) {
            e.preventDefault();
            alert('Passwords do not match.');
            return false;
        }
        return true;
    });

    // Success flow: auto-redirect when a .alert.alert-success is present and a data-redirect target is set on body
    $(function() {
        const success = $('.alert.alert-success');
        if (success.length) {
            const redirect = $('body').data('redirect');
            if (redirect) {
                setTimeout(function() { window.location.href = redirect; }, 1200);
            }
            // small fade animation
            success.css({opacity:0}).animate({opacity:1}, 600);
        }
    });
});