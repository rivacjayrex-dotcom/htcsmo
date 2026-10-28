<x-mail::message>
# Your account has been approved

Hello {{ $recipientName }},

The Supply Management Office has approved your account for the HTC Supply Management System. You can now sign in, submit supply requisitions, and track your requests.

<x-mail::button :url="$loginUrl">
Sign in to your account
</x-mail::button>

Use your username or school ID and the password you chose when you registered.

Forgot your password? [Request a password reset]({{ $resetPasswordUrl }}).

If you did not request this account, please contact the Supply Management Office.

Thank you,<br>
HTC Supply Management Office
</x-mail::message>
