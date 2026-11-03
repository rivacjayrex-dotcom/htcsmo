@php
    $feedbackMessages = [];
    foreach (['error', 'warning', 'success', 'info'] as $feedbackTone) {
        if (session()->has($feedbackTone)) {
            $feedbackMessages[] = ['tone' => $feedbackTone, 'message' => (string) session($feedbackTone)];
        }
    }

    if (session()->has('status')) {
        $feedbackStatus = match (session('status')) {
            'profile-updated' => __('Account information updated successfully.'),
            'password-updated' => __('Password updated successfully.'),
            'verification-link-sent' => __('A new verification link has been sent to your email address.'),
            default => session('status'),
        };
        $feedbackMessages[] = ['tone' => 'success', 'message' => (string) $feedbackStatus];
    }

    $feedbackErrors = [];
    foreach ($errors->getBags() as $feedbackBag) {
        $feedbackErrors = array_merge($feedbackErrors, $feedbackBag->all());
    }
    if ($feedbackErrors) {
        array_unshift($feedbackMessages, [
            'tone' => 'error',
            'title' => __('Please check your entries'),
            'messages' => array_values(array_unique($feedbackErrors)),
        ]);
    }
@endphp
@if($feedbackMessages)
    <script type="application/json" data-feedback-messages>{!! \Illuminate\Support\Js::encode($feedbackMessages) !!}</script>
@endif
